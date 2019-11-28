<?php

namespace CodeCrafting\AdoLDAP\Dialects;

use CodeCrafting\AdoLDAP\Query\QueryBuilder;

/**
 * ADSI Dialect interface for binding and searching
 */
abstract class DialectInterface
{
    /**
     * Standard LDAP protocol string
     */
    const PROTOCOL = 'LDAP://';

    /**
     * SSL LDAP protocol string
     */
    const SSL_PROTOCOL = 'LDAPS://';

    /**
     * Standard LDAP port
     */
    const PORT = 389;

    /**
     * SSL LDAP port
     */
    const SSL_PORT = 636;

    /**
     * Default DN as a the root directory data tree, to discover
     * the defaultNamingContext
     */
    const ROOT_DN = 'RootDSE';

    const MEMBEROF_OID = 'memberof:1.2.840.113556.1.4.1941:';

    /**
     * The LDAP connection host
     *
     * @var string|null
     */
    protected $host;

    /**
     * The LDAP connection host port
     *
     * @var int|null
     */
    protected $port;

    /**
     * The base distinguished name for the host
     *
     * @var string
     */
    protected $baseDn;

    /**
     * Whether the connection must be bound over SSL.
     *
     * @var bool
     */
    protected $ssl;

    /**
     * Get the LDAP connection host
     *
     * @return  string|null
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the LDAP connection host
     *
     * @param  string|null  $host  The LDAP connection host
     *
     * @return  self
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get the LDAP connection host port
     *
     * @return  int|null
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set the LDAP connection host port
     *
     * @param  int|null  $port  The LDAP connection host port
     *
     * @return  self
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get the base distinguished name for the host
     *
     * @return  string
     */
    public function getBaseDn()
    {
        return $this->baseDn;
    }

    /**
     * Set the base distinguished name for the host
     *
     * @param  string  $baseDn  The base distinguished name for the host
     *
     * @return  self
     */
    public function setBaseDn(string $baseDn)
    {
        $this->baseDn = $baseDn;

        return $this;
    }

    /**
     * Check if whether or not the BaseDN is set to RootDN
     *
     * @return bool
     */
    public function isRootDn()
    {
        return ($this->baseDn === self::ROOT_DN) ? true : false;
    }

    /**
     * Get whether the connection must be bound over SSL.
     *
     * @return  bool
     */
    public function isSsl()
    {
        return $this->ssl;
    }

    /**
     * Set whether the connection must be bound over SSL.
     *
     * @param  bool  $ssl  Whether the connection must be bound over SSL.
     *
     * @return  self
     */
    public function setSsl(bool $ssl)
    {
        $this->ssl = $ssl;

        return $this;
    }

    public function getMemberOfOID()
    {
        return self::MEMBEROF_OID;
    }

    /**
     * Get Command query key order
     *
     * @return array
     */
    abstract public function getCommandOrder();

    /**
     * Get Command query key separator
     *
     * @return string
     */
    abstract public function getCommandSeparator();

    /**
     * Compile query command filters
     *
     * @param array $filters
     * @return string
     */
    abstract public function compileFilters(array $filters);

    /**
     * Compile query command attributes
     *
     * @param array $attributes
     * @return string
     */
    abstract public function compileSelect(array $attributes);

    /**
     * Compile query command from
     *
     * @return string
     */
    abstract public function compileFrom();

    /**
     * Escape a string for use in an LDAP filter or DN
     *
     * @param string $value
     * @param int|null $flag
     * @return string
     */
    abstract public function escapeValue($value, $flag = null);

    /**
     * Escape a LDAP query identifier string
     *
     * @param string $identifier
     * @return string
     */
    abstract public function escapeIdentifier($identifier);

    /**
     * Get command compiled key parts
     *
     * @param QueryBuilder $builder
     * @return array
     */
    protected function getCommand(QueryBuilder $builder)
    {
        return [
            'SELECT'    => $this->compileSelect($builder->getSelects()),
            'FROM'      => $this->compileFrom(),
            'FILTERS'   => $this->compileFilters($builder->getFilters())
        ];
    }

    /**
     * Compiles the Builder instance into an LDAP query string command to be within a AdodbConnection.
     *
     * @param QueryBuilder $filter the filter conditions within the dialect
     * @return string
     */
    public function compileCommand(QueryBuilder $builder)
    {
        $command = $this->getCommand($builder);
        return implode($this->getCommandSeparator(), array_map(function ($key) use ($command) {
            if (array_key_exists($key, $command)) {
                return $command[$key];
            } else {
                throw new DialectException("Command key '{$key}' does not exists");
            }
        }, $this->getCommandOrder()));
    }
}
