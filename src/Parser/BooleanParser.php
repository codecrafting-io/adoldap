<?php

namespace CodeCrafting\AdoLDAP\Parser;

/**
 * Class BooleanParser.
 *
 * Parse the ADO boolean type to native PHP boolean
 */
class BooleanParser extends ParserInterface
{
    /**
     * ADO compatibile types
     */
    const ADO_TYPES = [11];

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
        return parent::BOOLEAN;
    }

    /**
     * @inheritDoc
     */
    public function parse($value)
    {
        return boolval($value);
    }
}
