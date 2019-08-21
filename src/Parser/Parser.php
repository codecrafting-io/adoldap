<?php

namespace CodeCrafting\AdoLDAP\Parser;

/**
 * Interface Parser
 */
abstract class Parser
{
    const INT = 0;
    const STRING = 1;
    const FILETIME = 2;
    const BINARY = 3;
    const DATE = 4;
    const BOOLEAN = 5;

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
        $oClass = new \ReflectionClass(__CLASS__);
        $constants = $oClass->getConstants();
        $parsers = [];
        foreach ($constants as $key => $value) {
            $className = __NAMESPACE__ . '\\' . str_replace('_', '', ucwords(strtolower($key), '_')) . 'Parser';
            $parsers[$value] = new $className();
        }

        return $parsers;
    }
}
