<?php

namespace CodeCrafting\AdoLDAP\Configuration;

use CodeCrafting\AdoLDAP\Parsers\Parser;
use CodeCrafting\AdoLDAP\Dialects\LDAPDialect;
use CodeCrafting\AdoLDAP\Dialects\DialectInterface;

/**
 * Class AdoLDAPConfiguration.
 *
 * Contains an array of configuration options for a ADODB LDAP connection.
 */
class AdoLDAPConfiguration
{
    /**
     * Connection default configuration
     *
     * @var array
     */
    protected $options = [
        // The host address.
        'host' => null,

        // The port to use for connecting to your host.
        'port' => DialectInterface::PORT,

        // The dialect class to use for the ADO LDAP search and bind.
        'dialect' => LDAPDialect::class,

        /**
         * The base distinguished name of your domain.
         * Use ROOT_DN to discover the defaultNamingContext.
         */
        'baseDn' => DialectInterface::ROOT_DN,

        // The username to connect to your hosts with.
        'username' => null,

        // The password that is utilized with the above user.
        'password' => null,

        // Whether or not to use SSL when connecting to your host. This override the port configuration
        'ssl' => false,

        // Whether or not to automaticly connect with the LDAP Provider
        'autoConnect' => true,

        //Timeout of connection execution in seconds
        'timeout' => 30,

        //Maximum number of objects to return in a results set
        'pageSize' => 1000,

        //Whether or not to check connection execution on bind
        'checkConnection' => false,

        //The data parser of a result set
        'parser' => Parser::class
    ];

    /**
     * Constructor.
     *
     * @param array $options
     * @throws ConfigurationException When an option value given is an invalid type.
     */
    public function __construct(array $options = [])
    {
        $this->setAll($options);
    }

    /**
     * Sets a configuration option.
     *
     * Throws an exception if the specified option does
     * not exist, or if it's an invalid type.
     *
     * @param string $key
     * @param mixed  $value
     * @throws ConfigurationException When an option value given is an invalid type.
     * @return self
     */

    public function set($key, $value)
    {
        if ($this->validate($key, $value)) {
            $this->options[$key] = $value;
        }

        return $this;
    }

    /**
     * Sets all configuration
     *
     * @param array $options
     * @throws ConfigurationException When an option value given is an invalid type.
     * @return self
     */
    public function setAll(array $options = [])
    {
        foreach ($options as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * Returns the value for the specified configuration options.
     *
     * Throws an exception if the specified option does not exist.
     *
     * @param string $key
     *
     * @throws ConfigurationException When the option specified does not exist.
     *
     * @return mixed
     */
    public function get($key)
    {
        if ($this->has($key)) {
            return $this->options[$key];
        }

        throw new ConfigurationException("Option {$key} does not exist.");
    }

    /**
     * Checks if a configuration option exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->options);
    }

    /**
     * Validates the new configuration option against its
     * default value to ensure it's the correct type.
     *
     * If an invalid type is given, an exception is thrown.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @throws ConfigurationException When an option value given is an invalid type.
     *
     * @return bool
     */
    protected function validate($key, $value)
    {
        $default = $this->get($key);

        if (is_array($default)) {
            $validator = new Validators\ArrayValidator($key, $value);
        } elseif (is_int($default)) {
            $validator = new Validators\IntegerValidator($key, $value);
        } elseif (is_bool($default)) {
            $validator = new Validators\BooleanValidator($key, $value);
        } elseif (class_exists($default)) {
            $validator = new Validators\ClassValidator($key, $value);
        } else {
            $validator = new Validators\StringOrNullValidator($key, $value);
        }

        return $validator->validate();
    }
}
