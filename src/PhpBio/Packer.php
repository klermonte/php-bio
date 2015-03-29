<?php

namespace PhpBio;


class Packer
{
    private static $lenthMap = [
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

    private static $formatMap = [
        'int' => [
            'u' => [
                8 => [
                    Endian::ENDIAN_LITTLE  => 'C',
                    Endian::ENDIAN_BIG     => 'C'
                ],
                16 => [
                    Endian::ENDIAN_LITTLE  => 'v',
                    Endian::ENDIAN_BIG     => 'n'
                ],
                32 => [
                    Endian::ENDIAN_LITTLE  => 'V',
                    Endian::ENDIAN_BIG     => 'N'
                ],
                64 => [
                    Endian::ENDIAN_LITTLE  => 'P',
                    Endian::ENDIAN_BIG     => 'J'
                ]
            ],
            's' => [
                8  => 'c',
                16 => 's',
                32 => 'l',
                64 => 'q'
            ]
        ]
    ];

    private static $fallBackFormats = [
        'P' => 'V2',
        'J' => 'N2',
    ];

    /**
     * @param string $format
     * @param string $data
     * @return int|string
     */
    public static function unpack($format, $data)
    {
        if (isset(self::$fallBackFormats[$format]) && !version_compare(phpversion(), '5.6.3', '>=')) {
            $result = unpack(self::$fallBackFormats[$format], $data);
            if ($format == 'P') {
                // LE
                $result = $result[2] * 0x100000000 + $result[1];
            } else {
                // BE
                $result = $result[1] * 0x100000000 + $result[2];
            }
        } else {
            list(, $result) = unpack($format, $data);
        }

        return $result;
    }

    /**
     * @param string $format
     * @param string $data
     * @return string
     */
    public static function pack($format, $data)
    {
        return pack($format, $data);
    }

    public static function getFormat($type, $length, $signed = false, $endian)
    {
        $sign = $signed ? 's' : 'u';
        if (isset(self::$formatMap[$type][$sign][$length])) {
            $map = self::$formatMap[$type][$sign][$length];
            if ($signed) {
                return $map;
            }

            if (isset($map[$endian])) {
                return $map[$endian];
            }
        }

        throw new \InvalidArgumentException("Packer can't find format.");
    }
}