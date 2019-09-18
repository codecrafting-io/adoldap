<?php

namespace CodeCrafting\AdoLDAP;

use InvalidArgumentException;
use CodeCrafting\AdoLDAP\AdoLDAPException;
use CodeCrafting\AdoLDAP\Dialects\SQLDialect;
use CodeCrafting\AdoLDAP\Parsers\ParserInterface;
use CodeCrafting\AdoLDAP\Query\ResultSetIterator;
use CodeCrafting\AdoLDAP\Dialects\DialectInterface;
use CodeCrafting\AdoLDAP\Connections\LDAPConnection;
use CodeCrafting\AdoLDAP\Connections\AdodbConnection;
use CodeCrafting\AdoLDAP\Connections\ProviderInterface;
use CodeCrafting\AdoLDAP\Connections\ConnectionException;
use CodeCrafting\AdoLDAP\Configuration\AdoLDAPConfiguration;

/**
 * Class AdoLDAP
 *
 * Provides LDAP funcionalities as connecting, binding and searching
 */
class AdoLDAP implements ProviderInterface
{
    /**
     * The connection configuration
     *
     * @var AdoLDAPConfiguration
     */
    protected $configuration;

    /**
     * The LDAP connection
     *
     * @var LDAPConnection
     */
    protected $connection;

    /**
     * Dialect search syntax for the ADSI
     *
     * @var DialectInterface
     */
    protected $dialect;

    /**
     * ResultSet data parser
     *
     * @var ParserInterface
     */
    protected $parser;

    /**
     * Constructor.
     *
     * @param AdoLDAPConfiguration|array $configuration
     * @throws AdoLDAPException if environment is incompatible
     */
    public function __construct($configuration)
    {
        if (self::isCompatible()) {
            $this->setConfiguration($configuration)->setConnection();
            if ($this->configuration->get('autoConnect')) {
                $this->connect($this->configuration->get('username'), $this->configuration->get('password'));
            }
        } else {
            throw new AdoLDAPException('Current environment is not a Windows system or did not loaded COM extension');
        }
    }

    /**
     * Close connection (if bound) upon destruction
     */
    public function __destruct()
    {
        $this->connection->unbind();
    }

    /**
     * Gets the current ldap connection
     *
     * @return LDAPConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Sets the current connection
     *
     * @return self
     */
    protected function setConnection()
    {
        $this->connection = new LDAPConnection();

        return $this;
    }

    /**
     * Gets the current configuration
     *
     * @return AdoLDAPConfiguration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Sets the current configuration
     *
     * @param  AdoLDAPConfiguration|array  $configuration  The provider configuration
     * @throws CodeCrafting\AdoLDAP\Configuration\ConfigurationException when configuration is invalid
     * @return self
     */
    public function setConfiguration($configuration = [])
    {
        if (! $this->configuration) {
            $this->configuration = new AdoLDAPConfiguration();
        }
        if (is_array($configuration)) {
            $configuration = $this->configuration->setAll($configuration);
        }
        if ($configuration instanceof AdoLDAPConfiguration) {
            $this->configuration = $configuration;
            $dialectClass = $this->configuration->get('dialect');
            $this->dialect = new $dialectClass();
            $this->dialect->setHost($this->configuration->get('host'));
            $this->dialect->setPort($this->configuration->get('port'));
            $this->dialect->setSsl($this->configuration->get('ssl'));
            $this->dialect->setBaseDn($this->configuration->get('baseDn'));
            $parser = $this->configuration->get('parser');
            $this->parser = new $parser();

            return $this;
        }
        $class = AdoLDAPConfiguration::class;
        throw new InvalidArgumentException("Configuration must be array or instance of {$class}");
    }

    /**
     * Gets the current dialect
     *
     * @return DialectInterface
     */
    public function getDialect()
    {
        return $this->dialect;
    }

    /**
     * Sets the current dialect
     *
     * @param DialectInterface $dialect
     * @return self
     */
    public function setDialect(DialectInterface $dialect)
    {
        if ($dialect !== null) {
            $this->dialect = $dialect;

            return $this;
        } else {
            throw new InvalidArgumentException('Dialect must not be null');
        }
    }

