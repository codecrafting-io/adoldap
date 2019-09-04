<?php

namespace CodeCrafting\AdoLDAP\Parsers\Types;

/**
 * Class IntParser.
 *
 * Parse the ADO integer types to native PHP int
 */
class IntParser extends TypeParser
{
    /**
     * ADO compatibile types
     */
    const ADO_TYPES = [\VT_I4, 20];

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
        return parent::INT;
    }

    /**
     * @inheritDoc
     */
    public function parse($value)
    {
        return intval($value);
    }
}
