<?php

namespace CodeCrafting\AdoLDAP\Parsers\Types;

/**
 * Class ObjectParser.
 *
 * Parse the ADO Filetime type to native PHP DateTime
 */
class ObjectParser extends TypeParser
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
            if (isset($value->HighPart)) {
                $dateParser = TypeParser::getTypeParser(TypeParser::DATE);

                return $dateParser->parseFiletime($value);
            } elseif (isset($value->Control)) {
                return [
                    'control' => $value->Control,
                    'group' => $value->Group,
                    'owner' => $value->Owner,
                    'revision' => $value->Revision
                ];
            }

            return $value;
        }

        return 0;
    }
}
