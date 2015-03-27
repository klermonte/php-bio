<?php


namespace PhpBio;


class BitBuffer extends ByteBuffer
{
    /**
     * @var int
     */
    private $lastByte;

    /**
     * @var int
     */
    private $shift;

    public function __construct($string = '')
    {
        parent::__construct($string);
        $this->lastByte = 0;
        $this->setShift(0);
    }

    /**
     * @return int
     */
    public function getShift()
    {
        return $this->shift;
    }

    /**
     * @param int $shift
     */
    public function setShift($shift)
    {
        $this->shift = $shift;
    }
    /**
     * @return int
     */
    public function getLastByte()
    {
        return $this->lastByte;
    }

    /**
     * @param int $lastByte
     */
    public function setLastByte($lastByte)
    {
        $this->lastByte = $lastByte;
    }
    

    public function readInt($bitsToRead = 8, $signed = false, $endian = 'm')
    {
        if ($bitsToRead > 64) {
            throw new \LengthException("Can't read integer larger 64 bit.");
        }

        if ($bitsToRead > 32 && !parent::can64()) {
            throw new \LengthException('Your system not support 64 bit integers.');
        }

        if ($endian == 'm') {
            $endian = $this->getMachineEndian();
        }

        $shift = $this->getShift();
        $highByteSize = $bitsToRead % 8;

        if (!$highByteSize && !$shift) {
            return parent::readInt($bitsToRead / 8, $signed, $endian);
        }

        if (!$shift) {
            $lastByte = ord($this->read(1));
        } else {
            $lastByte = $this->getLastByte();
        }

        $bytesToRead = (int) ceil(($bitsToRead - (8 - $shift)) / 8);

        $sourceBytes = [$lastByte];
        if ($bytesToRead) {
            foreach (str_split($this->read($bytesToRead)) as $byte) {
                $sourceBytes[] = ord($byte);
            }
        }

        $readBits = 0;
        $currentByte = null;
        $newStr = '';
        while ($readBits < $bitsToRead) {

            if ($currentByte === null) {
                $currentByte = array_shift($sourceBytes);
            }

            $batchSize = 8;
            if ($highByteSize && (($endian == 'b' && !$readBits) || ($endian == 'l' && ($bitsToRead - $readBits) < 8))) {
                $batchSize = $highByteSize;
            }

            $newByte = (($currentByte << $shift) & 0xFF) >> (8 - $batchSize);
            if ($batchSize >= (8 - $shift)) {
                $currentByte = array_shift($sourceBytes);
                $lowBits = $currentByte >> (8 - $shift) >> (8 - $batchSize);
                $newByte = $newByte | $lowBits;
            }

            $newStr .= chr($newByte);

            $readBits += $batchSize;
            $shift = ($shift + $batchSize) % 8;
        }

        $this->setLastByte($currentByte);
        $this->setShift($shift);

        $fullBytes = $this->getFullSize(strlen($newStr));
        $newStr = $this->fitTo($newStr, $fullBytes, $endian);

        return Packer::unpack(
            Packer::getFormat('int', $fullBytes * 8, $signed, $endian),
            $newStr
        );
    }
}