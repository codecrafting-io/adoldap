<?php

namespace CodeCrafting\AdoLDAP\Connections;

use CodeCrafting\AdoLDAP\AdoLDAPException;

/**
 * Class LDAPConnection.
 *
 * Provides a abstraction for a AdodbConnection with LDAP binding and searching
 */
class LDAPConnection
{
    /**
     * The ADODB Connection
     *
     * @var AdodbConnection
     */
    private $adodbConnection;

    /**
     * The bound status for a correct connection provider
     *
     * @var bool
     */
    private $bound = false;

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

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->adodbConnection = new AdodbConnection();
    }

    /**
     * Get connection timeout in seconds
     *
     * @return  int
     */
    public function getTimeout()
    {
        return $this->adodbConnection->getTimeout();
    }

    /**
     * Set connection timeout in seconds
     *
     * @param  int  $timeout  Connection timeout in seconds
     * @return  self
     */
    public function setTimeout(int $timeout)
    {
        $this->adodbConnection->setTimeout($timeout);

        return $this;
    }

    /**
     * Get maximum number of objects to return in a results set. @see https://docs.microsoft.com/en-us/windows/win32/adsi/searching-with-activex-data-objects-ado
     *
     * @return  int
     */
    public function getPageSize()
    {
        return $this->adodbConnection->getPageSize();
    }

    /**
     * Set maximum number of objects to return in a results set. @see https://docs.microsoft.com/en-us/windows/win32/adsi/searching-with-activex-data-objects-ado
     *
     * @param  int  $pageSize  Maximum number of objects to return in a results set.
     * @return  self
     */
    public function setPageSize(int $pageSize)
    {
        $this->setPageSize($pageSize);

        return $this;
    }

    /**
     * Get the default naming context
     *
     * @return string
     */
    public function getDefaultNamingContext()
    {
        if (! $this->defaultNamingContext) {
            $this->defaultNamingContext = $this->adodbConnection->getDefaultNamingContext();
        }

        return $this->defaultNamingContext;
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
     * Bind with the current configuration
     *
     * @return bool
     */
    public function bind(string $username = null, string $password = null)
    {
        if (! $this->bound) {
            if ($this->adodbConnection->connect($username, $password)) {
                $this->bound = true;

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
            if ($this->adodbConnection->isConnected()) {
                $this->adodbConnection->disconnect();
            }
            $this->defaultNamingContext = null;
            $this->bound = false;
        }
    }

    /**
     * Search entries on LDAP BASE DN with the provided settings
     *
     * @param string $command
     * @param int $scope scope of the search within it's search dialect.
     * @return \VARIANT
     */
    public function search($command, int $scope = AdodbConnection::ADS_SCOPE_SUBTREE)
    {
        if ($this->bound) {
            try {
                return $this->adodbConnection->execute($command, $scope);
            } catch (ConnectionException $e) {
                $this->unbind();
                throw $e;
            }
        } else {
            throw new ConnectionException("Connection not established");
        }
    }

    /**
      * Get the principal domain suffix name. Does not required to be connected
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
                throw new ConnectionException('Could not get domain');
            }
        }

        return $this->domain;
    }

    /**
     * Get machine domain name. Does not required to be connected
     *
     * @return  string
     */
    public function getDomainName()
    {
        if (! $this->domainName) {
            $this->domainName = $_SERVER['USERDOMAIN'] ?? exec('echo %userdomain%');
        }

        return $this->domainName;
    }

    /**
     * Get the primary domain controllers. Does not required to be connected.
     *
     * @throws ConnectionException if failed to execute a shell nltest
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
            throw new ConnectionException('Could not get primary domain controllers');
        }
    }

    /**
     * Get the domain controller which is currently logged. Only works if script user is logged to AD machine.
     * Does not required to be connected.
     *
     * @throws ConnectionException if failed to obtain the logonserver.
     * @return string|null
     */
    public function getLogonDomainController()
    {
        $controller = str_replace('\\\\', '', trim(exec('echo %logonserver%')));
        if ($controller && $controller != '%logonserver%') {
            return strtolower($controller) . '.' . $this->getDomain();
        } else {
            throw new ConnectionException('Could not get the logon controller');
        }
    }

    /**
     * Get the current connected DC of the machine. Does not required to be connected.
     *
     * @throws ConnectionException if failed to execute nltest
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

        throw new ConnectionException('Could not get the machine controller');
    }
}
