<?php

declare(strict_types=1);

namespace SettermjdTest\Validator;

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
        $validator = new VerifyPhoneNumber($client);

        $this->assertSame($phoneNumberIsValid, $validator->isValid($phoneNumber));
        if (! $phoneNumberIsValid) {
            $message = sprintf("'%s' is not a valid phone number", $phoneNumber);
            $this->assertSame(["msgInvalidPhoneNumber" => $message], $validator->getMessages());
        }
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
