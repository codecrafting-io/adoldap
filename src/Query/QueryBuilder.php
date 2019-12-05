<?php

namespace CodeCrafting\AdoLDAP\Query;

use InvalidArgumentException;
use CodeCrafting\AdoLDAP\Dialects\SQLDialect;
use CodeCrafting\AdoLDAP\Parsers\ParserInterface;
use CodeCrafting\AdoLDAP\Dialects\DialectInterface;
use CodeCrafting\AdoLDAP\Connections\LDAPConnection;
use CodeCrafting\AdoLDAP\Connections\AdodbConnection;
use CodeCrafting\AdoLDAP\Models\EntryNotFoundException;
use CodeCrafting\AdoLDAP\Models\Attributes\DistinguishedName;

/**
 * QueryBuilder class
 */
class QueryBuilder
{
    /**
     * LDAP Connection
     *
     * @var LDAPConnection
     */
    protected $connection;

    /**
     * Search dialect
     *
     * @var DialectInterface
     */
    protected $dialect;

    /**
     * ResultSet parser
     *
     * @var ParserInterface
     */
    protected $parser;

    /**
     * Scope of the search within it's search dialect.
     *
     * @var int
     */
    protected $searchScope = AdodbConnection::ADS_SCOPE_SUBTREE;

    /**
     * Query attributes
     *
     * @var array
     */
    protected $attributes = ['*'];

    /**
     * Query binding parameter delimiter
     *
     * @var string
     */
    protected $bindingDelimeter = ':';

    /**
     * Query binding parameters
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * QUery filters
     *
     * @var array
     */
    protected $filters = [];

    const AND_FILTER = 'AND';
    const OR_FILTER = 'OR';

    /**
     * Constructor
     *
     * @param LDAPConnection $connection
     * @param DialectInterface $dialect
     * @param ParserInterface $parser
     */
    public function __construct(LDAPConnection $connection, DialectInterface $dialect, ParserInterface $parser)
    {
        $this->connection = $connection;
        $this->dialect = $dialect;
        $this->parser = $parser;
    }

    /**
     * Get scope of the search within it's search dialect.
     *
     * @return  int
     */
    public function getSearchScope()
    {
        return $this->searchScope;
    }

    /**
     * Set scope of the search within it's search dialect.
     *
     * @param  int  $searchScope  Scope of the search within it's search dialect.
     * @return  self
     */
    public function setSearchScope(int $searchScope)
    {
        $this->searchScope = $searchScope;

        return $this;
    }

    /**
     * Get binding parameters.
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Get baseDn for this search query
     *
     * @return string
     */
    public function getDn()
    {
        return $this->dialect->getBaseDn();
    }

    /**
     * Change baseDn for this search query
     *
     * @param DistinguishedName|string $dn
     * @return self
     */
    public function setDn($dn)
    {
        if ($dn) {
            if (is_object($dn) && $dn instanceof DistinguishedName) {
                $this->dialect->setBaseDn($dn->getPath());
            } elseif (is_string($dn)) {
                $this->dialect->setBaseDn($dn);
            } else {
                throw new InvalidArgumentException('dn must be a string or a instance of' . DistinguishedName::class);
            }
        }

        return $this;
    }

    /**
     * Alias for setting baseDn
     *
     * @see QueryBuilder::setDn()
     * @param DistinguishedName|string $dn
     * @return self
     */
    public function from($dn)
    {
        return $this->setDn($dn);
    }

    /**
     * Adds attributes to query on the current LDAP connection.
     *
     * @param array|string $attributes
     * @return self
     */
    public function select($attributes = [])
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();
        if (!empty($attributes)) {
            $this->attributes = $attributes;
        }

