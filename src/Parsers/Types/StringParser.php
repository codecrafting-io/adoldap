<?php

namespace CodeCrafting\AdoLDAP\Parsers\Types;

/**
 * Class StringParser.
 *
 * Parse the ADO string types to native PHP string
 */
class StringParser extends TypeParser
{
    /**
     * ADO compatibile types
     */
    const ADO_TYPES = [130, 200, 201, 202];

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
        return parent::STRING;
    }

    /**
     * @inheritDoc
     */
    public function parse($value)
    {
        return utf8_encode(strval($value));
    }
}
