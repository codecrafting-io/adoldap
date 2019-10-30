<?php

namespace CodeCrafting\AdoLDAP\Parsers\Types;

/**
 * Class FileTimeParser.
 *
 * Parse the ADO Filetime type to native PHP DateTime
 */
class FiletimeParser extends TypeParser
{
    /**
     * ADO compatibile types
     */
    const ADO_TYPES = [VT_DISPATCH];

    /**
     * @inheritDoc
     */
    public function getADOTypes()
    {
        return self::ADO_TYPES;
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return parent::FILETIME;
    }

    /**
     * @inheritDoc
     */
    public function parse($value)
    {
        if ($value) {
            $high = $value->HighPart;
            $low = $value->LowPart;
            if ($high > 0) {
                if ($low < 0) {
                    $high += 1;
                }
                $windowsTimestamp = ($high << 32) + $low;
                $unixTimestamp = intval($windowsTimestamp / 10000000) + -11644473600;
                $dt = new \DateTime();
                $dt->setTimestamp($unixTimestamp);
                return $dt;
            } elseif($high == $low && $high == 0) {
                $dt = new \DateTime();
                $dt->setTimestamp(-11644473600);
                $dt;
            }
        }

        return 0;
    }
}
