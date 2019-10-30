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
        return preg_replace_callback('/\\\([0-9A-Fa-f]{2})/', function ($matches) {
            return chr(hexdec($matches[1]));
        }, utf8_encode(strval($value)));
    }
}
