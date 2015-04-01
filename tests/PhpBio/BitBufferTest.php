<?php
namespace PhpBio;

use PHPUnit_Framework_TestCase;

class BitBufferTest extends PHPUnit_Framework_TestCase
{
    public $empty;
    public $source;

    public function setUp()
    {
        $this->source = "\xF8" . // 1111 1000
                        "\xA5" . // 10|10 0101   BE: 00000010 01011011
                        "\xB1" . // 1011| 0001   LE: 00000011 10010110
                        "\x42" . // 0100 0010
                        "\xCD" . // 1100 1101
                        "\x01" . // 0000 0001
                        "\x39" . // 0011 1001
                        "\xD0" . // 1101 0000
                        "\x4B" . // 0100 1011
                        "\x0A";  // 0000 1010

        $this->empty = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
    }

    public function values()
    {
        $cases = [
            // within one entire byte
            [0, 8, 0, Endian::ENDIAN_LITTLE, 0b11111000, "\xF8\x00\x00\x00\x00\x00\x00\x00\x00\x00"],
            [0, 7, 0, Endian::ENDIAN_LITTLE, 0b01111100, "\xF8\x00\x00\x00\x00\x00\x00\x00\x00\x00"],
            [0, 3, 0, Endian::ENDIAN_LITTLE, 0b00000111, "\xE0\x00\x00\x00\x00\x00\x00\x00\x00\x00"],
            [1, 7, 0, Endian::ENDIAN_LITTLE, 0b01111000, "\x78\x00\x00\x00\x00\x00\x00\x00\x00\x00"],
            [5, 3, 0, Endian::ENDIAN_LITTLE, 0b00000000, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],
            [2, 4, 0, Endian::ENDIAN_LITTLE, 0b00001110, "\x38\x00\x00\x00\x00\x00\x00\x00\x00\x00"],
            [7, 1, 0, Endian::ENDIAN_LITTLE, 0b00000000, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],
            [4, 1, 0, Endian::ENDIAN_LITTLE, 0b00000001, "\x08\x00\x00\x00\x00\x00\x00\x00\x00\x00"],

            // less or equal one byte across 2 bytes
            [1, 8, 0, Endian::ENDIAN_LITTLE, 0b11110001, "\x78\x80\x00\x00\x00\x00\x00\x00\x00\x00"],
            [4, 8, 0, Endian::ENDIAN_LITTLE, 0b10001010, "\x08\xA0\x00\x00\x00\x00\x00\x00\x00\x00"],
            [4, 6, 0, Endian::ENDIAN_LITTLE, 0b00100010, "\x08\x80\x00\x00\x00\x00\x00\x00\x00\x00"],

            // from 1 to 2 bytes across
            [4,  16, 0, Endian::ENDIAN_LITTLE, 0x5B8A, "\x08\xA5\xB0\x00\x00\x00\x00\x00\x00\x00"],
            [4,  16, 0, Endian::ENDIAN_BIG,    0x8A5B, "\x08\xA5\xB0\x00\x00\x00\x00\x00\x00\x00"],
            [14, 16, 0, Endian::ENDIAN_LITTLE, 0b0101000001101100, "\x00\x01\xB1\x40\x00\x00\x00\x00\x00\x00"],
            [14, 16, 0, Endian::ENDIAN_BIG,    0b0110110001010000, "\x00\x01\xB1\x40\x00\x00\x00\x00\x00\x00"],
            [10, 10, 0, Endian::ENDIAN_LITTLE, 0b0000001110010110, "\x00\x25\xB0\x00\x00\x00\x00\x00\x00\x00"],
            [10, 10, 0, Endian::ENDIAN_BIG,    0b0000001001011011, "\x00\x25\xB0\x00\x00\x00\x00\x00\x00\x00"],
            [16, 10, 0, Endian::ENDIAN_LITTLE, 0b0000000110110001, "\x00\x00\xB1\x40\x00\x00\x00\x00\x00\x00"],
            [16, 10, 0, Endian::ENDIAN_BIG,    0b0000001011000101, "\x00\x00\xB1\x40\x00\x00\x00\x00\x00\x00"],
            [23, 15, 0, Endian::ENDIAN_LITTLE, 0b0011001110100001, "\x00\x00\x01\x42\xCC\x00\x00\x00\x00\x00"],
            [23, 15, 0, Endian::ENDIAN_BIG,    0b0101000010110011, "\x00\x00\x01\x42\xCC\x00\x00\x00\x00\x00"],
            [0,  10, 0, Endian::ENDIAN_LITTLE, 0b0000001011111000, "\xF8\x80\x00\x00\x00\x00\x00\x00\x00\x00"],
            [0,  10, 0, Endian::ENDIAN_BIG,    0b0000001111100010, "\xF8\x80\x00\x00\x00\x00\x00\x00\x00\x00"],

            // 2 entire bytes
            [0,  16, 0, null,               0xA5F8, "\xF8\xA5\x00\x00\x00\x00\x00\x00\x00\x00"],
            [0,  16, 0, Endian::ENDIAN_BIG, 0xF8A5, "\xF8\xA5\x00\x00\x00\x00\x00\x00\x00\x00"],
            [16, 16, 0, null,               0x42B1, "\x00\x00\xB1\x42\x00\x00\x00\x00\x00\x00"],
            [16, 16, 0, Endian::ENDIAN_BIG, 0xB142, "\x00\x00\xB1\x42\x00\x00\x00\x00\x00\x00"],

            // from 2 to 3 bytes across
            [20, 20, 0, null,               0x0D2C14, "\x00\x00\x01\x42\xCD\x00\x00\x00\x00\x00"],
            [20, 20, 0, Endian::ENDIAN_BIG, 0x0142CD, "\x00\x00\x01\x42\xCD\x00\x00\x00\x00\x00"],

            // end of file
            [60, 20, 0, null,               0x0AB004, "\x00\x00\x00\x00\x00\x00\x00\x00\x4B\x0A"],
            [60, 20, 0, Endian::ENDIAN_BIG, 0x004B0A, "\x00\x00\x00\x00\x00\x00\x00\x00\x4B\x0A"],

        ];

        if (PHP_INT_SIZE == 8) {
            $cases = array_merge($cases, [
                [ 0, 60, 0, Endian::ENDIAN_BIG, 0x0F8A5B142CD0139D, "\xF8\xA5\xB1\x42\xCD\x01\x39\xD0\x00\x00"],
                [ 0, 60, 0, null,               0x0D3901CD42B1A5F8, "\xF8\xA5\xB1\x42\xCD\x01\x39\xD0\x00\x00"],
                [20, 40, 0, Endian::ENDIAN_BIG, 0x000000142CD0139D, "\x00\x00\x01\x42\xCD\x01\x39\xD0\x00\x00"],
                [20, 40, 0, null,               0x0000009D13D02C14, "\x00\x00\x01\x42\xCD\x01\x39\xD0\x00\x00"],
            ]);
        }

        return $cases;
    }

