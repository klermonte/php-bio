<?php
namespace PhpBio;

use PHPUnit_Framework_TestCase;

class BitBufferTest extends PHPUnit_Framework_TestCase
{

    public $source;

    public function setUp()
    {
        $this->source = "\xF8" . // 1111 1000
                        "\xA5" . // 1010 0101
                        "\xB1" . // 1011 0001
                        "\x42" . // 0100 0010
                        "\xCD" . // 1100 1101
                        "\x01" . // 0000 0001
                        "\x39" . // 0011 1001
                        "\xD0" . // 1101 0000
                        "\x4B" . // 0100 1011
                        "\x0A";  // 0000 1010
    }

    public function values()
    {
        $cases = [
            // within one entire byte
            [0, 8, 0, Endian::ENDIAN_LITTLE, 0xF8],
            [0, 7, 0, Endian::ENDIAN_LITTLE, 0b1111100],
            [0, 3, 0, Endian::ENDIAN_LITTLE, 0b111],
            [1, 7, 0, Endian::ENDIAN_LITTLE, 0b1111000],
            [5, 3, 0, Endian::ENDIAN_LITTLE, 0b000],
            [2, 4, 0, Endian::ENDIAN_LITTLE, 0b1110],
            [7, 1, 0, Endian::ENDIAN_LITTLE, 0b0],
            [4, 1, 0, Endian::ENDIAN_LITTLE, 0b1],

            // less or equal one byte across 2 bytes
            [1, 8, 0, Endian::ENDIAN_LITTLE, 0b11110001],
            [4, 8, 0, Endian::ENDIAN_LITTLE, 0x8A],
            [4, 6, 0, Endian::ENDIAN_LITTLE, 0b00100010],

            // from 1 to 2 bytes across
            [4, 16, 0, Endian::ENDIAN_LITTLE, 0x5B8A],
            [4, 16, 0, Endian::ENDIAN_BIG   , 0x8A5B],
            [14, 16, 0, Endian::ENDIAN_LITTLE, 0b0101000001101100],
            [14, 16, 0, Endian::ENDIAN_BIG   , 0b0110110001010000],
            [10, 10, 0, Endian::ENDIAN_LITTLE, 0b0000001110010110],
            [10, 10, 0, Endian::ENDIAN_BIG   , 0b0000001001011011],
            [16, 10, 0, Endian::ENDIAN_LITTLE, 0b0000000110110001],
            [16, 10, 0, Endian::ENDIAN_BIG   , 0b0000001011000101],
            [23, 15, 0, Endian::ENDIAN_LITTLE, 0b0011001110100001],
            [23, 15, 0, Endian::ENDIAN_BIG   , 0b0101000010110011],
            [0,  10, 0, Endian::ENDIAN_LITTLE, 0b0000001011111000],
            [0,  10, 0, Endian::ENDIAN_BIG   , 0b0000001111100010],

            // 2 entire bytes
            [ 0, 16, 0, null,               0xA5F8],
            [ 0, 16, 0, Endian::ENDIAN_BIG, 0xF8A5],
            [16, 16, 0, null,               0x42B1],
            [16, 16, 0, Endian::ENDIAN_BIG, 0xB142],

            // from 2 to 3 bytes across
            [20, 20, 0, null,               0x0D2C14],
            [20, 20, 0, Endian::ENDIAN_BIG, 0x0142CD],

            // end of file
            [60, 20, 0, null,               0x0AB004],
            [60, 20, 0, Endian::ENDIAN_BIG, 0x004B0A],

        ];

        if (PHP_INT_SIZE == 8) {
            $cases = array_merge($cases, [
                [ 0, 60, 0, Endian::ENDIAN_BIG, 0x0F8A5B142CD0139D],
                [ 0, 60, 0, null,               0x0D3901CD42B1A5F8],
                [20, 40, 0, Endian::ENDIAN_BIG, 0x000000142CD0139D],
                [20, 40, 0, null,               0x0000009D13D02C14],
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

} 