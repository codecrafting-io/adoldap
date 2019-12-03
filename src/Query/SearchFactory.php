<?php

namespace CodeCrafting\AdoLDAP\Query;

use CodeCrafting\AdoLDAP\Models\User;
use CodeCrafting\AdoLDAP\Models\Group;
use CodeCrafting\AdoLDAP\Models\Computer;
use CodeCrafting\AdoLDAP\Parsers\ParserInterface;
use CodeCrafting\AdoLDAP\Dialects\DialectInterface;
use CodeCrafting\AdoLDAP\Connections\LDAPConnection;
use CodeCrafting\AdoLDAP\Configuration\AdoLDAPConfiguration;

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
     * Returns a user by account name. Use $translate for translate values with the User COLUMN_MAP
     *
     * @param string $accountName
     * @param array|string $attributes
     * @param bool $translate
     * @return User
     */
    public function user($accountName, $attributes = [], $translate = true)
    {
        if ($attributes) {
            $attributes = ($translate) ? self::translateAttributes(User::COLUMN_MAP, $attributes) : $attributes;
        } else {
            $attributes = User::getDefaultAttributes();
        }

        return $this->users()->firstBy('sAMAccountName', $accountName, $attributes);
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
     * Returns a computer by name. Use $translate for translate values with the Computer COLUMN_MAP
     *
     * @param string $name
     * @param array|string $attributes
     * @param bool $translate
     * @return Computer
     */
    public function computer($name, $attributes = [], $translate = true)
    {
        if ($attributes) {
            $attributes = ($translate) ? self::translateAttributes(Computer::COLUMN_MAP, $attributes) : $attributes;
        } else {
            $attributes = Computer::getDefaultAttributes();
        }

        return $this->computers()->firstBy('cn', $name, $attributes);
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
     * Returns a query builder scoped to search groups
     *
     * @return QueryBuilder
     */
    public function groups()
    {
        return $this->category(Group::objectClass()->getMostRelevant());
    }

    /**
     * Returns a group by name. Use $translate for translate values with the Group COLUMN_MAP
     *
     * @param string $name
     * @param array|string $attributes
     * @param bool $translate
     * @return Group
     */
    public function group($name, $attributes = [], $translate = true)
    {
        if ($attributes) {
            $attributes = ($translate) ? self::translateAttributes(Group::COLUMN_MAP, $attributes) : $attributes;
        } else {
            $attributes = Group::getDefaultAttributes();
        }

        return $this->groups()->firstBy('cn', $name, $attributes);
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

    /**
     * Get the translated values from a model column map
     *
     * @param array $columnMap
     * @param array|string $attributes
     * @return array
     */
    public static function translateAttributes(array $columnMap, $attributes)
    {
        $translation = [];
        $attributes = (is_string($attributes)) ? explode(',', $attributes) : $attributes;
        foreach ($attributes as $attribute) {
            $attribute = strtolower($attribute);
            if (array_key_exists($attribute, $columnMap)) {
                $newAttribute = $columnMap[$attribute];
                if (is_array($newAttribute)) {
                    $translation = array_merge($translation, array_values($newAttribute));
                } else {
                    $translation[] = $newAttribute;
                }
            }
        }

        return $translation;
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
}
