<?php

namespace CodeCrafting\AdoLDAP\Models\Attributes;

/**
 * Class Address.
 */
class Address
{
    /**
     * Contry
     *
     * @var string
     */
    private $country;

    /**
     * State
     *
     * @var string
     */
    private $state;

    /**
     * City
     *
     * @var string
     */
    private $city;

    /**
     * Street address. Can include the street name with neighborhood and number
     *
     * @var string
     */
    private $streetAddress;

    /**
     * Postal code number
     *
     * @var string
     */
    private $postalCode;


    /**
     * Get contry
     *
     * @return  string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set contry
     *
     * @param  string  $country  Contry
     *
     * @return  self
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get state
     *
     * @return  string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set state
     *
     * @param  string  $state  State
     *
     * @return  self
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get city
     *
     * @return  string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set city
     *
     * @param  string  $city  City
     *
     * @return  self
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get street address. Can include the street name with neighborhood and number
     *
     * @return  string
     */
    public function getStreetAddress()
    {
        return $this->streetAddress;
    }

    /**
     * Set street address. Can include the street name with neighborhood and number
     *
     * @param  string  $streetAddress  Street address. Can include the street name with neighborhood and number
     *
     * @return  self
     */
    public function setStreetAddress($streetAddress)
    {
        $this->streetAddress = $streetAddress;

        return $this;
    }

    /**
     * Get postal code number
     *
     * @return  string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Set postal code number
     *
     * @param  string  $postalCode  Postal code number
     *
     * @return  self
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Get a array representation
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'streetAddress' => $this->streetAddress,
            'postalCode' => $this->postalCode
        ];
    }
}
