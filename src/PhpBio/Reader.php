<?php

namespace PhpBio;


class Reader
{
    private $lenthMap = [
        'c' => 1,
        'C' => 1,
        's' => 2,
        'S' => 2,
        'n' => 2,
        'v' => 2,
        'l' => 4,
        'L' => 4,
        'N' => 4,
        'V' => 4,
        'q' => 8,
        'Q' => 8,
        'J' => 8,
        'P' => 8,
    ];

    /**
     * @var ByteBuffer
     */
    private $byteBuffer;

    /**
     * @var int
     */
    private $bitsBuffer;

    /**
     * @var int
     */
    private $bitPosition;

    public function __construct(ByteBuffer $buffer)
    {
        $this->setByteBuffer($buffer);
        $this->bitPosition = 0;
    }

    /**
     * @return mixed
     */
    public function getByteBuffer()
    {
        return $this->byteBuffer;
    }

    /**
     * @param mixed $byteBuffer
     */
    public function setByteBuffer(ByteBuffer $byteBuffer)
    {
        $this->byteBuffer = $byteBuffer;
    }

    /**
     * @param string $format
     * @return int|string
     */
    public function extract($format)
    {
        $encoded = $this->read($this->lenthMap[$format]);

        list(, $result) = unpack($format, $encoded);

        return $result;
    }

    public function read($length)
    {
        return $this->byteBuffer->read($length);
    }
}