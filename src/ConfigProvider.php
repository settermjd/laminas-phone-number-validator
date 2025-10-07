<?php

declare(strict_types=1);

namespace Settermjd\LaminasPhoneNumberValidator;

use Settermjd\LaminasPhoneNumberValidator\Factory\TwilioRestClientFactory;
use Settermjd\LaminasPhoneNumberValidator\Validator\VerifyPhoneNumber;
use Settermjd\LaminasPhoneNumberValidator\Validator\VerifyPhoneNumberFactory;
use Twilio\Rest\Client;

/**
 * This class simplifies setting up and providing the package's dependencies and resources
 * when used in a Mezzio application.
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return array<string,array<string,array<class-string,class-string>>>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * @return array<string,array<class-string,class-string>>
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                Client::class            => TwilioRestClientFactory::class,
                VerifyPhoneNumber::class => VerifyPhoneNumberFactory::class,
            ],
        ];
    }
}
