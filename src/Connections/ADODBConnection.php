<?php

namespace CodeCrafting\AdoLDAP\Connections;

use CodeCrafting\AdoLDAP\Dialects\DialectInterface;

/**
 * Class AdodbConnection.
 *
 * Abstracts the ADO connection to LDAP with ADsDSOObject provider
 */
class AdodbConnection
{
    /**
     * Base search scope
     */
    const ADS_SCOPE_BASE = 0;

    /**
     * One level search scope
     */
    const ADS_SCOPE_ONELEVEL = 1;

    /**
     * Subtree search scope
     */
    const ADS_SCOPE_SUBTREE = 2;

    /**
     * The connection status
     *
     * @var bool
     */
    private $connected;

    /**
     * The ADODB COM object connection
     *
     * @var \COM
     */
    private $connection;

    /**
     * Connection timeout in seconds
     *
     * @var int
     */
    private $timeout = 30;

    /**
     * Maximum number of objects to return in a results set. @see https://docs.microsoft.com/en-us/windows/win32/adsi/searching-with-activex-data-objects-ado
     *
     * @var integer
     */
    private $pageSize = 1000;

    /**
     * Get the connection status of the connection
     *
     * @return  bool
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * Create ao ADODB Connection with ADSI Provider
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function connect(string $username = null, string $password = null)
    {
        if (! $this->isConnected()) {
            $this->connection = new \COM('ADODB.Connection') or die('Failed to create a ADODB connection');
            $this->connection->Provider = 'ADsDSOObject';
            if (! (empty($username) || empty($password))) {
                $this->connection->Properties['User ID'] = $username;
                $this->connection->Properties['Password'] = $password;
                $this->connection->Properties['Encrypt Password'] = true;
            }
            $this->connection->open('Active Directory Provider');
            $this->connected = true;
        }

        return true;
    }

    /**
     * Get connection timeout in seconds
     *
     * @return  int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Set connection timeout in seconds
     *
     * @param  int  $timeout  Connection timeout in seconds
     *
     * @return  self
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Get maximum number of objects to return in a results set. @see https://docs.microsoft.com/en-us/windows/win32/adsi/searching-with-activex-data-objects-ado
     *
     * @return  int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * Set maximum number of objects to return in a results set. @see https://docs.microsoft.com/en-us/windows/win32/adsi/searching-with-activex-data-objects-ado
     *
     * @param  int  $pageSize  Maximum number of objects to return in a results set.
     * @throws AdodbException if pageSize is lower than 0
     * @return  self
     */
    public function setPageSize(int $pageSize)
    {
        if ($this->isPageSizeValid($pageSize)) {
            $this->pageSize = $pageSize;
        }

        return $this;
    }

    /**
     * Get the defaultNamingContext of Active Directory
     *
     * @throws ConnectionException When obtain a invalid or null RootDSE
     * @return string
     */
    public function getDefaultNamingContext()
    {
        $rootDse = $this->getLdapObject(DialectInterface::ROOT_DN);
        if ($rootDse) {
            return strval($rootDse->Get('defaultNamingContext'));
        } else {
            throw new ConnectionException("Failed to obtain defaultNamingContext");
        }
    }

    /**
     * Get a LDAP object
     *
     * @param string $path
     * @return mixed
     */
    public function getLdapObject(string $path)
    {
        if (stripos($path, DialectInterface::PROTOCOL) === false) {
            $path = DialectInterface::PROTOCOL . $path;
        }

        return new \COM($path);
    }

    /**
     * Close a existing connection
     *
     * @return bool
     */
    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->connection->close();
            $this->connected = false;
        }

        return true;
    }

    /**
     * Execute a COM Command with a existing connection.
     * If successful, returns a VARIANT (type 9) for COM _ResultSet interface
     *
     * @param string $command
     * @param int $scope The execution LDAP scope. Must be 0 - base, 1 - onelevel, 2 - subtree
     * @param array $properties The search properties of ADODB.Command
     * @throws ConnectionException|AdodbException When there's no connection established, invalid scope or execution failed
     * @return void
     */
    public function execute(string $command, array $properties = [])
    {
        if ($this->connected) {
            $defaultProperties['Timeout'] = $this->timeout;
            $defaultProperties['SearchScope'] = self::ADS_SCOPE_SUBTREE;
            if ($this->pageSize > 0) {
                $defaultProperties['Page Size'] = $this->pageSize;
            }
            try {
                return $this->getCommand($command, array_merge($defaultProperties, $properties))->execute();
            } catch (\com_exception $e) {
                throw new AdodbException("Failed to execute command {$command}", $e->getCode(), $e);
            }
        } else {
            throw new ConnectionException('Invalid operation. No connection established');
        }
    }

    /**
     * Check if scope is valid
     *
     * @param int $scope
     * @return bool
     */
    protected function isScopeValid($scope)
    {
        if (! $scope !== null && ($scope === self::ADS_SCOPE_BASE || $scope === self::ADS_SCOPE_ONELEVEL || $scope === self::ADS_SCOPE_SUBTREE)) {
            return true;
        }

        $scope = ($scope === null) ? 'null' : $scope;
        throw new AdodbException("Invalid scope {$scope}");
    }

    /**
     * Check if pageSize is valid
     *
     * @param int $pageSize
     * @throws AdodbException if pageSize is lower than 0
     * @return bool
     */
    public function isPageSizeValid($pageSize)
    {
        if (is_int($pageSize) && $pageSize < 0) {
            throw new AdodbException('pageSize must not be negative');
        }

        return true;
    }

    /**
     * Get a new ADO Command. For all properties available properties check the link
     *
     * @link https://docs.microsoft.com/pt-br/sql/ado/guide/appendixes/microsoft-ole-db-provider-for-microsoft-active-directory-service
     * @param string $command
     * @param array $properties
     * @throws AdodbException if Page Size or SearchScope is invalid
     * @return \COM
     */
    protected function getCommand(string $command, array $properties)
    {
        $adodbCommand = new \COM('ADODB.Command');
        $adodbCommand->CommandText = $command;
        $adodbCommand->ActiveConnection = $this->connection;
        foreach ($properties as $key => $value) {
            if ($key == 'SearchScope') {
                $this->isScopeValid($value);
            } elseif ($key == 'Page Size') {
                $this->isPageSizeValid($value);
            }
            $adodbCommand->Properties[$key] = $value;
        }

        return $adodbCommand;
    }
}
