<?php

namespace PhpBio;


class ByteBuffer
{
    /**
     * @var int Endian for current buffer instance.
     */
    protected $endian;

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
     * @param null $endian
     */
    public function __construct($string = '', $endian = null)
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

        if ($endian === null) {
            $endian = Endian::getMachineEndian();
        }

        $this->setPosition(0)->setEndian($endian);
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
        $this->position = (int) $position;
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
     * @return int
     */
    public function getEndian()
    {
        return $this->endian;
    }

    /**
     * @param int $endian
     * @return $this
     */
    public function setEndian($endian)
    {
        $this->endian = $endian;

        return $this;
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

        if ($this->position > $this->size) {
            $this->size = $this->position;
        }

        return $this;
    }

    /**
     * @param int $bytes
     * @param bool $signed
     * @param int $endian
     * @return int
     */
    public function readInt($bytes = 1, $signed = false, $endian = null)
    {
        if ($bytes > 8) {
            throw new \LengthException("Can't read integer larger 64 bit.");
        }

        if ($bytes > 4 && !$this->can64()) {
            throw new \LengthException('Your system not support 64 bit integers.');
        }

        if ($endian === null) {
            $endian = $this->getEndian();
        }

        $fullBytes = $this->getFullSize($bytes);
        $data = $this->fitTo($this->read($bytes), $fullBytes, $endian);

        return Packer::unpack(
            Packer::getFormat('int', $fullBytes * 8, $signed, $endian),
            $data
        );
    }

    /**
     * @param int $int
     * @param int $bytes
     * @param int $endian
     * @return ByteBuffer
     */
    public function writeInt($int, $bytes = 1, $endian = null)
    {
        if ($bytes > 8) {
            throw new \LengthException("Can't write integer larger 64 bit.");
        }

        if ($bytes > 4 && !$this->can64()) {
            throw new \LengthException('Your system not support 64 bit integers.');
        }

        if ($endian === null) {
            $endian = $this->getEndian();
        }

        $str = Packer::pack(
            Packer::getFormat('int', $bytes * 8, false, $endian),
            $int
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
        if ($fullSize > strlen($data)) {
            $data = str_pad($data, $fullSize, "\x00", $endian == Endian::ENDIAN_BIG ? STR_PAD_LEFT : STR_PAD_RIGHT);
        } else {
            $data = substr($data, ($endian == Endian::ENDIAN_LITTLE ? 0 : -$fullSize), $fullSize);
        }
        return $data;
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
}