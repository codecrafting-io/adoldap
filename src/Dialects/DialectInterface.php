<?php

namespace CodeCrafting\AdoLDAP\Dialects;

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

    /**
     * Get a Command string to be executed within a ADODBConnection
     *
     * @param mixed $filter the filter conditions within the dialect
     * @param string $context the command context withing the dialect
     * @return string
     */



    /**
     * Get a Command string to be executed within a ADODBConnection
     *
     * @param mixed $filter the filter conditions within the dialect
     * @param array|string $attributes
     * @param string $context the command context withing the dialect
     * @return string
     */
    abstract public function getCommand($filter, $attributes, string $context = null);
}
