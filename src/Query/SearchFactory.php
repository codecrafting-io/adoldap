<?php

namespace CodeCrafting\AdoLDAP\Query;

use CodeCrafting\AdoLDAP\Models\User;
use CodeCrafting\AdoLDAP\Parsers\ParserInterface;
use CodeCrafting\AdoLDAP\Dialects\DialectInterface;
use CodeCrafting\AdoLDAP\Connections\LDAPConnection;
use CodeCrafting\AdoLDAP\Configuration\AdoLDAPConfiguration;
use CodeCrafting\AdoLDAP\Models\Computer;

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
     * @var AdoLDAPConfiguration
     */
    protected $configuration;

    /**
     * ResultSet parser
     *
     * @var ParserInterface
     */
    protected $parser;

    public function __construct(LDAPConnection $connection, AdoLDAPConfiguration $configuration, ParserInterface $parser)
    {
        $this->setConnection($connection)->setConfiguration($configuration)->setParser($parser);
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
     * @param  AdoLDAPConfiguration  $dialect  Search dialect
     * @return  self
     */
    public function setConfiguration(AdoLDAPConfiguration $configuration)
    {
        $this->configuration = $configuration;

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

    /**
     * Gets a new QueryBuilder
     *
     * @return QueryBuilder
     */
    public function newQuery()
    {
        return $this->newBuilder();
    }

    /**
     * Returns a query builder scoped to search users
     *
     * @return QueryBuilder
     */
    public function users()
    {
        return $this->category(User::objectClass()->getMostRelevant());
    }

    /**
     * Returns a computer by name
     *
     * @param string $accountName
     * @param array $attributes
     * @return ResultSetIterator
     */
    public function computer($name, $attributes = [])
    {
        $attributes = ($attributes) ? $attributes : Computer::DEFAULT_ATTRIBUTES;
        return $this->computers()->findBy('CN', $name, $attributes);
    }

    /**
     * Returns a query builder scoped to search computers
     *
     * @return QueryBuilder
     */
    public function computers()
    {
        return $this->category(Computer::objectClass()->getMostRelevant());
    }

    /**
     * Returns a query builder scoped to search by category/objectclass
     *
     * @param string $category
     * @return QueryBuilder
     */
    public function category($category)
    {
        return $this->newQuery()->whereEquals('objectCategory', $category);
    }

    /**
     * Returns a user by account name
     *
     * @param string $accountName
     * @param array $attributes
     * @return User
     */
    public function user($accountName, $attributes = [])
    {
        $attributes = ($attributes) ? $attributes : User::DEFAULT_ATTRIBUTES;
        return $this->users()->findBy('sAMAccountName', $accountName, $attributes);
    }

    /**
     * Get a new DialectInterface instance
     *
     * @return DialectInterface
     */
    protected function newDialect()
    {
        $dialectClass = $this->configuration->get('dialect');
        $dialect = new $dialectClass();
        $dialect->setHost($this->configuration->get('host'));
        $dialect->setPort($this->configuration->get('port'));
        $dialect->setSsl($this->configuration->get('ssl'));
        $dialect->setBaseDn($this->configuration->get('baseDn'));
        if ($dialect->isRootDn()) {
            $dialect->setBaseDn($this->connection->getDefaultNamingContext());
        }

        return $dialect;
    }

    protected function newBuilder()
    {
        return new QueryBuilder($this->connection, $this->newDialect(), $this->parser);
    }

    /**
     * Handle dynamic method calls on a new QueryBuilder instance
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->newQuery(), $method], $parameters);
    }
}
