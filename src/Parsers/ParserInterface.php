<?php

namespace CodeCrafting\AdoLDAP\Parsers;

/**
 * Interface ParserInterface.
 */
interface ParserInterface
{
    /**
     * Parse the field to PHP native value
     *
     * @param mixed $field
     * @param bool $containerNameOnly Only returns the name for container values
     * @return void
     */
    public function parse($field, bool $containerNameOnly);
}