    /**
     * @dataProvider values
     */
    public function testReadInt($offset, $bitCount, $signed, $endian, $result)
    {
        $bitBuffer = new BitBuffer($this->source);

        if ($offset) {
            $bitBuffer->setPosition($offset);
        }

        $this->assertSame($result, $bitBuffer->readInt($bitCount, $signed, $endian));
    }

    /**
     * @dataProvider values
     */
    public function testWriteInt($offset, $bitCount, $signed, $enian, $number, $result)
    {
        $bitBuffer = new BitBuffer($this->empty);

        if ($offset) {
            $bitBuffer->setPosition($offset);
        }

        $bitBuffer->writeInt($number, $bitCount, $enian);
        $bitBuffer->setPosition(0);

        $read = $bitBuffer->read(80);

        $messge = 'expect: ' . self::readable($result) . PHP_EOL .
                  'actual: ' . self::readable($read);

        $this->assertSame($result, $read, $messge);
    }

    public function testPosition()
    {
        $bitBuffer = new BitBuffer($this->source);

        $bitBuffer->setPosition(35);

        $this->assertSame(35, $bitBuffer->getPosition());

        $bitBuffer->read(5);

        $this->assertSame(40, $bitBuffer->getPosition());

        $bitBuffer->write('qwerty', 6 * 8);

        $this->assertSame(40 + 6 * 8, $bitBuffer->getPosition());
    }

    public function testReadWrite()
    {
        $bitBuffer = new BitBuffer('');
        $bitBuffer
            ->write("\xF8", 8)
            ->write("\xA5\xB1", 16)
            ->write("\x04", 4)
            ->write("\x02", 4)
            ->write("\x33", 6)
            ->write("\x08", 5)
            ->write("\x01", 5)
            ->write("\x0E\x74", 14, Endian::ENDIAN_BIG)
            ->write("\x12\x30", 14, Endian::ENDIAN_LITTLE)
            ->write("\x01", 1)
            ->write("\x00", 1)
            ->write("\x01", 1)
            ->write("\x00", 1);

        $bitBuffer->setPosition(0);

        $read = $bitBuffer->read(80);

        $message = 'expect: ' . self::readable($this->source) . PHP_EOL .
                   'actual: ' . self::readable($read);

        $this->assertSame($this->source, $read, $message);
    }

    public static function readable($string)
    {
        $return = [];
        foreach (str_split($string) as $byte) {
            $return[] = str_pad(base_convert(ord($byte), 10, 2), 8, '0', STR_PAD_LEFT);
        }

        return implode(' ', $return);
    }

} 