<?php

declare(strict_types=1);

namespace Settermjd\LaminasPhoneNumberValidatorTest\Validator;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Settermjd\LaminasPhoneNumberValidator\InputFilter\QueryParametersInputFilter;
use Settermjd\LaminasPhoneNumberValidator\Validator\VerifyPhoneNumber;
use Settermjd\LaminasPhoneNumberValidator\Validator\VerifyPhoneNumberFactory;
use Twilio\Rest\Client;

class VerifyPhoneNumberFactoryTest extends TestCase
{
    #[TestWith([false])]
    #[TestWith([true])]
    public function testCanInvokeVerifyPhoneNumberProperly(bool $hasCache): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->atMost(3))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $this->createMock(Client::class),
                new QueryParametersInputFilter(),
                $hasCache ? $this->createMock(CacheInterface::class) : null,
            );
        $container
            ->expects($this->atMost(3))
            ->method('has')
            ->with(CacheInterface::class)
            ->willReturn($hasCache);

        $this->assertInstanceOf(
            VerifyPhoneNumber::class,
            (new VerifyPhoneNumberFactory())->__invoke($container)
        );
    }
}
