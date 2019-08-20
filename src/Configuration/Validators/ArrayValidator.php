<?php

namespace CodeCrafting\AdoLDAP\Configuration\Validators;

use CodeCrafting\AdoLDAP\Configuration\ConfigurationException;

/**
 * Class ArrayValidator.
 *
 * Validates that the configuration as a an array.
 */
class ArrayValidator extends Validator
{
    /**
     * {@inheritdoc}
     */
    public function validate()
    {
        if (! is_array($this->value)) {
            throw new ConfigurationException("Option {$this->key} must be an array.");
        }

        return true;
    }
}
