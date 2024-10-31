<?php

declare(strict_types=1);

namespace SimpleSAML\Module\casserver\Cas;

use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Module;

/**
 * Extract the user and any mapped attributes from the AuthSource attributes
 */
class AttributeExtractor
{
    /**
     * Determine the user and any CAS attributes based on the attributes from the
     * authsource and the CAS configuration.
     *
     * The result is an array
     * [
     *   'user' => 'user_value',
     *   'attributes' => [
     *    // any attributes
     * ]
     *
     * If no CAS attributes are configured then the attributes array is empty
     * @param array $attributes
     * @param \SimpleSAML\Configuration $casconfig
     * @return array
     */
    public function extractUserAndAttributes(array $attributes, Configuration $casconfig): array
    {
        if ($casconfig->hasValue('authproc')) {
            $attributes = $this->invokeAuthProc($attributes, $casconfig);
        }

        $casUsernameAttribute = $casconfig->getOptionalValue('attrname', 'eduPersonPrincipalName');

        $userName = $attributes[$casUsernameAttribute][0];
        if (empty($userName)) {
            throw new \Exception("No cas user defined for attribute $casUsernameAttribute");
        }

        if ($casconfig->getOptionalValue('attributes', true)) {
            $attributesToTransfer = $casconfig->getOptionalValue('attributes_to_transfer', []);

            if (sizeof($attributesToTransfer) > 0) {
                $casAttributes = [];

                foreach ($attributesToTransfer as $key) {
                    if (array_key_exists($key, $attributes)) {
                        $casAttributes[$key] = $attributes[$key];
                    }
                }
            } else {
                $casAttributes = $attributes;
            }
        } else {
            $casAttributes = [];
        }

        return [
            'user' => $userName,
            'attributes' => $casAttributes,
        ];
    }


    /**
     * Process any authproc filters defined in the configuration. The Authproc filters must only
     * rely on 'Attributes' being available and not on additional SAML state.
     * @see \SimpleSAML_Auth_ProcessingChain::parseFilter() For the original, SAML side implementation
     * @param array $attributes The current attributes
     * @param \SimpleSAML\Configuration $casconfig The cas configuration
     * @return array The attributes post processing.
     */
    private function invokeAuthProc(array $attributes, Configuration $casconfig): array
    {
        $filters = $casconfig->getOptionalArray('authproc', []);

        $state = [
            'Attributes' => $attributes,
        ];
        foreach ($filters as $config) {
            $className = Module::resolveClass(
                $config['class'],
                'Auth\Process',
                Auth\ProcessingFilter::class,
            );
            // Unset 'class' to prevent the filter from interpreting it as an option
            unset($config['class']);
            /** @psalm-suppress InvalidStringClass */
            $filter = new $className($config, null);
            $filter->process($state);
        }

        return $state['Attributes'];
    }
}
