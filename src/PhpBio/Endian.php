<?php

namespace PhpBio;


class Endian
{
    const ENDIAN_LITTLE  = 1;
    const ENDIAN_BIG     = 2;

    /**
     * @var int Is machine order Big or Little endian.
     */
    private static $machineEndian;

    /**
     * @return int
     */
    public static function getMachineEndian()
    {
        if (!self::$machineEndian) {
            $testInt = 0x00FF;
            $p = pack('S', $testInt);
            self::$machineEndian = $testInt === current(unpack('v', $p)) ? self::ENDIAN_LITTLE : self::ENDIAN_BIG;
        }

        return self::$machineEndian;
    }

} 