    /**
     * Gets the current parser
     *
     * @return ParserInterface
     */
    public function getParser()
    {
        $this->parser;
    }

    /**
     * Set the current parser
     *
     * @return self
     */
    public function setParser(ParserInterface $parser)
    {
        if ($parser !== null) {
            $this->parser = $parser;

            return $this;
        } else {
            throw new InvalidArgumentException('Parser must not be null');
        }
    }

    /**
     * Connect and bind to the domain controller using the current configuration
     *
     * @return bool
     */
    public function connect()
    {
        if (! $this->connection->isBound()) {
            if ($this->dialect->isRootDn()) {
                $this->dialect->setBaseDn($this->connection->getDefaultNamingContext());
            }
            if ($this->connection->bind($this->getConfiguration()->get('username'), $this->getConfiguration()->get('password'))) {
                if ($this->configuration->get('checkConnection')) {
                    $this->checkConnection();
                }
            }
        }

        return $this;
    }

    /**
     * Search entries on LDAP BASE DN with the provided configuration
     *
     * @param string $filter
     * @param mixed $attributes
     * @param integer $scope
     * @return ResultSetIterator
     */
    public function search($filter, $attributes, int $scope = AdodbConnection::ADS_SCOPE_SUBTREE)
    {
        $resultSet = $this->connection->search($this->dialect->getCommand($filter, $attributes), $scope);
        return new ResultSetIterator($resultSet, $this->parser);
    }

    /**
     * Check the connection
     *
     * @return boolean
     */
    public function checkConnection()
    {
        if ($this->connection->isBound()) {
            $result = $this->search('(objectClass=*)', ['ADsPath'], AdodbConnection::ADS_SCOPE_BASE);

            return ($result != null) ? $result->valid() : false;
        }

        return false;
    }

    /**
     * Get the default naming context
     *
     * @return string|null
     */
    public function getDefaultNamingContext()
    {
        return $this->connection->getDefaultNamingContext();
    }

    /**
     * Get overall information about the domains on the current AD
     *
     * @throws ConnectionException if is not connected
     * @return array
     */
    public function info()
    {
        if ($this->connection->isBound()) {
            $logonDomainController = null;
            try {
                $logonDomainController = $this->connection->getLogonDomainController();
            } catch (AdoLDAPException $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            }

            return [
                'domain' => $this->connection->getDomain(),
                'domainName' => $this->connection->getDomainName(),
                'defaultNamingContext' => $this->connection->getDefaultNamingContext(),
                'logonDomainController' => $logonDomainController,
                'machineDomainController' => $this->connection->getMachineDomainController(),
                'primaryDomainControllers' => $this->connection->getPrimaryDomainControllers(),
                'domainControllers' => $this->getDomainControllers()
            ];
        } else {
            throw new ConnectionException("Connection not established");
        }
    }

    /**
     * Get the domain controllers within the default naming context
     *
     * @return array
     */
    protected function getDomainControllers()
    {
        if ($this->connection->isBound()) {
            $baseDn = $this->dialect->getBaseDn();
            $this->dialect->setBaseDn('OU=DOMAIN CONTROLLERS,' . $this->connection->getDefaultNamingContext());
            $result = null;
            try {
                $filter = '(objectCategory=Computer)';
                if ($this->dialect instanceof SQLDialect) {
                    $filter = "objectCategory = 'Computer'";
                }
                $result = $this->search($filter, ['dnsHostName']);
            } catch (AdodbException $e) {
                $this->dialect->setBaseDn($baseDn);
                throw $e;
            }
            $controllers = [];
            if ($result) {
                $controllers = array_map(function ($controller) {
                    return strtolower($controller['dnsHostName']);
                }, $result->getElements());
            }
            sort($controllers);
            $this->dialect->setBaseDn($baseDn);

            return $controllers;
        } else {
            throw new ConnectionException("Connection not established");
        }
    }

    /**
     * Check whether or not if the current environment is compatibile
     *
     * @return bool
     */
    public static function isCompatible()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && class_exists(\COM::class, false)) {
            return true;
        }

        return false;
    }
}
