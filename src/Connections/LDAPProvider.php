<?php

namespace CodeCrafting\AdoLDAP\Connections;

use InvalidArgumentException;
use CodeCrafting\AdoLDAP\AdoLDAPException;
use CodeCrafting\AdoLDAP\Parsers\ParserInterface;
use CodeCrafting\AdoLDAP\Dialects\DialectInterface;
use CodeCrafting\AdoLDAP\Connections\ADODBConnection;
use CodeCrafting\AdoLDAP\Connections\ExecutionException;
use CodeCrafting\AdoLDAP\Connections\ConnectionException;
use CodeCrafting\AdoLDAP\Configuration\AdoLDAPConfiguration;
use CodeCrafting\AdoLDAP\Configuration\ConfigurationException;

/**
 * Class LDAPProvider.
 *
 * Provides a abstraction for a ADODBConnection with LDAP binding and searching
 */
class LDAPProvider
{
    /**
     * The connection configuration
     *
     * @var AdoLDAPConfiguration
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
     * @var ParserInterface
     */
    private $parser;

    /**
     * Default naming context for the current connection
     *
     * @var string|null
     */
    private $defaultNamingContext;

    /**
     * Full domain
     *
     * @var string
     */
    private $domain;

    /**
     * Machine domain name
     *
     * @var string
     */
    private $domainName;

    const ADO_RESULTSET_OPEN = 1;

    /**
     * Constructor.
     *
     * @param AdoLDAPConfiguration|array $configuration
     */
    public function __construct($configuration = [])
    {
        $this->setConfiguration($configuration);
        $parser = $this->configuration->get('parser');
        $parser = new $parser();
        if ($parser instanceof ParserInterface) {
            $this->parser = $parser;
            if ($this->configuration->get('autoBind')) {
                $this->bind();
            }
        } else {
            throw new ConfigurationException('parser must implements ParserInterface');
        }
    }

    /**
     * Close ADODB connection (if bound) upon destruction
     */
    public function __destruct()
    {
        $this->unbind();
    }


    /**
     * Get the provider connection configuration
     *
     * @return  AdoLDAPConfiguration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Set the provider connection configuration
     *
     * @param  AdoLDAPConfiguration|array  $configuration  The provider connection configuration
     * @throws CodeCrafting\AdoLDAP\Configuration\ConfigurationException when configuration is invalid
     * @return  self
     */
    public function setConfiguration($configuration = [])
    {
        if (is_array($configuration)) {
            $configuration = new AdoLDAPConfiguration($configuration);
        }
        if ($configuration instanceof AdoLDAPConfiguration) {
            $this->configuration = $configuration;
            $dialectClass = $this->configuration->get('dialect');
            $this->dialect = new $dialectClass();
            $this->dialect->setHost($this->configuration->get('host'));
            $this->dialect->setPort($this->configuration->get('port'));
            $this->dialect->setSsl($this->configuration->get('ssl'));
            $this->dialect->setBaseDn($this->configuration->get('baseDn'));

            return $this;
        }
        $class = AdoLDAPConfiguration::class;

        throw new InvalidArgumentException("Configuration must be array or instance of {$class}");
    }

    /**
     * Get the default naming context. Returns null if unbound
     *
     * @return string|null
     */
    public function getDefaultNamingContext()
    {
        return $this->defaultNamingContext;
    }

    /**
     * Get the principal domain suffix name
     *
     * @throws AdoLDAPException if failed to execute dns discover through dns_get_record
     * @return string
     */
    public function getDomain()
    {
        if (! $this->domain) {
            $hostname = gethostname();
            $dns = dns_get_record($hostname);
            if ($dns) {
                $this->domain = str_replace($hostname . '.', '', $dns[0]['host']);
            } else {
                throw new AdoLDAPException('Could not get domain');
            }
        }

        return $this->domain;
    }

    /**
     * Get machine domain name
     *
     * @return  string
     */
    public function getDomainName()
    {
        if (! $this->domainName) {
            if (isset($_SERVER['USERDOMAIN'])) {
                $this->domainName = $_SERVER['USERDOMAIN'];
            } else {
                $this->domainName = exec('echo %userdomain%');
            }
        }

        return $this->domainName;
    }

