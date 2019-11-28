<?php

namespace CodeCrafting\AdoLDAP\Parsers;

use CodeCrafting\AdoLDAP\Models\Entry;

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
    public function parseField($field);

    /**
     * Parse a resultset entry
     *
     * @param \VARIANT $entry
     * @return Entry
     */
    public function parseEntry($entry);
}
