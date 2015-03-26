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
        return [
            // from 1 to 2 bytes across
            [4, 16, 0, 'l', 0x5B8A],
            [4, 16, 0, 'b', 0x8A5B],
            [14, 16, 0, 'l', 0b0101000001101100],
            [14, 16, 0, 'b', 0b0110110001010000],
            [10, 10, 0, 'l', 0b0000001110010110],
            [10, 10, 0, 'b', 0b0000001001011011],

            [0,  10, 0, 'l', 0b0000001011111000],
            [0,  10, 0, 'b', 0b0000001111100010],


//            [20, 12, 0, 'l', 0x],

            // within one entire byte
            [0, 8, 0, 'l', 0xF8],
            [0, 7, 0, 'l', 0b1111100],
            [0, 3, 0, 'l', 0b111],
            [1, 7, 0, 'l', 0b1111000],
            [5, 3, 0, 'l', 0b000],
            [2, 4, 0, 'l', 0b1110],

            // less or equal one byte across 2 bytes
            [1, 8, 0, 'l', 0b11110001],
            [4, 8, 0, 'l', 0x8A],
            [4, 6, 0, 'l', 0b00100010],

            // 2 entire bytes
            [0, 16, 0, 'l', 0xA5F8],
            [0, 16, 0, 'b', 0xF8A5],
            [16, 16, 0, 'l', 0x42B1],
            [16, 16, 0, 'b', 0xB142],


        ];
    }

    /**
     * @dataProvider values
     */
    public function testReadInt($offset, $bitCount, $signed, $endian, $result)
    {
        $bitBuffer = new BitBuffer($this->source);

        if ($offset) {
            $bitBuffer->readInt($offset);
        }

        $this->assertSame($result, $bitBuffer->readInt($bitCount, $signed, $endian));
    }

} 