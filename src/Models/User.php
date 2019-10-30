<?php

namespace CodeCrafting\AdoLDAP\Models;

use CodeCrafting\AdoLDAP\Models\Attributes\Address;
use CodeCrafting\AdoLDAP\Models\Attributes\ObjectClass;
use CodeCrafting\AdoLDAP\Models\Attributes\DistinguishedName;

/**
 * User model class.
 */
class User extends Model
{
    /**
     * Column attributes with the originam LDAP name
     *
     * @var array
     */
    const RAW_ATTRIBUTES = [
        'objectclass',
        'distinguishedName',
        'sAMAccountName',
        'givenName',
        'name',
        'userWorkstations',
        'mail',
        'description',
        'title',
        'street',
        'postalCode',
        'st',
        'l',
        'co',
        'msExchDelegateListBL',
        'mobile',
        'telephoneNumber',
        'department',
        'extensionAttribute1',
        'memberOf',
        'company',
        'thumbnailPhoto',
        'pwdLastSet',
        'badPwdCount',
        'badPasswordTime',
        'lastLogonTimestamp',
        'lockoutTime',
        'objectguid',
        'objectsid'
    ];

    /**
     * Default objectClass
     *
     * @var ObjectClass
     */
    private static $defaultClass;

    /**
     * User addresss
     *
     * @var Address
     */
    private $address;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (! $this->objectClass) {
            $this->setObjectClass(self::defaultClass());
        }
        $this->getAddress();
        if ($this->hasAttribute('description')) {
            $description = $this->getAttribute('description');
            if (is_array($description)) {
                $this->setAttribute('description', current($description));
            }
        }
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
                'user'
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
     * Gets user's login name, aka sAMAccountName
     *
     * @return void
     */
    public function getLogin()
    {
        return strtolower($this->getAttribute('sAMAccountName'));
    }

    /**
     * Sets user's login name, aka sAMAccountName
     *
     * @param string $value
     * @return self
     */
    public function setLogin(string $login)
    {
        return $this->setAttribute('sAMAccountName', strtolower($login));
    }

    /**
     * Gets the user's first name
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->getAttribute('givenName');
    }

    /**
     * Sets the user's first name
     *
     * @param string $firstName
     * @return self
     */
    public function setFirstName(string $firstName)
    {
        return $this->setAttribute('givenName', $firstName);
    }

    /**
     * Gets the user's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getAttribute('name');
    }

    /**
     * Sets the user's name
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name)
    {
        return $this->setAttribute('name', $name);
    }

    /**
     * Gets user's allowed workstations. In case of a empty array no restrictions are aplied
     *
     * @return array
     */
    public function getWorkstations()
    {
        $workstations = explode(',', $this->getAttribute('userWorkstations'));

        return $workstations ?? [];
    }

    /**
     * Sets the user's allowed workstations. In case of a empty array no restrictions are aplied
     *
     * @param array $workstations
     * @return self
     */
    public function setWorkstations(array $workstations)
    {
        return $this->setAttribute('userWorkstations', implode(',', $workstations));
    }

    /**
     * Gets the user's mail
     *
     * @return string
     */
    public function getMail()
    {
        return strtolower($this->getAttribute('mail'));
    }

    /**
     * Sets the user's mail
     *
     * @param string $mail
     * @return self
     */
    public function setMail(string $mail)
    {
        return $this->setAttribute('mail', strtolower($mail));
    }

    /**
     * Get user's job title description
     *
     * @return string
     */
    public function getJobTitle()
    {
        return $this->getAttribute('description');
    }

    /**
     * Sets user's job title description
     *
     * @param string $jobTitle
     * @return self
     */
    public function setJobTitle(string $jobTitle)
    {
        return $this->setAttribute('description', $jobTitle);
    }

    /**
     * Gets user's job role title
     *
     * @return string
     */
    public function getJobRole()
    {
        return $this->getAttribute('title');
    }

    /**
     * Sets the user's job role
     *
     * @param string $jobRole
     * @return self
     */
    public function setJobRole(string $jobRole)
    {
        return $this->setAttribute('title', $jobRole);
    }

    /**
     * Get address as a object
     *
     * @return Address|null
     */
    public function getAddress()
    {
        if (! $this->address) {
            $this->address = new Address();
            $this->address->setCountry($this->getAttribute('co'));
            $this->address->setState($this->getAttribute('st'));
            $this->address->setCity($this->getAttribute('l'));
            $this->address->setStreetAddress($this->getAttribute('street'));
            $this->address->setPostalCode($this->getAttribute('postalCode'));
        }

        return $this->address;
    }

    /**
     * Set address
     *
     * @param Address $address
     * @return self
     */
    public function setAddress(Address $address)
    {
        if ($address !== null) {
            $this->address = $address;
            $this->setAttribute('co', $address->getCountry());
            $this->setAttribute('st', $address->getState());
            $this->setAttribute('l', $address->getCity());
            $this->setAttribute('street', $address->getStreetAddress());
            $this->setAttribute('postalCode', $address->getPostalCode());
        }

        return $this;
    }

