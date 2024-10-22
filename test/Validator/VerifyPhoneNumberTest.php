<?php

declare(strict_types=1);

namespace SettermjdTest\Validator;

use Laminas\Cache\Storage\StorageInterface;
use Laminas\Validator\ValidatorInterface;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Settermjd\Validator\VerifyPhoneNumber;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Twilio\Rest\Lookups;
use Twilio\Rest\Lookups\V2;
use Twilio\Rest\Lookups\V2\PhoneNumberContext;
use Twilio\Rest\Lookups\V2\PhoneNumberInstance;

use function assert;
use function sprintf;

class VerifyPhoneNumberTest extends TestCase
{
    #[TestWith(['+61000000000', true, true])]
    #[TestWith(['+61000000000', false, true])]
    #[TestWith(['61', false, false])]
    #[TestWith(['61', true, false])]
    public function testCanCacheValidationChecks(string $phoneNumber, bool $itemInCache, bool $phoneNumberIsValid): void
    {
        /** @var StorageInterface&MockObject $cache */
        $cache = $this->createMock(StorageInterface::class);
        $cache
            ->expects($this->once())
            ->method("hasItem")
            ->with(sprintf("key-%s", $phoneNumber))
            ->willReturn($itemInCache);

        if ($itemInCache) {
            $cache
                ->expects($this->once())
                ->method("getItem")
                ->with(sprintf("key-%s", $phoneNumber))
                ->willReturn($phoneNumberIsValid);
            $validator = new VerifyPhoneNumber($this->createMock(Client::class), $cache);
            $this->assertSame($phoneNumberIsValid, $validator->isValid($phoneNumber));
            return;
        }

        $cache
            ->expects($this->once())
            ->method("setItem")
            ->with(sprintf("key-%s", $phoneNumber), $phoneNumberIsValid);

        if ($phoneNumberIsValid) {
            $validator = $this->setupValidator($phoneNumber, $phoneNumberIsValid, $cache);
            $this->assertTrue($validator->isValid($phoneNumber));
        } else {
            $validator = new VerifyPhoneNumber($this->createMock(Client::class), $cache);
            $this->assertFalse($validator->isValid($phoneNumber));
            $this->assertSame(
                [
                    "msgInvalidPhoneNumber" => sprintf("'%s' is not a valid phone number", $phoneNumber),
                ],
                $validator->getMessages()
            );
        }
    }

    #[TestWith(['+61000a00000'])]
    #[TestWith(['+61000@00000'])]
    #[TestWith(['61000000000'])]
    #[TestWith(['-61000000000'])]
    #[TestWith(['61'])]
    public function testPhoneNumbersMustPassE164Regex(string $invalidPhoneNumber): void
    {
        $validator = new VerifyPhoneNumber($this->createMock(Client::class));

        $this->assertFalse($validator->isValid($invalidPhoneNumber));
        $this->assertSame(
            [
                "msgInvalidPhoneNumber" => sprintf(
                    "'%s' is not a valid phone number",
                    $invalidPhoneNumber
                ),
            ],
            $validator->getMessages()
        );
    }

    #[TestWith(['+61000000000', true])]
    #[TestWith(['+61', false])]
    public function testValidPhoneNumbersValidateSuccessfully(string $phoneNumber, bool $phoneNumberIsValid): void
    {
        $validator = $this->setupValidator($phoneNumber, $phoneNumberIsValid);

        $this->assertSame($phoneNumberIsValid, $validator->isValid($phoneNumber));
        if (! $phoneNumberIsValid) {
            $message = sprintf("'%s' is not a valid phone number", $phoneNumber);
            $this->assertSame(["msgInvalidPhoneNumber" => $message], $validator->getMessages());
        }
    }

    private function setupValidator(
        string $phoneNumber,
        bool $phoneNumberIsValid,
        ?StorageInterface $cache = null
    ): ValidatorInterface {
        /** @var MockObject $phoneNumberInstance */
        $phoneNumberInstance = $this->createMock(PhoneNumberInstance::class);
        $phoneNumberInstance->expects($this->once())
            ->method("__get")
            ->with("valid")
            ->willReturn($phoneNumberIsValid);

        /** @var MockObject $context */
        $context = $this->createMock(PhoneNumberContext::class);
        $context->expects($this->once())
            ->method("fetch")
            ->willReturn($phoneNumberInstance);

        /** @var MockObject $v2 */
        $v2 = $this->createMock(V2::class);
        $v2->expects($this->once())
            ->method("__call")
            ->with("phoneNumbers", [$phoneNumber])
            ->willReturn($context);

        /** @var MockObject $lookups */
        $lookups = $this->createMock(Lookups::class);
        $lookups->expects($this->once())
            ->method("__get")
            ->with("v2")
            ->willReturn($v2);

        /** @var MockObject $client */
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method("__get")
            ->with("lookups")
            ->willReturn($lookups);

        assert($client instanceof Client);
        return new VerifyPhoneNumber($client, $cache);
    }

    public function testCanHandleExceptionsWhileQueryingTheLookupAPI(): void
    {
        $phoneNumber = '+61000000000';

        /** @var MockObject $context */
        $context = $this->createMock(PhoneNumberContext::class);
        $context->expects($this->once())
            ->method("fetch")
            ->willThrowException(new TwilioException("Unable to fetch record"));

        /** @var MockObject $v2 */
        $v2 = $this->createMock(V2::class);
        $v2->expects($this->once())
            ->method("__call")
            ->with("phoneNumbers", [$phoneNumber])
            ->willReturn($context);

        /** @var MockObject $lookups */
        $lookups = $this->createMock(Lookups::class);
        $lookups->expects($this->once())
            ->method("__get")
            ->with("v2")
            ->willReturn($v2);

        /** @var MockObject $client */
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method("__get")
            ->with("lookups")
            ->willReturn($lookups);

        assert($client instanceof Client);
        $validator = new VerifyPhoneNumber($client);

        $this->assertFalse($validator->isValid($phoneNumber));
        $message = sprintf("There was a network error while checking if '%s' is valid", $phoneNumber);
        $this->assertSame(["msgNetworkLookupFailure" => $message], $validator->getMessages());
    }
}
