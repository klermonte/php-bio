<?php

namespace PhpBio;


class ByteBuffer
{
    /**
     * @var resource
     */
    private $handle;

    /**
     * @var int
     */
    private $size;

    /**
     * @var int
     */
    private $position;

    /**
     * @param string $string
     */
    public function __construct($string = '')
    {
        if (!is_resource($string)) {
            $handle = fopen('php://memory', 'br+');
            fwrite($handle, $string);
            rewind($handle);
        } else {
            $handle = $string;
        }

        $this->handle = $handle;
        $this->size = fstat($this->handle)['size'];
        $this->setPosition(0);
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     * @return $this
     */
    public function setPosition($position)
    {
        $this->position = $position;
        fseek($this->handle, $position);

        return $this;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $bytesCount
     * @return int
     */
    public function canRead($bytesCount)
    {
        return $this->position + $bytesCount <= $this->size;
    }

    /**
     * @param int $bytesCount
     * @return string
     */
    public function read($bytesCount)
    {
        if ($this->canRead($bytesCount)) {
            $this->position += $bytesCount;
            return fread($this->handle, $bytesCount);
        } else {
            throw new \OutOfBoundsException('Exceeds the boundary of the file.');
        }
    }

    /**
     * @param string $bytes
     * @return $this
     */
    public function write($bytes)
    {
        $this->position += strlen($bytes);
        fwrite($this->handle, $bytes);
        return $this;
    }

    public function readInt($bytes = 1, $signed = false, $endian = 'm')
    {
        $bytes = min($bytes, 8);

        if ($bytes > 4 && !$this->can64()) {
            throw new \LengthException('Your system not support 64 bit integers.');
        }

        return Packer::unpack(
            Packer::getFormat('int', $bytes * 8, $signed, $endian),
            $this->read($bytes)
        );
    }

    public function writeInt($data, $bytes = 1, $endian = 'm')
    {
        $bytes = min($bytes, 8);

        if ($bytes > 4 && !$this->can64()) {
            throw new \LengthException('Your system not support 64 bit integers.');
        }

        $str = Packer::pack(
            Packer::getFormat('int', $bytes * 8, false, $endian),
            $data
        );

        return $this->write($str);
    }

    protected function can64()
    {
        return PHP_INT_SIZE == 8;
    }

}