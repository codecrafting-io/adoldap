<?php

namespace CodeCrafting\AdoLDAP\Parsers\Types;

/**
 * Class DateParser.
 *
 * Parse the ADO DATE types to native PHP DateTime
 */
class DateParser extends TypeParser
{
    /**
     * ADO compatibile types
     */
    const ADO_TYPES = [7, 5];

    /**
     * LDAP initial DateTime
     *
     * @var [type]
     */
    private static $ldapDtStart;

    /**
     * Gets the LDAP initial DateTime
     *
     * @return void
     */
    public static function getLdapDtStart()
    {
        if (! self::$ldapDtStart) {
            self::$ldapDtStart = new \DateTime('@' . -11644473600);
        }

        return self::$ldapDtStart;
    }

    /**
     * @inheritDoc
     */
    public function getADOTypes()
    {
        return self::ADO_TYPES;
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return parent::DATE;
    }

    /**
     * @inheritDoc
     */
    public function parse($value)
    {
        if ($value) {
            return $this->parseVariantDate($value);
        }

        return 0;
    }

    /**
     * Parse a VARIANT VT_DISPATCH for Filetime objects
     *
     * @param VARIANT $value
     * @return DateTime|null
     */
    public function parseFiletime($value)
    {
        if (isset($value->HighPart)) {
            $high = $value->HighPart;
            $low = $value->LowPart;
            if ($high > 0) {
                if ($low < 0) {
                    $high += 1;
                }
                $windowsTimestamp = ($high << 32) + $low;
                $unixTimestamp = intval($windowsTimestamp / 10000000) + -11644473600;

                return new \DateTime('@' . $unixTimestamp);
            } elseif ($high == 0) {
                return DateParser::getLdapDtStart();
            }
        }

        return null;
    }

    /**
     * Parse a VARIANT Date value to DateTime
     *
     * @param VARIANT $value
     * @return DateTime
     */
    public function parseVariantDate($value)
    {
        $timestamp = variant_date_to_timestamp($value);

        return new \DateTime('@' . $timestamp);
    }
}
