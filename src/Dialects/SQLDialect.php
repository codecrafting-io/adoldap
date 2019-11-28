<?php

namespace CodeCrafting\AdoLDAP\Dialects;

/**
 * Class SQLDialect.
 *
 * A class that specifies and build a Command string using the SQL dialect.
 */
class SQLDialect extends DialectInterface
{
    /**
     *
     */
    const COMMAND_ORDER = ['SELECT', 'FROM', 'FILTERS'];
    const COMMAND_SEPARATOR = ' ';

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
        if (! is_numeric($value)) {
            return "'" . str_replace("'", "''", $value) . "'";
        }

        return $value;
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
        $filterString = 'WHERE';
        $pParenthesis = false;
        foreach ($filters as $key => $filter) {
            if ($key > 0) {
                if($filter['expression'] == '(') {
                    $pParenthesis = true;
                    $booleanOperator = ' ' . $filter['type'] . ' ';
                } elseif($pParenthesis) {
                    $pParenthesis = false;
                    $booleanOperator = '';
                } else {
                    $booleanOperator = ($filter['type']) ? ' ' . $filter['type'] . ' ' : '';
                }
                $filterString .= $booleanOperator . $filter['expression'];
            } else {
                $filterString .= ' ' . $filter['expression'];
            }
        }

        return $filterString;
    }

    /**
     * {@inheritdoc}
     */
    public function compileSelect(array $attributes)
    {
        return 'SELECT ' . implode(',', $attributes);
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
        if ($this->host) {
            $adPath .= $this->host;
            if ($this->port != parent::PORT) {
                $adPath .= ':' . $this->port;
            }
        }
        $adPath .= '/' . $this->baseDn;

        return "FROM '{$adPath}'";
    }
}
