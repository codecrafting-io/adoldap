<?php

namespace CodeCrafting\AdoLDAP\Parser;

/**
 * Class BinaryParser.
 *
 * Parse the ADO OctetString Array to native PHP base64 encoded string
 */
class BinaryParser extends ParserInterface
{
    /**
     * ADO compatibile types
     */
    const ADO_TYPES = [204];

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
        return parent::BINARY;
    }

    /**
     * @inheritDoc
     */
    public function parse($value)
    {
        if ($value) {
            $strBin = '';
            foreach ($value as $key => $value2) {
                $strBin .= pack('c', $value2);
            }

            return base64_encode($strBin);
        }

        return null;
    }
}
