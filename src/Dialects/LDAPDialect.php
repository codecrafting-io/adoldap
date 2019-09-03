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
     * {@inheritdoc}
     */
    public function getCommand($filter, $attributes, string $context = null)
    {
        if ((is_array($attributes) || is_string($attributes)) && ! empty($attributes)) {
            $adPath = parent::PROTOCOL;
            if ($this->isSsl()) {
                $adPath = parent::SSL_PROTOCOL;
            }
            if ($this->host && $this->baseDn != DialectInterface::ROOT_DN) {
                $adPath .= $this->host;
                if ($this->port != parent::PORT) {
                    $adPath .= ':' . $this->port;
                }
                $adPath .= '/';
            }
            $adPath .= $this->baseDn;
            $attributes = implode(',', $attributes);
            $command = "<{$adPath}>";
            if ($filter) {
                $command .= ";{$filter}";
            }
            if ($attributes) {
                $command .= ";{$attributes}";
            }
            if ($context) {
                $command .= ";{$context}";
            } else {
                $command .= ";subtree";
            }

            return $command;
        } else {
            throw new DialectException('attributes must be a string or array and not empty');
        }
    }
}
