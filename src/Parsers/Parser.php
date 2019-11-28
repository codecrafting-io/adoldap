<?php

namespace CodeCrafting\AdoLDAP\Parsers;

use CodeCrafting\AdoLDAP\Models\User;
use CodeCrafting\AdoLDAP\Models\Entry;
use CodeCrafting\AdoLDAP\Models\Group;
use CodeCrafting\AdoLDAP\Models\Model;
use CodeCrafting\AdoLDAP\Models\Computer;
use CodeCrafting\AdoLDAP\Parsers\Types\TypeParser;
use CodeCrafting\AdoLDAP\Models\Attributes\ObjectClass;

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
     * Parse a resultset entry
     *
     * @param \VARIANT|array $entry
     * @return Entry
     */
    public function parseEntry($entry)
    {
        $attributes = [];
        if (is_array($entry)) {
            foreach ($entry as $name => $field) {
                $attributes[strtolower($name)] = $this->parseField($field);
            }
        } else {
            foreach ($entry as $field) {
                $attributes[strtolower($field->name)] = $this->parseField($field);
            }
        }

        return $this->newEntry($attributes);
    }

    /**
     * @inheritDoc
     */
    public function parseField($field)
    {
        if ($field !== null) {
            $fieldSchema = $this->getFieldSchema($field);
            if (in_array($fieldSchema['type'], [\VT_VARIANT, 8204])) {
                if ($fieldSchema['value'] && count($fieldSchema['value']) > 0) {
                    $aux = [];
                    foreach ($fieldSchema['value'] as $value2) {
                        $aux[] = $this->parseField($value2);
                    }

                    return $aux;
                }
            }
            foreach ($this->typeParsers as $typeParser) {
                if ($typeParser->isType($fieldSchema['type'])) {
                    return $typeParser->parse($fieldSchema['value']);
                }
            }

            return $fieldSchema['value'];
        }

        return null;
    }

    /**
     * Create a new Entry or Model instance
     *
     * @param array $attributes
     * @return Entry
     */
    private function newEntry(array $attributes = [])
    {
        $model = Entry::class;
        if (array_key_exists('objectclass', $attributes)) {
            $modelMap = $this->getObjectClassModelMap();
            $objectClass = (new ObjectClass($attributes['objectclass']))->getMostRelevant();
            $model = $modelMap[$objectClass] ?? Model::class;
        }

        return new $model($attributes);
    }

    /**
     * Get the objectClass LDAP Model Map
     *
     * @return void
     */
    private function getObjectClassModelMap()
    {
        return [
            User::objectClass()->getMostRelevant()       => User::class,
            Group::objectClass()->getMostRelevant()      => Group::class,
            Computer::objectClass()->getMostRelevant()     => Computer::class
        ];
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
                    if (! $this->getTypeParser(TypeParser::OBJECT)->isType($type)) {
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
     * @return TypeParser
     */
    private function getTypeParser(int $typeParserId)
    {
        return $this->typeParsers[$typeParserId];
    }
}
