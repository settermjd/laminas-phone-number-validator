<?php

declare(strict_types=1);

namespace Settermjd\LaminasPhoneNumberValidator\Validator;

use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Settermjd\LaminasPhoneNumberValidator\InputFilter\QueryParametersInputFilter;
use Twilio\Rest\Client;

/**
 * VerifyPhoneNumberFactory is a factory class that returns an instantiated VerifyPhoneNumber instance
 * avoiding the need to do so manually in code anywhere in the package, or anywhere in code that uses
 * this package.
 *
 * It is, however, mostly designed to be used with a DI/service container, such as laminas-servicemanager
 * and in Mezzio applications, specifically, through this package's ConfigProvider class.
 */
class VerifyPhoneNumberFactory
{
    public function __invoke(ContainerInterface $container): VerifyPhoneNumber
    {
        return new VerifyPhoneNumber(
            $container->get(Client::class),
            $container->get(QueryParametersInputFilter::class),
            [],
            $container->has(CacheInterface::class)
                ? $container->get(CacheInterface::class)
                : null,
        );
    }
}
