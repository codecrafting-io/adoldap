<?php

namespace CodeCrafting\AdoLDAP\Models\Attributes;

/**
 * Class ObjectClass.
 */
class ObjectClass
{
    /**
     * Object class values
     *
     * @var array
     */
    protected $classes;

    protected $mostRelevant;

    public function __construct(array $classes)
    {
        $this->classes = ($classes !== null) ? array_map('strtolower', $classes) : [];
        $this->mostRelevant = end($this->classes);
    }

    /**
     * Check if the provided class is a
     *
     * @param string $className
     * @return boolean
     */
    public function is(string $className)
    {
        return (array_search(strtolower($className), $this->classes) !== false);
    }

    /**
     * Get object classes
     *
     * @return array
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * Get most relevant class value
     *
     * @return string
     */
    public function getMostRelevant()
    {
        return $this->mostRelevant;
    }
}
