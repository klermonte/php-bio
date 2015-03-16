<?php


namespace PhpBio;


class Buffer
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var Writer
     */
    private $writer;

    public function __construct($string)
    {
        $buffer = new ByteBuffer($string);
        $this->reader = new Reader($buffer);
        $this->writer = new Writer($buffer);
    }

    public function readInt8()
    {
        return $this->reader->extract('c');
    }

    public function readUInt8()
    {
        return $this->reader->extract('C');
    }

    public function readInt16()
    {
        return $this->reader->extract('s');
    }

    public function readUInt16()
    {
        return $this->reader->extract('S');
    }

    public function readUInt16BE()
    {
        return $this->reader->extract('n');
    }

    public function readUInt16LE()
    {
        return $this->reader->extract('v');
    }

    public function readInt32()
    {
        return $this->reader->extract('l');
    }

    public function readUInt32()
    {
        return $this->reader->extract('L');
    }

    public function readUInt32BE()
    {
        return $this->reader->extract('N');
    }

    public function readUInt32LE()
    {
        return $this->reader->extract('V');
    }
}