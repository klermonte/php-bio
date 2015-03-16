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
     * @return int
     */
    public function write($bytes)
    {
        $this->position += strlen($bytes);
        return fwrite($this->handle, $bytes);
    }

}