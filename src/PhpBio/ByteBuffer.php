<?php

namespace PhpBio;


class ByteBuffer
{
    const ENDIAN_LITTLE  = 1;
    const ENDIAN_BIG     = 2;
    const ENDIAN_MACHINE = 3;

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
     * @var int Is machine order Big or Little endian.
     */
    protected static $machineEndian;

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

        /**
         * Init machine endian
         */
        self::$machineEndian = $this->getMachineEndian();
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
     * @param int $endian
     * @return int
     */
    public function readInt($bytes = 1, $signed = false, $endian = self::ENDIAN_MACHINE)
    {
        if ($bytes > 8) {
            throw new \LengthException("Can't read integer larger 64 bit.");
        }

        if ($bytes > 4 && !$this->can64()) {
            throw new \LengthException('Your system not support 64 bit integers.');
        }

        if ($endian == self::ENDIAN_MACHINE) {
            $endian = self::getMachineEndian();
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
     * @param int $endian
     * @return ByteBuffer
     */
    public function writeInt($data, $bytes = 1, $endian = self::ENDIAN_MACHINE)
    {
        if ($bytes > 8) {
            throw new \LengthException("Can't write integer larger 64 bit.");
        }

        if ($bytes > 4 && !$this->can64()) {
            throw new \LengthException('Your system not support 64 bit integers.');
        }

        $str = Packer::pack(
            Packer::getFormat('int', $bytes * 8, false, $endian),
            $data
        );

        return $this->write($str);
    }

    /**
     * @return bool
     */
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
        return str_pad($data, $fullSize, "\x00", $endian == self::ENDIAN_BIG ? STR_PAD_LEFT : STR_PAD_RIGHT);
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

    /**
     * @return int
     */
    public static function getMachineEndian()
    {
        if (self::$machineEndian) {
            return self::$machineEndian;
        }

        $testInt = 0x00FF;
        $p = pack('S', $testInt);
        return $testInt === current(unpack('v', $p)) ? self::ENDIAN_LITTLE : self::ENDIAN_BIG;
    }

}