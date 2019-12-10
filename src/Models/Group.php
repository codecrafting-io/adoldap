<?php

namespace CodeCrafting\AdoLDAP\Models;

use CodeCrafting\AdoLDAP\Models\Attributes\ObjectClass;
use CodeCrafting\AdoLDAP\Models\Attributes\DistinguishedName;

/**
 * Class Group
 */
class Group extends Model
{
    /**
     * Column attributes with the original LDAP name
     *
     * @var array
     */
    const COLUMN_MAP = [
        'objectClass'   => 'objectClass',
        'dn'            => 'distinguishedName',
        'name'          => 'displayName',
        'members'       => 'member',
        'createdAt'     => 'whenCreated',
        'objectGuid'    => 'objectGuid',
        'objectSid'     => 'objectSid'
    ];

    /**
     * Default objectClass
     *
     * @var ObjectClass
     */
    private static $objClass;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (! $this->objectClass) {
            $this->setObjectClass(self::objectClass());
        }
    }

    /**
     * Get default object class
     *
     * @return ObjectClass
     */
    public static function objectClass()
    {
        if (! self::$objClass) {
            self::$objClass = new ObjectClass([
                'top',
                'group',
            ]);
        }

        return self::$objClass;
    }

    /**
     * Get group default attributes
     *
     * @return array
     */
    public static function getDefaultAttributes()
    {
        $return = [];
        $defaultAttributes = array_values(self::COLUMN_MAP);
        array_walk_recursive($defaultAttributes, function ($a) use (&$return) {
            $return[] = $a;
        });

        return $return;
    }

    /**
     * Gets the computer's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getAttribute('displayName');
    }

    /**
     * Sets the computer's name
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name)
    {
        return $this->setAttribute('displayName', $name);
    }

    /**
     * Gets members of the group
     *
     * @param bool $nameOnly whether or not return full distinguished name or just the common name
     * @return array
     */
    public function getMembers(bool $nameOnly = true)
    {
        if (! $this->isEmpty('member')) {
            return array_map(function ($mailbox) use ($nameOnly) {
                if ($nameOnly) {
                    return DistinguishedName::extractDnComponent($mailbox, 'cn');
                }

                return new DistinguishedName($mailbox);
            }, $this->getAttribute('member'));
        }

        return null;
    }

    /**
     * Check whether or not the user or users are member of the group.
     * This method does not resolve nested memberships.
     *
     * @param mixed $user
     * @throws ModelException if group is not a string or a instance of DistinguidedName
     * @return bool
     */
    public function inMembers($user)
    {
        $users = [];
        if (! is_array($user)) {
            $users[] = $user;
        } else {
            $users = $user;
        }

        foreach ($users as $user) {
            if (is_object($user) && ($user instanceof DistinguishedName || $user instanceof User)) {
                $members = $this->getMembers(false);
                if ($members === null) {
                    return false;
                }
                $equals = boolval(array_filter($members, function ($dn) use ($user) {
                    if (isset($user->getDn)) {
                        return $user->getDn()->equals($dn);
                    }

                    return $user->equals($dn);
                }));
                if ($equals) {
                    return true;
                }
            } elseif (is_string($user)) {
                $members = $this->getMembers();
                if ($members === null) {
                    return false;
                }
                $user = trim(strtolower($user));
                $equals = (array_search($user, array_map('strtolower', $members)) !== false);
                if ($equals) {
                    return true;
                }
            } else {
                throw new ModelException('group must be a string or a instance of' . DistinguishedName::class . ' or ' . User::class);
            }
        }

        return false;
    }
}
