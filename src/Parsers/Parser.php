<?php

namespace CodeCrafting\AdoLDAP\Parsers;

use CodeCrafting\AdoLDAP\Parsers\Types\TypeParser;

/**
 * Class Parser.
 *
 * Parse ResultSet field ADO values
 */
class Parser implements ParserInterface
{
    /**
     * All available type parsers
     *
     * @var TypeParser[]
     */
    private $typeParsers;

    /**
     * Constructor.
     *
     * @param boolean $containerNameOnly Whether or not to only returns the name for container values
     */
    public function __construct()
    {
        $this->typeParsers = TypeParser::getParsers();
    }

    /**
     * @inheritDoc
     */
    public function parse($field, bool $containerNameOnly)
    {
        if ($field !== null) {
            $fieldSchema = $this->getFieldSchema($field);
            if ($fieldSchema['type'] == \VT_VARIANT) {
                if ($fieldSchema['value'] && count($fieldSchema['value']) > 0) {
                    $aux = [];
                    foreach ($fieldSchema['value'] as $key => $value2) {
                        $aux[] = $this->parse($value2, $containerNameOnly);
                    }

                    return $aux;
                }
            }
            foreach ($this->typeParsers as $typeParser) {
                if ($typeParser->isType($fieldSchema['type'])) {
                    $value = $typeParser->parse($fieldSchema['value']);
                    if ($typeParser->getType() == TypeParser::STRING) {
                        return $this->parseContainer($value, $containerNameOnly);
                    }

                    return $value;
                }
            }

            return $fieldSchema['value'];
        }

        return null;
    }

    /**
     * Get field valye/type schema
     *
     * @param mixed $field
     * @return array
     */
    private function getFieldSchema($field)
    {
        $type = 202;
        $value = null;
        if (is_a($field, \VARIANT::class)) {
            if (isset($field->type)) {
                $type = $field->type;
                $value = $field->value;
                if ($type == \VT_VARIANT && is_a($value, \VARIANT::class)) {
                    $type = variant_get_type($value);
                    if (! $this->getTypeParser(TypeParser::FILETIME)->isType($type)) {
                        $type = $field->type;
                    }
                }
            } else {
                $type = variant_get_type($field);
                $value = $field;
            }
        } else {
            if (is_array($field)) {
                $type = 12;
            } else {
                $type = is_int($field) ? 3 : 202;
            }
            $value = $field;
        }

        return [
            'type'  => $type,
            'value' => $value
        ];
    }

    /**
     * Get the parser by a Parser constant ID
     *
     * @param integer $typeParserId
     * @return void
     */
    private function getTypeParser(int $typeParserId)
    {
        return $this->typeParsers[$typeParserId];
    }

    /**
     * Parse a LDAP container to native PHP array
     *
     * @param string $container
     * @param boolean $containerNameOnly Only returns the name for container values
     * @return array|string
     */
    private function parseContainer($container, $containerNameOnly)
    {
        $containerName = explode(',', $container);
        if (strpos($container, 'CN=') === 0 && count($containerName) > 1) {
            $containerName = str_replace('CN=', '', $containerName[0]);
        } else {
            $containerName = null;
        }
        if ($containerName !== null) {
            if ($containerNameOnly) {
                return $containerName;
            } else {
                return [
                    'path' => $container,
                    'name' => $containerName
                ];
            }
        } else {
            return $container;
        }
    }
}
