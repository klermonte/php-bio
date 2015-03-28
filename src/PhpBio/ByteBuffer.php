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
     * @var string Is machine order Big or Little endian.
     */
    private static $machineEndian;

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
        if ($bytesCount <= 0) {
            return '';
        }

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

    /**
     * @param int $bytes
     * @param bool $signed
     * @param string $endian
     * @return int
     */
    public function readInt($bytes = 1, $signed = false, $endian = 'm')
    {
        if ($endian == 'm') {
            $endian = self::getMachineEndian();
        }

        $bytes = min($bytes, 8);

        if ($bytes > 4 && !$this->can64()) {
            throw new \LengthException('Your system not support 64 bit integers.');
        }

        $fullBytes = $this->getFullSize($bytes);
        $data = $this->fitTo($this->read($bytes), $fullBytes, $endian);

        return Packer::unpack(
            Packer::getFormat('int', $fullBytes * 8, $signed, $endian),
            $data
        );
    }

    /**
     * @param int $data
     * @param int $bytes
     * @param string $endian
     * @return ByteBuffer
     */
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

    /**
     * @param string $data
     * @param int $fullSize
     * @param string $endian
     * @return string
     */
    protected function fitTo($data, $fullSize, $endian)
    {
        return str_pad($data, $fullSize, "\x00", $endian == 'b' ? STR_PAD_LEFT : STR_PAD_RIGHT);
    }

    /**
     * @param string $bytes
     * @return int
     */
    protected function getFullSize($bytes)
    {
        if ($bytes > 4) {
            return 8;
        } elseif ($bytes > 2) {
            return 4;
        }

        return $bytes;
    }

    public static function getMachineEndian()
    {
        if (self::$machineEndian) {
            return self::$machineEndian;
        }

        $testInt = 0x00FF;
        $p = pack('S', $testInt);
        return $testInt === current(unpack('v', $p)) ? 'l' : 'b';
    }

}