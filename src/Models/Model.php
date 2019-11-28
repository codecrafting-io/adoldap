<?php

namespace CodeCrafting\AdoLDAP\Models;

use CodeCrafting\AdoLDAP\Models\Attributes\ObjectClass;
use CodeCrafting\AdoLDAP\Models\Attributes\DistinguishedName;

/**
 * Class model.
 */
class Model extends Entry
{
    /**
     * Distinguished name
     *
     * @var DistinguishedName
     */
    protected $dn;

    /**
     * Object class
     *
     * @var ObjectClass
     */
    protected $objectClass;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if ($this->hasAttribute('distinguishedname')) {
            $this->setDn($this->getAttribute('distinguishedname'));
        }
        if ($this->hasAttribute('objectclass')) {
            $this->setObjectClass($this->getAttribute('objectclass'));
        }
    }


    /**
     * Gets the distinguished name
     *
     * @return DistinguishedName
     */
    public function getDn()
    {
        return $this->dn;
    }

    /**
     * Sets the distinguished name
     *
     * @param DistinguishedName|string $dn
     * @return self
     */
    public function setDn($dn)
    {
        if (is_object($dn) && $dn instanceof DistinguishedName) {
            $this->dn = $dn;
        } else {
            $this->dn = new DistinguishedName($dn);
        }
        $this->setAttribute('distinguishedname', $this->dn->getPath());

        return $this;
    }

    /**
     * Gets the objectClass
     *
     * @return ObjectClass
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * Set object class of the entry
     *
     * @param  ObjectClass|array $objectClass  Object class of the model
     * @return  self
     */
    protected function setObjectClass($objectClass)
    {
        if (is_object($objectClass) && $objectClass instanceof ObjectClass) {
            $this->objectClass = $objectClass;
        } else {
            $this->objectClass = new ObjectClass($objectClass);
        }
        $this->setAttribute('objectclass', $this->objectClass->getClasses());

        return $this;
    }

    /**
     * Gets the object creation time
     *
     * @return \DateTime|null
     */
    public function createdAt()
    {
        return $this->getAttribute('whenCreated');
    }

    /**
     * Gets the object GUID
     *
     * @return string
     */
    public function getGuid()
    {
        return $this->getAttribute('objectguid');
    }

    /**
     * Gets the object SID
     *
     * @return string
     */
    public function getSid()
    {
        return $this->getAttribute('objectsid');
    }
}
