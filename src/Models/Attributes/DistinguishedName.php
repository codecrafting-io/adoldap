<?php

namespace CodeCrafting\AdoLDAP\Models\Attributes;

use CodeCrafting\AdoLDAP\Parsers\Types\StringParser;

/**
 * Class DistinguishedName.
 */
class DistinguishedName
{
    /**
     * The distinguished name components (in order of assembly).
     *
     * @var array
     */
    protected $components = [
        'CN'  => [],
        'UID' => [],
        'OU'  => [],
        'DC'  => [],
        'O'   => [],
    ];

    /**
     * Constructor.
     *
     * @param mixed $baseDn
     */
    public function __construct($path = null)
    {
        $this->setPath($path);
    }

    /**
     * Returns the complete distinguished name.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getPath();
    }

    /**
     * Returns the complete distinguished name path
     *
     * @return string
     */
    public function getPath()
    {
        $components = [];
        foreach ($this->components as $component => $rdns) {
            array_map(function ($value) use ($component, &$components) {
                $components[] = $component . '=' . ldap_escape($value, '', LDAP_ESCAPE_FILTER);
            }, $rdns);
        }

        return implode(',', $components);
    }

    /**
     * Sets the path distinguished name.
     *
     * @param string|DistinguishedName $base
     * @return self
     */
    public function setPath($path)
    {
        $path = self::explodeDn($path);
        unset($path['count']);
        foreach ($path as $rdn) {
            $pieces = explode('=', $rdn) ?: [];
            if (count($pieces) == 2) {
                $this->addComponent($pieces[0], StringParser::unescape($pieces[1]));
            }
        }

        return $this;
    }

    /**
     * Get DN common name
     *
     * @return void
     */
    public function getName()
    {
        return implode(',', $this->components['CN']);
    }

    /**
     * Returns an array of all components in the distinguished name.
     *
     * If a component name is given ('cn', 'dc' for example) then
     * the values of that component will be returned.
     *
     * @param string|null $component The component to retrieve values of
     * @return array|null
     */
    public function getComponents($component = null)
    {
        if (is_null($component)) {
            return $this->components;
        } elseif ($this->validateComponent($component)) {
            return $this->components[$component];
        }

        return null;
    }

    /**
     * Adds a component to the distinguished name.
     *
     * @param string $component
     * @param string $value
     * @throws \InvalidArgumentException|\UnexpectedValueException When the given name does not exist or component is invalid
     * @return self
     */
    public function addComponent($component, $value)
    {
        // We need to make sure the value we're given isn't empty before adding it into our components.
        if (! empty($value)) {
            $component = strtoupper($component);
            if ($this->validateComponent($component)) {
                $this->components[$component][] = $value;
            } else {
                throw new \UnexpectedValueException("The RDN component '$component' is invalid.");
            }
        } else {
            throw new \InvalidArgumentException('The $value cannot be empty');
        }

        return $this;
    }

    /**
     * Removes the given value from the given component.
     *
     * @param string $component
     * @param string $value
     * @throws \UnexpectedValueException When the given component is invalid
     * @return self
     */
    public function removeComponent($component, $value)
    {
        $component = strtoupper($component);
        if ($this->validateComponent($component)) {
            $this->components[$component] = array_diff($this->components[$component], [$value]);
        } else {
            throw new \UnexpectedValueException("The RDN component '$component' is invalid.");
        }

        return $this;
    }

    /**
     * Check if whether or not the provided is equal to
     *
     * @param DistinguishedName $dn
     * @return boolean
     */
    public function equals(DistinguishedName $dn)
    {
        if ($dn) {
            return (strcasecmp($this->getPath(), $dn->getPath()) == 0);
        }

        return false;
    }

    /**
     * Validates that the given component exists in the available components.
     *
     * @param string $component The name of the component to validate.
     * @return bool
     */
    protected function validateComponent($component)
    {
        return array_key_exists($component, $this->components);
    }

    /**
     * Converts a DN string into an array of RDNs.
     *
     * https://www.php.net/manual/pt_BR/function.ldap-explode-dn.php#34724
     *
     * @param string $dn
     * @param bool   $removeAttributePrefixes
     * @return array
     */
    public static function explodeDn($dn, $removeAttributePrefixes = false)
    {
        return ldap_explode_dn($dn, ($removeAttributePrefixes ? 1 : 0)) ?: [];
    }

    /**
     * Extracts a DN component string
     *
     * @param string $dn
     * @param string $component
     * @return string
     */
    public static function extractDnComponent($dn, $component)
    {
        $component = strtoupper($component);
        $components = [];
        $path = self::explodeDn($dn);
        unset($path['count']);
        foreach ($path as $rdn) {
            $pieces = explode('=', $rdn) ?: [];
            if (count($pieces) == 2 && strtoupper($pieces[0]) == $component) {
                $components[] = StringParser::unescape($pieces[1]);
            }
        }

        return implode(',', $components);
    }
}
