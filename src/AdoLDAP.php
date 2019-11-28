<?php

namespace CodeCrafting\AdoLDAP;

use InvalidArgumentException;
use CodeCrafting\AdoLDAP\AdoLDAPException;
use CodeCrafting\AdoLDAP\Dialects\SQLDialect;
use CodeCrafting\AdoLDAP\Query\SearchFactory;
use CodeCrafting\AdoLDAP\Parsers\ParserInterface;
use CodeCrafting\AdoLDAP\Connections\AdodbException;
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
            throw new AdoLDAPException('Current environment must be a Windows system with 64 bit PHP with COM extension loaded');
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
     * @inheritDoc
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @inheritDoc
     */
    protected function setConnection()
    {
        $this->connection = new LDAPConnection();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @inheritDoc
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
            $parser = $this->configuration->get('parser');
            $this->parser = new $parser();
            return $this;
        }
        $class = AdoLDAPConfiguration::class;
        throw new InvalidArgumentException("Configuration must be array or instance of {$class}");
    }

    /**
     * @inheritDoc
     */
    public function getParser()
    {
        $this->parser;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function connect()
    {
        if (! $this->connection->isBound()) {
            if ($this->connection->bind($this->getConfiguration()->get('username'), $this->getConfiguration()->get('password'))) {
                if ($this->configuration->get('checkConnection')) {
                    $this->checkConnection();
                }
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function search()
    {
        if ($this->connection->isBound()) {
            return new SearchFactory($this->connection, $this->configuration, $this->parser);
        } else {
            throw new ConnectionException("Connection not established");
        }
    }

    /**
     * @inheritDoc
     */
    public function checkConnection()
    {
        if ($this->connection->isBound()) {
            try {
                $this->search()
                    ->setSearchScope(AdodbConnection::ADS_SCOPE_BASE)
                    ->whereEquals('objectClass', '*')
                    ->firstOrFail(['ADsPath']);
            } catch (AdodbException $e) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultNamingContext()
    {
        return $this->connection->getDefaultNamingContext();
    }

    /**
     * @inheritDoc
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
     * @throws AdodbException|ConnectionException if no connection is established or failed to search
     * @return array
     */
    public function getDomainControllers()
    {
        if ($this->connection->isBound()) {
            $controllers = $this->search()->computers()
                ->from('OU=DOMAIN CONTROLLERS,' . $this->connection->getDefaultNamingContext())
                ->select(['dnsHostName'])
                ->get()->afterFetch(function ($entry) {
                    return strtolower($entry->getAttribute('dnsHostName'));
                })->getEntries();
            sort($controllers);

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
        if (PHP_INT_SIZE == 8 && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && class_exists(\COM::class, false)) {
            return true;
        }

        return false;
    }
}
