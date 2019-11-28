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
    const DEFAULT_ATTRIBUTES = [
        'objectclass',
        'distinguishedName',
        'displayName',
        'member',
        'whenCreated',
        'objectGUID',
        'objectSID'
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
     * Check whether or not the user's is a member of the group
     *
     * @param DistinguishedName|string $groupMember
     * @throws ModelException if group is not a string or a instance of DistinguidedName
     * @return bool
     */
    public function inMembers($groupMember)
    {
        if (is_object($groupMember) && $groupMember instanceof DistinguishedName) {
            $members = $this->getMembers(false);
            if ($members === null) {
                return false;
            }
            return boolval(array_filter($members, function ($dn) use ($groupMember) {
                return $groupMember->equals($dn);
            }));
        } elseif (is_string($groupMember)) {
            $members = $this->getMemberOf();
            if ($members === null) {
                return false;
            }
            $groupMember = trim(strtolower($groupMember));

            return (array_search($groupMember, array_map('strtolower', $members)) !== false);
        } else {
            throw new ModelException('group must be a string or a instance of' . DistinguishedName::class);
        }
    }
}
