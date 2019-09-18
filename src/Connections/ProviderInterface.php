<?php

namespace CodeCrafting\AdoLDAP\Connections;

use CodeCrafting\AdoLDAP\Parsers\ParserInterface;
use CodeCrafting\AdoLDAP\Query\ResultSetIterator;
use CodeCrafting\AdoLDAP\Dialects\DialectInterface;

/**
 * Interface ProviderInterface.
 *
 * Provides a abstraction for a LDAPConnection
 */
interface ProviderInterface
{
    /**
     * Constructor.
     *
     * @param AdoLDAPConfiguration|array $configuration
     */
    public function __construct($configuration);

    /**
     * Gets the current ldap connection
     *
     * @return LDAPConnection
     */
    public function getConnection();

    /**
     * Gets the current configuration
     *
     * @return AdoLDAPConfiguration
     */
    public function getConfiguration();

    /**
     * Set the provider configuration
     *
     * @param  AdoLDAPConfiguration|array  $configuration  The provider configuration
     * @throws CodeCrafting\AdoLDAP\Configuration\ConfigurationException when configuration is invalid
     * @return self
     */
    public function setConfiguration($configuration = []);

    /**
     * Gets the current dialect
     *
     * @return DialectInterface
     */
    public function getDialect();

    /**
     * Sets the current dialect
     *
     * @param DialectInterface $dialect
     * @return self
     */
    public function setDialect(DialectInterface $dialect);

    /**
     * Gets the current parser
     *
     * @return ParserInterface
     */
    public function getParser();

    /**
     * Set the current parser
     *
     * @return self
     */
    public function setParser(ParserInterface $parser);

    /**
     * Check the connection
     *
     * @return boolean
     */
    public function checkConnection();

    /**
     * Connect and bind to the domain controller using the current configuration
     *
     * @return self
     */
    public function connect();

    /**
     * Search entries on LDAP BASE DN with the provided configuration
     *
     * @param string $filter
     * @param mixed $attributes
     * @param integer $scope
     * @return ResultSetIterator
     */
    public function search($filter, $attributes, int $scope = AdodbConnection::ADS_SCOPE_SUBTREE);

    /**
     * Get the default naming context
     *
     * @return string
     */
    public function getDefaultNamingContext();

    /**
     * Get overall information about the domains on the current AD
     *
     * @throws ConnectionException if is not connected
     * @return array
     */
    public function info();
}
