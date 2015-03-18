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
                    'm' => 'C',
                    'l' => 'C',
                    'b' => 'C'
                ],
                16 => [
                    'm' => 'S',
                    'l' => 'v',
                    'b' => 'n'
                ],
                32 => [
                    'm' => 'L',
                    'l' => 'V',
                    'b' => 'N'
                ],
                64 => [
                    'm' => 'Q',
                    'l' => 'P',
                    'b' => 'J'
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

    /**
     * @param string $format
     * @param string $data
     * @return int|string
     */
    public static function unpack($format, $data)
    {
        list(, $result) = unpack($format, $data);

        return $result;
    }

    /**
     * @param string $format
     * @param string $data
     * @return int|string
     */
    public static function pack($format, $data)
    {
        return pack($format, $data);
    }

    public static function getFormat($type, $length, $signed = false, $endian = 'm')
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

        throw new \InvalidArgumentException("Can't find format.");
    }
}