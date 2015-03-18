<?php


namespace PhpBio;


class BitBuffer extends ByteBuffer
{
    /**
     * @var int
     */
    private $readBuffer;

    /**
     * @var int
     */
    private $readBufferPosition;

    public function __construct($string = '')
    {
        parent::__construct($string);
        $this->readBuffer = 0;
        $this->setReadBufferPosition(0);
    }

    /**
     * @return int
     */
    public function getReadBufferPosition()
    {
        return $this->readBufferPosition;
    }

    /**
     * @param int $readBufferPosition
     */
    public function setReadBufferPosition($readBufferPosition)
    {
        $this->readBufferPosition = $readBufferPosition;
    }

    public function readInt($bits = 8, $signed = false, $endian = 'm')
    {
        $bytesToRead = ceil($bits / 8);

        if (in_array($bytesToRead, [1, 2, 4, 8]) && !$this->readBuffer && !$this->readBufferPosition) {
            return parent::readInt($bytesToRead, $signed, $endian);
        }

        if ($bits > 64) {
            throw new \LengthException("Can't read integer larger 64 bit.");
        }

        if ($bits > 32 && !parent::can64()) {
            throw new \LengthException('Your system not support 64 bit integers.');
        }

        $bytesToRead = ceil($bytesToRead);
    }
}