    /**
     * Get the domain controllers within the default naming context
     *
     * @return array
     */
    public function getDomainControllers()
    {
        if ($this->bound) {
            $baseDn = $this->dialect->getBaseDn();
            $this->dialect->setBaseDn('OU=DOMAIN CONTROLLERS,' . $this->defaultNamingContext);
            $result = null;
            try {
                $result = $this->search('(objectCategory=Computer)', ['dnsHostName']);
            } catch (ExecutionException $e) {
                $this->dialect->setBaseDn($baseDn);
                throw $e;
            }
            $controllers = [];
            $entries = $this->getEntries($result);
            if ($entries) {
                $controllers = array_map(function ($controller) {
                    return strtolower($controller['dnsHostName']);
                }, $entries);
            }
            sort($controllers);
            $this->dialect->setBaseDn($baseDn);

            return $controllers;
        } else {
            throw new ConnectionException("Connection not established");
        }
    }

    /**
     * Get the primary domain controllers
     * @throws AdoLDAPException if failed to execute a shell nltest
     * @return array
     */
    public function getPrimaryDomainControllers()
    {
        $result = shell_exec('nltest /dclist:');
        if (! empty($result)) {
            $lines = explode("\n", $result);
            $controllers = [];
            foreach ($lines as $key => $value) {
                if (stripos($value, '[PDC]')) {
                    $controllers[] = strtolower(trim(explode('[PDC]', $value)[0]));
                }
            }

            return $controllers;
        } else {
            throw new AdoLDAPException('Could not get primary domain controllers');
        }
    }

    /**
     * Get the domain controller which is currently logged. Only works if script user is logged to AD machine
     *
     * @throws AdoLDAPException if failed to obtain the logonserver.
     * @return string|null
     */
    public function getLogonDomainController()
    {
        $controller = str_replace('\\\\', '', trim(exec('echo %logonserver%')));
        if ($controller && $controller != '%logonserver%') {
            return strtolower($controller) . '.' . $this->getDomain();
        } else {
            throw new AdoLDAPException('Could not get the logon controller');
        }
    }

    /**
     * Get the current connected DC of the machine
     *
     * @throws AdoLDAPException if failed to execute nltest
     * @return string|null
     */
    public function getMachineDomainController()
    {
        $result = shell_exec('nltest /dsgetdc:');
        if (! empty($result)) {
            $lines = explode("\n", $result);
            if ($lines) {
                return strtolower(str_replace('DC: \\\\', '', trim($lines[0])));
            }
        }

        throw new AdoLDAPException('Could not get the machine controller');
    }

