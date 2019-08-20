<?php

namespace CodeCrafting\AdoLDAP\Connections;

use CodeCrafting\AdoLDAP\Parser\FieldParser;
use CodeCrafting\AdoLDAP\Connections\ADODBConnection;
use CodeCrafting\AdoLDAP\Connections\ConnectionException;
use CodeCrafting\AdoLDAP\Configuration\ConnectionConfiguration;

/**
 * Class LDAPProvider.
 *
 * Provides a abstraction for a ADODBConnection with LDAP binding and searching
 */
class LDAPProvider
{
    /**
     * The provider connection configuration
     *
     * @var ConnectionConfiguration
     */
    private $configuration;

    /**
     * The ADODB Connection
     *
     * @var ADODBConnection
     */
    private $connection;

    /**
     * The bound status for a correct connection provider
     *
     * @var bool
     */
    private $bound = false;

    /**
     * Dialect search and connection provider for ADSI
     *
     * @var DialectInterface
     */
    private $dialect;

    /**
     * Field data parser
     *
     * @var FieldParser
     */
    private $fieldParser;


    /**
     * Constructor.
     *
     * @param ConnectionConfiguration|array $configuration
     */
    public function __construct($configuration = [])
    {
        $this->setConfiguration($configuration);
        $this->fieldParser = new FieldParser($this->configuration->get('containerNameOnly'));
        if ($this->configuration->get('autoBind')) {
            $this->bind();
        }
    }

    /**
     * Close ADODB connection (if bound) upon destruction
     */
    public function __destruct()
    {
        if ($this->bound && $this->connection->isConnected()) {
            $this->connection->disconnect();
            $this->bound = false;
        }
    }


    /**
     * Get the provider connection configuration
     *
     * @return  ConnectionConfiguration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Set the provider connection configuration
     *
     * @param  ConnectionConfiguration|array  $configuration  The provider connection configuration
     * @throws CodeCrafting\AdoLDAP\Configuration\ConfigurationException when configuration is invalid
     * @return  self
     */
    public function setConfiguration($configuration = [])
    {
        if (is_array($configuration)) {
            $configuration = new ConnectionConfiguration($configuration);
        }
        if ($configuration instanceof ConnectionConfiguration) {
            $this->configuration = $configuration;
            $dialectClass = $this->configuration->get('dialect');
            $this->dialect = new $dialectClass();
            $this->dialect->setHost($this->configuration->get('host'));
            $this->dialect->setPort($this->configuration->get('port'));
            $this->dialect->setSsl($this->configuration->get('ssl'));
            $this->dialect->setBaseDn($this->configuration->get('baseDn'));

            return $this;
        }
        $class = ConnectionConfiguration::class;

        throw new InvalidArgumentException("Configuration must be array or instance of {$class}");
    }

    /**
     * Get the bound status for a correct connection provider
     *
     * @return  bool
     */
    public function isBound()
    {
        return $this->bound;
    }

    /**
     * Test a bind to baseDn
     *
     * @return bool
     */
    public function bind()
    {
        if (! $this->bound) {
            $this->connection = new ADODBConnection();

            //For some reason is faster to bind to RootDSE first
            $defaultNamingContext = $this->connection->getDefaultNamingContext();
            if ($this->dialect->isRootDn()) {
                $this->dialect->setBaseDn($defaultNamingContext);
            }
            if ($this->connection->connect()) {
                $this->bound = true;

                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Search entries on LDAP BASE DN with the provided filter
     *
     * @param string $filter
     * @param array $attributes
     * @param string $context Context of the search within it's search dialect
     * @param bool|null $containerNameOnly Only returns the name for container values. Null value will be replaced by the default provided configuration
     * @return array
     */
    public function search($filter = null, $attributes = [], $context = null, $containerNameOnly = null)
    {
        if ($this->bound) {
            $containerNameOnly = ($containerNameOnly !== null) ? $containerNameOnly : $this->configuration->get('containerNameOnly');
            $command = $this->dialect->getCommand($filter, $attributes, $context);
            $resultSet = $this->connection->execute($command);
            return $this->parseResultSet($resultSet, $containerNameOnly);
        } else {
            throw new ConnectionException("Connection not established");
        }
    }

    /**
     * Parse LDAP ResultSET Fields to native PHP values.
     *
     * @param \VARIANT $resultSet
     * @param bool $containerNameOnly Only returns the name for container values
     * @return array
     */
    private function parseResultSet($resultSet, $containerNameOnly)
    {
        $result = [];
        while (! $resultSet->EOF) {
            $aux = [];
            foreach ($resultSet->fields as $key => $field) {
                if ($field->name == 'distinguishedName') {
                    $aux[$field->name] = $this->fieldParser->parse($field, false);
                } else {
                    $aux[$field->name] = $this->fieldParser->parse($field, $containerNameOnly);
                }
            }
            if ($aux) {
                $result[] = $aux;
            }
            $resultSet->MoveNext();
        }
        if ($resultSet) {
            $resultSet->close();
        }

        return $result;
    }
}
