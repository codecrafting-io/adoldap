<?php

namespace CodeCrafting\AdoLDAP\Dialects;

/**
 * Class LDAPDialect.
 *
 * A class that specifies and build a Command string using the LDAP dialect.
 */
class LDAPDialect extends DialectInterface
{
    /**
     *
     */
    const COMMAND_ORDER = ['FROM', 'FILTERS', 'SELECT'];
    const COMMAND_SEPARATOR = ';';

    /**
     * {@inheritdoc}
     */
    public function getCommandOrder()
    {
        return self::COMMAND_ORDER;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandSeparator()
    {
        return self::COMMAND_SEPARATOR;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeValue($value, $flag = null)
    {
        if ($flag) {
            return ldap_escape($value, '', $flag);
        }

        //TODO: check for * in values
        return $value;//ldap_escape($value, '', LDAP_ESCAPE_FILTER);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier($identifier)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function compileFilters(array $filters)
    {
        $filters = $this->splitFilterByType($filters);
        $filterString = $this->concatenate($filters['&'], '&');
        if (count($filters['|']) > 0) {
            $filterString = $this->wrap($filterString . $this->concatenate($filters['|'], '|'), '(&');
        }

        return $filterString;
    }

    /**
     * {@inheritdoc}
     */
    public function compileSelect(array $attributes)
    {
        return implode(',', $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function compileFrom()
    {
        $adPath = parent::PROTOCOL;
        if ($this->isSsl()) {
            $adPath = parent::SSL_PROTOCOL;
        }
        if ($this->host && ! $this->isRootDn()) {
            $adPath .= $this->host;
            if ($this->port != parent::PORT) {
                $adPath .= ':' . $this->port;
            }
            $adPath .= '/';
        }
        $adPath .= $this->baseDn;

        return "<{$adPath}>";
    }

    /**
     * Translates a SQL boolean operant to LDAP syntax
     *
     * @param string $operand
     * @return string
     */
    public function sqlBooleanOperandToLDAP(string $operand)
    {
        return ($operand === 'AND') ? '&' : '|';
    }

    /**
     * Wraps a filter expression string in brackets.
     *
     * Example: (expression)
     *
     * @param string $expression
     * @param string $prefix
     * @param string $suffix
     * @return string
     */
    protected function wrap(string $expression, $prefix = '(', $suffix = ')')
    {
        return $prefix.$expression.$suffix;
    }

    /**
     * Concatenate filter expressions by type
     *
     * @param array $filters
     * @param string $type
     * @return string
     */
    protected function concatenate(array $filters, string $type)
    {
        $filterString = '';
        foreach ($filters as $filter) {
            $filterString .= $this->wrap($filter);
        }
        if (count($filters) > 1) {
            $filterString = $this->wrap($filterString, '(' . $type);
        }

        return $filterString;
    }

    /**
     * Split filter by boolean operand type
     *
     * @param array $filters
     * @return array
     */
    protected function splitFilterByType(array $filters)
    {
        $newFilter = ['&' => [], '|' => []];
        foreach ($filters as $filter) {
            if ($filter['type']) {
                $newFilter[$this->sqlBooleanOperandToLDAP($filter['type'])][] = $filter['expression'];
            }
        }

        return $newFilter;
    }
}
