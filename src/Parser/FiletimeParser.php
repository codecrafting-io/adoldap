<?php

namespace CodeCrafting\AdoLDAP\Parser;

/**
 * Class FileTimeParser.
 *
 * Parse the ADO Filetime type to native PHP DateTime
 */
class FiletimeParser extends ParserInterface
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
                $secsAfterADEpoch = ($high * pow(2, 32) + $low) / pow(10, 7);
                $adToUnixConverter = ((1970 - 1601) * 365 - 3 + round((1970 - 1601) / 4)) * 86400;
                $unixTimestamp = intval($secsAfterADEpoch - $adToUnixConverter);
                $dt = new \DateTime();
                $dt->setTimestamp($unixTimestamp);

                return $dt;
            }
        }

        return 0;
    }
}