        return $this;
    }

    /**
     * Returns the current selected fields to retrieve.
     *
     * @return array
     */
    public function getSelects()
    {
        $selects = $this->attributes;

        // If the asterisk is not provided in the selected columns, we need to
        // ensure we always select the object class, as these
        // are used for constructing models. The asterisk indicates that
        // we want all attributes returned for LDAP records.
        if (! in_array('*', $selects)) {
            $selects[] = 'objectclass';
        }

        //Return unique and escaped attributes for selection
        return array_map(function ($attribute) {
            return $this->dialect->escapeIdentifier($attribute);
        }, array_unique($selects));
    }

    /**
     * Compiles and returns the current query string
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->dialect->compileCommand($this);
    }

    /**
     * Performs the specified query on the current LDAP connection.
     *
     * @param string $query
     * @return ResultSetIterator
     */
    public function query(string $query)
    {
        $result = new ResultSetIterator($this->connection->search($query, $this->getSearchProperties()), $this->parser);
        if (in_array('*', $this->attributes)) {
            return $this->resolveAdsPath($result);
        }

        return $result;
    }

    /**
     * Performs and get the ResultSet of the current query
     *
     * @return ResultSetIterator
     */
    public function get()
    {
        return $this->query($this->getQuery());
    }

    /**
     * Get first entry in a search result
     *
     * @param array|string $attributes
     * @return Entry|null
     */
    public function first($attributes = [])
    {
        $results = $this->select($attributes)->get();

        return $results->current();
    }

    /**
     * Returns the first entry in a search result.
     *
     * If no entry is found, an exception is thrown.
     *
     * @param array|string $attributes
     * @throws EntryNotFoundException
     * @return Entry
     */
    public function firstOrFail($attributes = [])
    {
        $entry = $this->first($attributes);
        if (! $entry) {
            throw (new EntryNotFoundException())->setQuery($this->getQuery());
        }

        return $entry;
    }

    /**
     * Finds records by the specified attribute and value.
     *
     * @param string       $attribute
     * @param string       $value
     * @param array|string $attributes
     * @return ResultSetIterator
     */
    public function findBy($attribute, $value, $attributes = [])
    {
        return $this->whereEquals($attribute, $value)->select($attributes)->get();
    }

    /**
     * Finds the first record by the specified attribute and value.
     *
     * @param string       $attribute
     * @param string       $value
     * @param array|string $attributes
     * @return Entry|null
     */
    public function firstBy($attribute, $value, $attributes = [])
    {
        try {
            return $this->firstByOrFail($attribute, $value, $attributes);
        } catch (EntryNotFoundException $e) {
            return null;
        }
    }

    /**
     * Finds the first record by the specified attribute and value.
     *
     * If no record is found an exception is thrown.
     *
     * @param string       $attribute
     * @param string       $value
     * @param array|string $attributes
     * @throws EntryNotFoundException
     * @return Entry
     */
    public function firstByOrFail($attribute, $value, $attributes = [])
    {
        return $this->whereEquals($attribute, $value)->firstOrFail($attributes);
    }

    /**
     * Get query provided filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Clear the query builder filters.
     *
     * @return self
     */
    public function clearFilters()
    {
        $this->filter = [];

        return $this;
    }

    /**
     * Adds a clause to the current query
     *
     * @param string $expression The expression to be filter
     * @param string $type The filter type, must be 'AND' or 'OR'
     * @param array $bindings The optional binding parameter values
     * @return self
     */
    public function where(string $expression, string $type = self::AND_FILTER, array $bindings = [])
    {
        if (! in_array($type, [self::AND_FILTER, self::OR_FILTER])) {
            throw new InvalidArgumentException("Invalid filter type: {$type}.");
        }
        $this->bindings = array_merge($this->bindings, $bindings);
        $this->filters[] = ['type' => $type, 'expression' => $expression];

        return $this;
    }

    /**
     * Adds a AND WHERE clause. The 'AND' operator will be ignored if this is the first filter
     *
     * @param string $expression The expression to be filter
     * @param array $bindings The optional binding parameter values
     * @return self
     */
    public function andWhere(string $expression, array $bindings = [])
    {
        return $this->where($expression, self::AND_FILTER, $bindings);
    }

    /**
     * Adds a OR WHERE clause. The 'OR' operator will be ignored if this is the first filter
     *
     * @param string $expression The expression to be filter
     * @param array $bindings The optional binding parameter values
     * @return self
     */
    public function orWhere(string $expression, array $bindings = [])
    {
        return $this->where($expression, self::OR_FILTER, $bindings);
    }

    /**
     * Adds a open delemiter for a expression filter evaluation.
     * @param string $type The filter type, must be 'AND' or 'OR'
     * @return self
     */
    public function openWhereGroup(string $type = self::AND_FILTER)
    {
        //For now the builder does not support nested expressions
        if ($this->isSQLDialect()) {
            $this->where('(', $type);
        }

        return $this;
    }

    /**
     * Adds a close delemiter for a WHERE expression filter evaluation. This only is valid for SQL queries
     *
     * @return self
     */
    public function closeWhereGroup()
    {
        //For now the builder does not support nested expressions
        if ($this->isSQLDialect()) {
            $this->filters[] = ['type' => null, 'expression' => ')'];
        }

        return $this;
    }

    /**
     * Adds a 'where equals' clause to the current query
     *
     * @param string $field
     * @param string $value
     * @param string $type
     * @return self
     */
    public function whereEquals(string $expression, $value, string $type = self::AND_FILTER)
    {
        return $this->where($expression . '=' . $this->dialect->escapeValue($value), $type);
    }

    /**
     * Adds a 'where not equals' clause to the current query
     *
     * @param string $field
     * @param string $value
     * @param string $type
     * @return self
     */
    public function whereNotEquals(string $expression, $value, string $type = self::AND_FILTER)
    {
        return $this->where($expression . '!=' . $this->dialect->escapeValue($value), $type);
    }

    /**
     * Adds a clause for query values in a group of values.
     *
     * @param string $expression
     * @param array $values
     * @param string $type
     * @return self
     */
    public function whereIn(string $expression, array $values, string $type = self::AND_FILTER)
    {
        $this->openWhereGroup($type);
        foreach ($values as $value) {
            $this->whereEquals($expression, $value, self::OR_FILTER);
        }

        return $this->closeWhereGroup();
    }

    /**
     * Add a betwwen clause to the current query
     *
     * @param string $expression The expression value
     * @param mixed $min The lower delimiter of the interval
     * @param mixed $max The higher delimiter of the interval
     * @param string $type The filter type, must be 'AND' or 'OR'
     * @return self
     */
    public function whereBetween(string $expression, $min, $max, $type = self::AND_FILTER)
    {
        if ($this->isSQLDialect()) {
            return $this->where($expression . " BETWEEN {$this->dialect->escapeValue($min)} AND {$this->dialect->escapeValue($max)}", $type);
        } else {
            return $this->openWhereGroup($type)
                ->where($expression . '>=' . $this->dialect->escapeValue($min))
                ->where($expression . '<=' . $this->dialect->escapeValue($max))
                ->closeWhereGroup();
        }
    }

    /**
     * Adds a 'contains like clause' to the current query
     *
     * @param string $expression The expression value
     * @param mixed $value The value to be compared
     * @param string $type The filter type, must be 'AND' or 'OR'
     * @return self
     */
    public function whereContains(string $expression, $value, string $type = self::AND_FILTER)
    {
        return $this->where($expression . '=' . "{$this->dialect->escapeValue('*' . $value . '*')}", $type);
    }

    /**
     * Adds a 'contains like clause' to the current query
     *
     * @param string $expression The expression value
     * @param mixed $value The value to be compared
     * @param string $type The filter type, must be 'AND' or 'OR'
     * @return self
     */
    public function whereNotContains(string $expression, $value, string $type = self::AND_FILTER)
    {
        return $this->where($expression . '!=' . "{$this->dialect->escapeValue('*' . $value . '*')}", $type);
    }

    /**
     * Adds a 'starts with clause' to the current query
     *
     * @param string $expression The expression value
     * @param mixed $value The value to be compared
     * @param string $type The filter type, must be 'AND' or 'OR'
     * @return self
     */
    public function whereStartsWith(string $expression, $value, string $type = self::AND_FILTER)
    {
        return $this->where($expression . '=' . "{$this->dialect->escapeValue($value . '*')}", $type);
    }

    /**
     * Adds a 'not starts with clause' to the current query
     *
     * @param string $expression The expression value
     * @param mixed $value The value to be compared
     * @param string $type The filter type, must be 'AND' or 'OR'
     * @return self
     */
    public function whereNotStartsWith(string $expression, $value, string $type = self::AND_FILTER)
    {
        return $this->where($expression . '=' . "{$this->dialect->escapeValue($value . '*')}", $type);
    }

    /**
     * Adds a 'ends with clause' to the current query
     *
     * @param string $expression The expression value
     * @param mixed $value The value to be compared
     * @param string $type The filter type, must be 'AND' or 'OR'
     * @return self
     */
    public function whereEndsWith(string $expression, $value, string $type = self::AND_FILTER)
    {
        return $this->where($expression . '=' . "{$this->dialect->escapeValue('*' . $value)}", $type);
    }

    /**
     * Adds a 'not ends with clause' to the current query
     *
     * @param string $expression The expression value
     * @param mixed $value The value to be compared
     * @param string $type The filter type, must be 'AND' or 'OR'
     * @return self
     */
    public function whereNotEndsWith(string $expression, $value, string $type = self::AND_FILTER)
    {
        return $this->where($expression . '!=' . "{$this->dialect->escapeValue('*' . $value)}", $type);
    }


    /**
     * Adds a 'member of' filter to the current query.
     *
     * @param DistinguishedName|string $dn
     * @return self
     */
    public function whereMemberOf($dn)
    {
        $dnPath = (is_object($dn) && $dn instanceof DistinguishedName) ? $dn->getPath() : $dn;

        return $this->whereEquals('memberOf', $dnPath);
    }

    /**
     * Check if whether or not the current dialect is instance of SQLDialect
     *
     * @return bool
     */
    public function isSQLDialect()
    {
        return ($this->dialect instanceof SQLDialect);
    }

    /**
     * Resolve adspath to parse all attributes
     *
     * @param ResultSetIterator $result
     * @return ResultSetIterator
     */
    private function resolveAdsPath(ResultSetIterator $result)
    {
        return $result->afterFetch(function ($entry) {
            if ($entry->isAdPathOnly()) {
                $obj = $this->connection->getLdapObject($entry->adspath);
                return $this->parser->parseEntry($obj);
            }
        });
    }

    /**
     * Get LDAP search properties
     *
     * @return array
     */
    private function getSearchProperties()
    {
        $properties['SearchScope'] = $this->searchScope;
        /*
        Not compatibile with ADO DB
        if ($this->limit > 0) {
            //$properties['MaxRecords'] = $this->limit;
        }
        */

        return $properties;
    }
}
