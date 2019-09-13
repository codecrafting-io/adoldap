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
     * {@inheritdoc}
     */
    public function getCommand($filter, $attributes)
    {
        if ((is_array($attributes) || is_string($attributes)) && ! empty($attributes)) {
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
            $attributes = implode(',', $attributes);

            return "SELECT {$attributes} FROM '{$adPath}' WHERE {$filter}";
        } else {
            throw new DialectException('attributes must be a string or array and not empty');
        }
    }
}
