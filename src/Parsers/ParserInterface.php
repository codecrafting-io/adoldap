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
     * @return void
     */
    public function parse($field);
}
