<?php

namespace CodeCrafting\AdoLDAP\Connections;

use CodeCrafting\AdoLDAP\Dialects\DialectInterface;

/**
 * Class ADODBConnection.
 *
 * Abstracts the ADO connection to LDAP with ADsDSOObject provider
 */
class ADODBConnection
{
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
     * @return bool
     */
    public function connect()
    {
        if (! $this->isConnected()) {
            $this->connection = new \COM('ADODB.Connection') or Die('Failed to create a ADODB connection');
            $this->connection->Provider = 'ADsDSOObject';
            $this->connection->open();
            $this->connected = true;
        }

        return true;
    }

    /**
     * Get the defaultNamingContext of Active Directory
     *
     * @throws ConnectionException When obtain a invalid or null RootDSE
     * @return string
     */
    public function getDefaultNamingContext()
    {
        $rootDse = new \COM(DialectInterface::PROTOCOL . DialectInterface::ROOT_DN);
        if ($rootDse) {
            return strval($rootDse->Get('defaultNamingContext'));
        } else {
            throw new ConnectionException("Failed to obtain defaultNamingContext");
        }
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
     * @throws ConnectionException When there's no connection established or failed to execute a command
     * @return \VARIANT
     */
    public function execute(string $command)
    {
        if ($this->isConnected()) {
            try {
                $adodbCommand = new \COM("ADODB.Command");
                $adodbCommand->ActiveConnection = $this->connection;
                $adodbCommand->CommandText = $command;

                return $adodbCommand->execute();
            } catch (\com_exception $e) {
                throw new ExecutionException("Failed to execute command {$command}", $e->getCode(), $e);
            }
        } else {
            throw new ConnectionException("Invalid operation. No connection established");
        }
    }
}
