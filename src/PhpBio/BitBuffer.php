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

    /**
     * @param int $position
     * @return $this
     */
    public function setPosition($position)
    {
        parent::setPosition(floor($position / 8));

        $shift = $position % 8;
        $this->setShift($shift);

        if ($shift) {
            $this->setLastByte(ord(parent::read(1)));
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return parent::getPosition() + $this->getShift();
    }

    /**
     * @param int $bitsToRead
     * @param int $endian
     * @return string
     */
    public function read($bitsToRead = 8, $endian = null)
    {
        if ($endian === null) {
            $endian = $this->getEndian();
        }

        $shift = $this->getShift();
        $highByteSize = $bitsToRead % 8;

        if (!$highByteSize && !$shift) {
            return parent::read($bitsToRead / 8);
        }

        if (!$shift) {
            $this->setLastByte(ord(parent::read(1)));
        }

        $bytesToRead = (int) ceil(($bitsToRead - (8 - $shift)) / 8);

        $readBytes = [];
        if ($bytesToRead) {
            foreach (str_split(parent::read($bytesToRead)) as $byte) {
                $readBytes[] = ord($byte);
            }
        }

        $readBits = 0;
        $currentByte = $this->getLastByte();
        $newStr = '';
        while ($readBits < $bitsToRead) {

            $isBigEndianHighByte    = $endian == Endian::ENDIAN_BIG    && !$readBits;
            $isLittleEndianHighByte = $endian == Endian::ENDIAN_LITTLE && ($bitsToRead - $readBits) < 8;

            $batchSize = 8;
            if ($highByteSize && ($isBigEndianHighByte || $isLittleEndianHighByte)) {
                $batchSize = $highByteSize;
            }

            $newByte = (($currentByte << $shift) & 0xFF) >> (8 - $batchSize);
            if ($batchSize >= (8 - $shift)) {
                $currentByte = array_shift($readBytes);
                $lowBits = $currentByte >> (8 - $shift) >> (8 - $batchSize);
                $newByte = $newByte | $lowBits;
            }

            $newStr .= chr($newByte);

            $readBits += $batchSize;
            $shift = ($shift + $batchSize) % 8;
        }

        $this->setLastByte($currentByte);
        $this->setShift($shift);

        return $newStr;
    }

    /**
     * @param int $bitsToRead
     * @param bool $signed
     * @param int $endian
     * @return int
     */
    public function readInt($bitsToRead = 8, $signed = false, $endian = null)
    {
        if ($bitsToRead > 64) {
            throw new \LengthException("Can't read integer larger 64 bit.");
        }

        if ($bitsToRead > 32 && !$this->can64()) {
            throw new \LengthException('Your system not support 64 bit integers.');
        }

        if ($endian === null) {
            $endian = $this->getEndian();
        }

        $newStr = $this->read($bitsToRead, $endian);

        $fullBytes = $this->getFullSize(strlen($newStr));
        $newStr = $this->fitTo($newStr, $fullBytes, $endian);

        return Packer::unpack(
            Packer::getFormat('int', $fullBytes * 8, $signed, $endian),
            $newStr
        );
    }

    public function writeInt($data, $bitsToWrite = 8, $endian = null)
    {

    }
}