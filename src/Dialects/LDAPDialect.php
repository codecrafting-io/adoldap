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
    public function getCommand($filter = null, $attributes = [], string $context = null)
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

        //dump($filter, $attributes, $context, $command);

        return $command;
    }
}
