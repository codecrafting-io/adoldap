<?php

namespace CodeCrafting\AdoLDAP\Parsers\Types;

/**
 * Class DateParser.
 *
 * Parse the ADO DATE types to native PHP DateTime
 */
class DateParser extends TypeParser
{
    /**
     * ADO compatibile types
     */
    const ADO_TYPES = [7, 5];

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
        return parent::DATE;
    }

    /**
     * @inheritDoc
     */
    public function parse($value)
    {
        if ($value) {
            $timestamp = variant_date_to_timestamp($value);
            $dt = new \DateTime();
            $dt->setTimestamp($timestamp);

            return $dt;
        }

        return 0;
    }
}
