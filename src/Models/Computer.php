<?php

namespace CodeCrafting\AdoLDAP\Models;

use CodeCrafting\AdoLDAP\Models\Attributes\OS;
use CodeCrafting\AdoLDAP\Models\Attributes\DistinguishedName;

/**
 * Class Computer
 */
class Computer extends Model
{
    /**
     * Column attributes with the original LDAP name
     *
     * @var array
     */
    const RAW_ATTRIBUTES = [
        'objectclass',
        'distinguishedName',
        'name',
        'operatingSystem',
        'operatingSystemVersion',
        'memberOf',
        'whenCreated',
        'objectGUID',
        'objectSID'
    ];

    /**
     * Default objectClass
     *
     * @var ObjectClass
     */
    private static $defaultClass;

    /**
     * Operating System
     *
     * @var OS
     */
    protected $os;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (! $this->objectClass) {
            $this->setObjectClass(self::defaultClass());
        }
        $this->getOS();
    }

    /**
     * Get default object class
     *
     * @return ObjectClass
     */
    public static function defaultClass()
    {
        if (! self::$defaultClass) {
            self::$defaultClass = new ObjectClass([
                'top',
                'person',
                'organizationalPerson',
                'user',
                'computer'
            ]);
        }

        return self::$defaultClass;
    }

    /**
     * Get model map keys
     *
     * @return array
     */
    public static function getRawAttributes()
    {
        return self::RAW_ATTRIBUTES;
    }

    /**
     * Get Operating System
     *
     * @return void
     */
    public function getOS()
    {
        if (! $this->os) {
            $this->os = new OS($this->getAttribute('operatingSystem'), $this->getAttribute('operatingSystemVersion'));

            return $this->os;
        }

        return $this->os;
    }

    /**
     * Gets the computer's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getAttribute('name');
    }

    /**
     * Sets the computer's name
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name)
    {
        return $this->setAttribute('name', $name);
    }

    /**
     * Gets groups which the user's is a member
     *
     * @param bool $nameOnly whether or not return full distinguished name or just the common name
     * @return array
     */
    public function getMemberOf(bool $nameOnly = true)
    {
        if (! $this->isEmpty('memberOf')) {
            return array_map(function ($mailbox) use ($nameOnly) {
                if ($nameOnly) {
                    return DistinguishedName::extractDnComponent($mailbox, 'cn');
                }

                return new DistinguishedName($mailbox);
            }, $this->getAttribute('memberOf'));
        }

        return null;
    }

    /**
     * Check whether or not the user's is a member of the provided group
     *
     * @param DistinguishedName|string $group
     * @throws ModelException if group is not a string or a instance of DistinguidedName
     * @return bool
     */
    public function isMemberOf($group)
    {
        if (is_object($group) && $group instanceof DistinguishedName) {
            $memberOf = $this->getMemberOf(false);
            if ($memberOf === null) {
                return false;
            }
            return boolval(array_filter($memberOf, function ($dn) use ($group) {
                return $group->equals($dn);
            }));
        } elseif (is_string($group)) {
            $memberOf = $this->getMemberOf();
            if ($memberOf === null) {
                return false;
            }
            $group = trim(strtolower($group));

            return (array_search($group, array_map('strtolower', $memberOf)) !== false);
        } else {
            throw new ModelException('group must be a string or a instance of' . DistinguishedName::class);
        }
    }
}
