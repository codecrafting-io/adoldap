<?php

namespace CodeCrafting\AdoLDAP\Parsers\Types;

/**
 * Class BinaryParser.
 *
 * Parse the ADO binary Array to native PHP base64 encoded string
 */
class BinaryParser extends TypeParser
{
    /**
     * ADO compatibile types
     */
    const ADO_TYPES = [204];

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
        return parent::BINARY;
    }

    /**
     * @inheritDoc
     */
    public function parse($value)
    {
        if ($value) {
            $size = count($value);
            $decodedValue = $this->decodeBinary($value);
            switch ($size) {
                case 16:
                    return $this->decodeGuid($decodedValue);
                    break;

                case 28:
                    return $this->decodeSid($decodedValue);
                    break;

                default:
                    return base64_encode($decodedValue);
                    break;
            }
        }

        return null;
    }

    private function decodeGuid($binGuid)
    {
        if (trim($binGuid) == '' || is_null($binGuid)) {
            return;
        }
        $hex = unpack('H*hex', $binGuid)['hex'];
        $hex1 = substr($hex, -26, 2).substr($hex, -28, 2).substr($hex, -30, 2).substr($hex, -32, 2);
        $hex2 = substr($hex, -22, 2).substr($hex, -24, 2);
        $hex3 = substr($hex, -18, 2).substr($hex, -20, 2);
        $hex4 = substr($hex, -16, 4);
        $hex5 = substr($hex, -12, 12);

        return sprintf('%s-%s-%s-%s-%s', $hex1, $hex2, $hex3, $hex4, $hex5);
    }

    private function decodeSid($binSid)
    {
        // Get revision, indentifier, authority
        $parts = unpack('Crev/x/nidhigh/Nidlow', $binSid);
        // Set revision, indentifier, authority
        $sid = sprintf('S-%u-%d',  $parts['rev'], ($parts['idhigh']<<32) + $parts['idlow']);
        // Translate domain
        $parts = unpack('x8/V*', $binSid);
        // Append if parts exists
        if ($parts) {
            $sid .= '-';
        }
        // Join all
        $sid .= join('-', $parts);
        return $sid;
    }

    private function decodeBinary($bin)
    {
        $strBin = '';
        foreach ($bin as $value) {
            $strBin .= pack('c', $value);
        }

        return $strBin;
    }
}