    /**
     * Gets the user's exchange mailboxes
     *
     * @param bool $nameOnly whether or not return full distinguished name or just the common name
     * @return array
     */
    public function getMailboxes(bool $nameOnly = true)
    {
        return array_map(function ($mailbox) use ($nameOnly) {
            if ($nameOnly) {
                return (new DistinguishedName($mailbox))->getName();
            }

            return new DistinguishedName($mailbox);
        }, $this->getAttribute('msExchDelegateListBL'));
    }

    /**
     * Gets user's mobile phone
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->getAttribute('mobile');
    }

    /**
     * Sets the user's mobile phone
     *
     * @param string $mobile
     * @return self
     */
    public function setMobile(string $mobile)
    {
        return $this->setAttribute('mobile', $mobile);
    }

    /**
     * Gets the user's business phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->getAttribute('telephoneNumber');
    }

    /**
     * Sets the user's bussiness phone
     *
     * @param string $phone
     * @return self
     */
    public function setPhone(string $phone)
    {
        return $this->setAttribute('telephoneNumber', $phone);
    }

    /**
     * Gets the user's branch line number.
     *
     * @param integer $size the size of branch line number
     * @throws ModelException if $size is negative
     * @return string
     */
    public function getBranchLine(int $size = 4)
    {
        if ($size > 0) {
            if ($phone = $this->getPhone()) {
                return substr($phone, -$size);
            }

            return null;
        } else {
            throw new ModelException('size must be greater than zero');
        }
    }

    /**
     * Gets user's department
     *
     * @return string
     */
    public function getDepartment()
    {
        return $this->getAttribute('department');
    }

    /**
     * Sets the user's department
     *
     * @param string $department
     * @return self
     */
    public function setDepartment(string $department)
    {
        return $this->setAttribute('department', $department);
    }

    /**
     * Gets the user's department code
     *
     * @return string
     */
    public function getDepartmentCode()
    {
        return $this->getAttribute('extensionAttribute1');
    }

    public function setDepartmentCode(string $departmentCode)
    {
        return $this->setAttribute('departmentCode', $departmentCode);
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

    /**
     * Gets the user's company name
     *
     * @return string
     */
    public function getCompany()
    {
        return $this->getAttribute('company');
    }

    /**
     * Sets the user's company name
     *
     * @param string $company
     * @return self
     */
    public function setCompany(string $company)
    {
        return $this->setAttribute('company', $company);
    }

    /**
     * Gets the user's base64 encoded photo
     *
     * @return string
     */
    public function getPhoto()
    {
        return $this->getAttribute('thumbnailPhoto');
    }

    /**
     * Gets the user's photo as image html tag
     *
     * @param array $attributes
     * @return string
     */
    public function getHtmlPhoto(array $attributes = [])
    {
        if ($photo = $this->getAttribute('thumbnailPhoto')) {
            $mime = 'image/jpeg';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open();
                $mime = finfo_buffer($finfo, $photo, FILEINFO_MIME_TYPE);
            }
            $attributesStr = '';
            foreach ($attributes as $key => $value) {
                if (stripos($key, 'src') === false) {
                    $value = (is_array($value)) ? implode(' ', $value) : $value;
                    $attributesStr .= " {$key}=\"{$value}\"";
                }
            }

            return "<img src=\"data:{$mime};base64,{$photo}\"{$attributesStr}/>";
        }

        return null;
    }

    /**
     * Gets the user's photo raw data
     *
     * @return string
     */
    public function getRawPhoto()
    {
        if ($photo = $this->getPhoto()) {
            return base64_decode($photo);
        }

        return null;
    }

    /**
     * Sets the user's photo
     *
     * @param string $data
     * @param bool   $encode
     * @return self
     */
    public function setPhoto($data, $encode = true)
    {
        if ($encode && !base64_decode($data)) {
            // If the string we're given is not base 64 encoded, then
            // we will encode it before setting it on the user.
            $data = base64_encode($data);
        }

        return $this->setAttribute('thumbnailPhoto', $data);
    }

    /**
     * Gets the user's last time the password was setted. This attribute may not be accurate depending of the host connected
     *
     * @return \DateTime|null
     */
    public function getPasswordLastSet()
    {
        return $this->getAttribute('pwdLastSet');
    }

    /**
     * Gets the user's last number of times of password errors
     *
     * @return int
     */
    public function getPasswordErrorCount()
    {
        return $this->getAttribute('badPwdCount');
    }

    /**
     * Gets the user's last error time of a password. This attribute may not be accurate depending of the host connected
     *
     * @return \DateTime|null
     */
    public function getPasswordErrorTime()
    {
        return $this->getAttribute('badPasswordTime');
    }

    /**
     * Gets the user's last logon. This attribute may not be accurate depending of the host connected
     *
     * @return \DateTime
     */
    public function getLastLogon()
    {
        return $this->getAttribute('lastLogonTimestamp');
    }

    /**
     * Gets the user's last lockout time. The value 0 means the user is not lockout.
     *
     * @return \DateTime|int
     */
    public function getLockoutTime()
    {
        return $this->getAttribute('lockoutTime');
    }

    /**
     * Check whether the user are lockout
     *
     * @return bool
     */
    public function isLockout()
    {
        return ($this->getLockoutTime() === 0) ? false : true;
    }
}
