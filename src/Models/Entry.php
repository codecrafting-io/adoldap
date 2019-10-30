<?php

namespace CodeCrafting\AdoLDAP\Models;

/**
 * Class Entry.
 */
class Entry
{
    /**
     * Entry attributes
     *
     * @var array
     */
    protected $attributes;

    public function __construct(array $attributes = [])
    {
        $this->attributes = [];
        $this->setAllAttributes($attributes);
    }

    /**
     * Dynamically retrieve attributes on the object.
     *
     * @param mixed $key
     * @return bool
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the object.
     *
     * @param mixed $key
     * @param mixed $value
     * @return $this
     */
    public function __set($key, $value)
    {
        return $this->setAttribute($key, $value);
    }

    /**
     * Returns the entry distinguished name
     *
     * @return string
     */
    public function __toString()
    {
        return $this->dn;
    }

    /**
     * Get entry attribute
     *
     * @param int|string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if ($this->hasAttribute($key)) {
            $key = $this->normalizeKey($key);

            return $this->attributes[$key];
        }
    }

    /**
     * Get all entry attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Sets the attribute
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setAttribute(string $key, $value)
    {
        $this->attributes[$this->normalizeKey($key)] = $this->normalizeValue($value);

        return $this;
    }

    /**
     * Sets all the attributes
     *
     * @param array $attributes
     * @return self
     */
    public function setAllAttributes(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Check whether or not the attribute exists
     *
     * @param string $key
     * @return bool
     */
    public function hasAttribute(string $key)
    {
        return array_key_exists($this->normalizeKey($key), $this->attributes);
    }

    /**
     * Check whether the key value exists and is empty
     *
     * @param string $key
     * @return bool
     */
    public function isEmpty(string $key)
    {
        if ($this->hasAttribute($key)) {
           return is_null($this->getAttribute($key));
        }

        return true;
    }

    /**
     * Normalize the name of the entry key
     *
     * @param string $key
     * @return string
     */
    protected function normalizeKey(string $key)
    {
        return strtolower($key);
    }

    /**
     * Normalize empty values
     *
     * @param mixed $value
     * @return mixed
     */
    private function normalizeValue($value)
    {
        return (is_string($value) && empty($value)) ? null : $value;
    }
}
