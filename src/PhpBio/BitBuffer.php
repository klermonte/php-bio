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
    

    public function readInt($bits = 8, $signed = false, $endian = 'm')
    {
        if ($bits > 64) {
            throw new \LengthException("Can't read integer larger 64 bit.");
        }

        if ($bits > 32 && !parent::can64()) {
            throw new \LengthException('Your system not support 64 bit integers.');
        }

        $shift = $this->getShift();
        $lastByte = $this->getLastByte();
        
        if (!($bits % 8) && !$shift) {
            return parent::readInt($bits / 8, $signed, $endian);
        }



        $bitsLeft = 8 - $shift;

        if ($bitsLeft < $bits) {

            $bytesToRead = (int) ceil(($bits - $bitsLeft) / 8);
            $readBytes = $this->read($bytesToRead);

            if ($endian == 'l') {

                $newStr = '';
                foreach (str_split($readBytes) as $i => $byte) {

                    $highBits = ($lastByte & Mask::$rightMask[$shift]) << $shift;
                    $lastByte = ord($byte);
                    $newByte = $highBits | ($lastByte >> $bitsLeft);
                    $newStr .= chr($newByte);

                }

                $lowBitsOfHighByte = $bits % 8;

                if ($lowBitsOfHighByte) {
                    $newStr .= ord(($lastByte << $shift) >> (8 - $lowBitsOfHighByte));
                }
            }
        }




    }
}