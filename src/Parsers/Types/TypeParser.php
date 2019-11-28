<?php

namespace CodeCrafting\AdoLDAP\Parsers\Types;

/**
 * Class TypeParser.
 */
abstract class TypeParser
{
    const INT = 0;
    const STRING = 1;
    const OBJECT = 2;
    const BINARY = 3;
    const DATE = 4;
    const BOOLEAN = 5;

    /**
     * Available TypeParsers
     *
     * @var TypeParser[]
     */
    private static $parsers = [];

    /**
     * Get the compatible ADO data types
     *
     * @return array
     */
    abstract public function getADOTypes();

    /**
     * Get the Parser type
     *
     * @return int
     */
    abstract public function getType();

    /**
     * Parse value to native PHP value
     *
     * @param mixed $value
     * @return mixed
     */
    abstract public function parse($value);

    /**
     * Check if the provided ADO type is compatible if the parser type
     *
     * @param int $type the ADO Variant type
     * @return bool
     */
    public function isType($type)
    {
        if ($type !== null) {
            return in_array($type, $this->getADOTypes());
        }

        return false;
    }

    /**
     * Dynamically get the available parsers
     *
     * @return Parser[]
     */
    public static function getParsers()
    {
        if (! self::$parsers) {
            $oClass = new \ReflectionClass(__CLASS__);
            $constants = $oClass->getConstants();
            foreach ($constants as $key => $value) {
                $className = __NAMESPACE__ . '\\' . str_replace('_', '', ucwords(strtolower($key), '_')) . 'Parser';
                self::$parsers[$value] = new $className();
            }
        }


        return self::$parsers;
    }

    /**
     * Get the parser by a Parser constant ID
     *
     * @param integer $typeParserId
     * @return TypeParser
     */
    public static function getTypeParser(int $typeParserId)
    {
        return self::getParsers()[$typeParserId];
    }
}