    /**
     * Get overall information about the domains on the current AD
     *
     * @throws ConnectionException if is not connected
     * @return array
     */
    public function info()
    {
        if ($this->bound) {
            $logonDomainController = null;
            try {
                $logonDomainController = $this->getLogonDomainController();
            } catch (AdoLDAPException $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            }

            return [
                'domain' => $this->getDomain(),
                'domainName' => $this->getDomainName(),
                'defaultNamingContext' => $this->defaultNamingContext,
                'logonDomainController' => $logonDomainController,
                'machineDomainController' => $this->getMachineDomainController(),
                'primaryDomainControllers' => $this->getPrimaryDomainControllers(),
                'domainControllers' => $this->getDomainControllers()
            ];
        } else {
            throw new ConnectionException("Connection not established");
        }
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
     * Bind with the provided configuration
     *
     * @return bool
     */
    public function bind()
    {
        $host = ($this->configuration->get('bindToLogonServer')) ? $this->getLogonDomainController() : null;

        return $this->bindWithServer($host);
    }

    /**
     * Bind to a server or use the provided configuration
     *
     * @param string $host
     * @return bool
     */
    private function bindWithServer($host)
    {
        if (! $this->bound) {
            $this->connection = new ADODBConnection();
            $this->connection->setTimeout($this->configuration->get('timeout'));
            $this->connection->setPageSize($this->configuration->get('pageSize'));

            //For some reason is faster to bind to RootDSE first
            $this->defaultNamingContext = $this->connection->getDefaultNamingContext();
            if ($this->dialect->isRootDn()) {
                $this->dialect->setBaseDn($this->defaultNamingContext);
            }
            if ($this->connection->connect($this->configuration->get('username'), $this->configuration->get('password'))) {
                $this->bound = true;
                if ($host) {
                    $this->dialect->setHost($host);
                }
                if ($this->configuration->get('checkConnection')) {
                    return $this->checkConnection();
                }

                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Unbind and disconnect from ADODB
     *
     * @return void
     */
    public function unbind()
    {
        if ($this->bound) {
            if ($this->connection->isConnected()) {
                $this->connection->disconnect();
            }
            $this->dialect->setBaseDn($this->configuration->get('baseDn'));
            $this->defaultNamingContext = null;
            $this->bound = false;
        }
    }

    /**
     * Search entries on LDAP BASE DN with the provided settings
     *
     * @param string $filter
     * @param array $attributes
     * @param string $context Context of the search within it's search dialect
     * @return \VARIANT
     */
    public function search($filter, $attributes, $context = null)
    {
        if ($this->bound) {
            $command = $this->dialect->getCommand($filter, $attributes, $context);
            try {
                return $this->connection->execute($command);
            } catch (ConnectionException $e) {
                $this->unbind();
                throw $e;
            }
        } else {
            throw new ConnectionException("Connection not established");
        }
    }

    /**
     * Get resultset size from search
     *
     * @param \VARIANT $resultSet
     * @throws InvalidArgumentException|AdoLDAPException if resultSet is invalid or resultSet is not opened
     * @return int
     */
    public function getEntrySize($resultSet)
    {
        if (is_object($resultSet) && $resultSet instanceof \VARIANT) {
            if ($resultSet->State === self::ADO_RESULTSET_OPEN) {
                $resultSet->MoveLast();

                return $resultSet->RecordCount;
            } else {
                throw new AdoLDAPException('resultSet must be open');
            }
        } else {
            throw new InvalidArgumentException('resultSet must be a variant object');
        }
    }

    /**
     * Parse LDAP ResultSet Fields entries to native PHP values
     *
     * @param \VARIANT $resultSet
     * @param bool|null $containerNameOnly
     * @param int $offset
     * @param int $limit
     * @throws InvalidArgumentException if resultSet, offset or limit is invalid
     * @return array
     */
    public function getEntries($resultSet, $containerNameOnly = null, int $offset = 0, int $limit = 0)
    {
        if (is_object($resultSet) && $resultSet instanceof \VARIANT) {
            if ($offset >= 0 && $limit >= 0) {
                $containerNameOnly = ($containerNameOnly !== null) ? $containerNameOnly : $this->configuration->get('containerNameOnly');
                $result = [];
                if (! $resultSet->EOF) {
                    if ($resultSet->AbsolutePosition > 1) {
                        $resultSet->MoveFirst();
                    }
                    if ($offset > 0) {
                        $resultSet->Move($offset);
                    }
                }
                $size = 0;
                while (! $resultSet->EOF && ($limit == 0 || $size < $limit)) {
                    $aux = [];
                    foreach ($resultSet->fields as $key => $field) {
                        if ($field->name == 'distinguishedName') {
                            $aux[$field->name] = $this->parser->parse($field, false);
                        } else {
                            $aux[$field->name] = $this->parser->parse($field, $containerNameOnly);
                        }
                    }
                    if ($aux) {
                        $result[] = $aux;
                    }
                    $size++;
                    $resultSet->MoveNext();
                }
                $resultSet->close();

                return $result;
            } else {
                throw new InvalidArgumentException('offset and limit must not be negative');
            }
        } else {
            throw new InvalidArgumentException('resultSet must be a VARIANT object');
        }
    }

    /**
     * Get the ADODB Connection
     *
     * @return  ADODBConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Check the connection
     *
     * @return boolean
     */
    public function checkConnection()
    {
        if ($this->bound) {
            return empty($this->search('(objectClass=*)', ['ADsPath'], 'base'));
        }

        return false;
    }
}
