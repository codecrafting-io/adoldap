<?php

namespace CodeCrafting\AdoLDAP\Query;

use CodeCrafting\AdoLDAP\Parsers\ParserInterface;
use CodeCrafting\AdoLDAP\Dialects\DialectInterface;
use CodeCrafting\AdoLDAP\Connections\LDAPConnection;

/**
 * Class SearchFactory
 *
 * Creates new LDAP queries
 */
class SearchFactory
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

    public function __construct(LDAPConnection $connection, DialectInteface $dialect, ParserInterface $parser)
    {
        $this->setConnection($connection)->setDialect($dialect)->setParser($parser);
    }

    /**
     * Set lDAP Connection
     *
     * @param  LDAPConnection  $connection  LDAP Connection
     * @return  self
     */
    public function setConnection(LDAPConnection $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set search dialect
     *
     * @param  DialectInterface  $dialect  Search dialect
     * @return  self
     */
    public function setDialect(DialectInteface $dialect)
    {
        $this->dialect = $dialect;

        return $this;
    }

    /**
     * Set resultSet parser
     *
     * @param  ParserInterface  $parser  ResultSet parser
     * @return  self
     */
    public function setParser(ParserInterface $parser)
    {
        $this->parser = $parser;

        return $this;
    }
}
