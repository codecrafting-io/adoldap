<?php

namespace CodeCrafting\AdoLDAP\Models;

use CodeCrafting\AdoLDAP\AdoLDAPException;

/**
 * Class EntryNotFoundException.
 *
 * Thrown if the entry is not found on a LDAP search
 */
class EntryNotFoundException extends AdoLDAPException
{
    /**
     * The query filter that was used.
     *
     * @var string
     */
    protected $query;

    /**
     * Sets the query that was used.
     *
     * @param string $query
     * @return ModelNotFoundException
     */
    public function setQuery($query)
    {
        $this->query = $query;
        $this->message = "No LDAP query results the search: [{$query}]";

        return $this;
    }
}
