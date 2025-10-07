<?php

declare(strict_types=1);

namespace Settermjd\LaminasPhoneNumberValidator\Factory;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Twilio\Rest\Client;

use function array_key_exists;
use function assert;
use function is_array;
use function is_string;

/**
 * This class simplifies instantiating a Twilio Rest Client object from the application's configuration provided
 *
 * The __invoke method expects the application's configuration to have at least the following structure:
 *
 * [
 *     'twilio' => [
 *         'account_sid' => '',
 *         'auth_token' => '',
 *     ]
 * ]
 */
class TwilioRestClientFactory
{
    public function __invoke(ContainerInterface $container): Client
    {
        if (! $container->has('config')) {
            throw new InvalidArgumentException('Application configuration is missing.');
        }

        $configuration = $container->get('config');
        if (
            ! is_array($configuration)
            || ! array_key_exists('twilio', $configuration)
        ) {
            throw new InvalidArgumentException('Twilio configuration is missing.');
        }

        if (
            ! is_array($configuration['twilio'])
            || empty($configuration['twilio']['account_sid'])
            || empty($configuration['twilio']['auth_token'])
        ) {
            throw new InvalidArgumentException(
                'Twilio configuration is missing either the account SID or the auth token.'
            );
        }

        assert(is_string($configuration['twilio']['account_sid']));
        assert(is_string($configuration['twilio']['auth_token']));
        return new Client(
            $configuration['twilio']['account_sid'],
            $configuration['twilio']['auth_token']
        );
    }
}
