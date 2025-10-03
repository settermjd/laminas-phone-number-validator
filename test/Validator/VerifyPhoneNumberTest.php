<?php

declare(strict_types=1);

namespace SettermjdTest\Validator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Settermjd\Exception\InvalidQueryParametersException;
use Settermjd\InputFilter\QueryParametersInputFilter;
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
        /** @var CacheInterface&MockObject $cache */
        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects($this->once())
            ->method("has")
            ->with(sprintf("key-%s", $phoneNumber))
            ->willReturn($itemInCache);

        if ($itemInCache) {
            $cache
                ->expects($this->once())
                ->method("get")
                ->with(sprintf("key-%s", $phoneNumber))
                ->willReturn($phoneNumberIsValid);
            $validator = new VerifyPhoneNumber(
                twilioClient: $this->createMock(Client::class),
                inputFilter: new QueryParametersInputFilter(),
                cache: $cache
            );
            $this->assertSame($phoneNumberIsValid, $validator->isValid($phoneNumber));
            return;
        }

        $cache
            ->expects($this->once())
            ->method("set")
            ->with(sprintf("key-%s", $phoneNumber), $phoneNumberIsValid);

        if ($phoneNumberIsValid) {
            $validator = $this->setupValidator(
                $phoneNumber,
                $phoneNumberIsValid,
                [],
                [],
                $cache
            );
            $this->assertTrue($validator->isValid($phoneNumber));
        } else {
            $validator = new VerifyPhoneNumber(
                twilioClient: $this->createMock(Client::class),
                inputFilter: new QueryParametersInputFilter(),
                cache: $cache
            );
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
        $validator = new VerifyPhoneNumber(
            $this->createMock(Client::class),
            inputFilter: new QueryParametersInputFilter(),
        );

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
        $validator = $this->setupValidator($phoneNumber, $phoneNumberIsValid, []);
        $this->assertSame($phoneNumberIsValid, $validator->isValid($phoneNumber));
        if (! $phoneNumberIsValid) {
            $message = sprintf("'%s' is not a valid phone number", $phoneNumber);
            $this->assertSame(["msgInvalidPhoneNumber" => $message], $validator->getMessages());
        }
    }

    /**
     * @param array<string,string> $queryParameters
     * @param array<string,string> $filteredQueryParameters
     * @throws Exception
     */
    private function setupValidator(
        string $phoneNumber,
        bool $phoneNumberIsValid,
        array $queryParameters = [],
        array $filteredQueryParameters = [],
        ?CacheInterface $cache = null
    ): VerifyPhoneNumber {
        /** @var MockObject $phoneNumberInstance */
        $phoneNumberInstance = $this->createMock(PhoneNumberInstance::class);
        $phoneNumberInstance->expects($this->once())
            ->method("__get")
            ->with("valid")
            ->willReturn($phoneNumberIsValid);

        /** @var MockObject $context */
        $context = $this->createMock(PhoneNumberContext::class);
        if (! empty($queryParameters)) {
            $context->expects($this->once())
                ->method("fetch")
                ->with($filteredQueryParameters)
                ->willReturn($phoneNumberInstance);
        } else {
            $context->expects($this->once())
                ->method("fetch")
                ->willReturn($phoneNumberInstance);
        }

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
        return new VerifyPhoneNumber(
            twilioClient: $client,
            inputFilter: new QueryParametersInputFilter(),
            queryParameters: $queryParameters,
            cache: $cache
        );
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
        $validator = new VerifyPhoneNumber(
            $client,
            inputFilter: new QueryParametersInputFilter(),
        );

        $this->assertFalse($validator->isValid($phoneNumber));
        $message = sprintf("There was a network error while checking if '%s' is valid", $phoneNumber);
        $this->assertSame(["msgNetworkLookupFailure" => $message], $validator->getMessages());
    }

    /**
     * @param array<string,string> $queryParameters
     * @param array<string,array<string,string>> $validationMessages
     */
    #[DataProvider('inValidQueryParameters')]
    public function testThrowsExceptionWhenSettingQueryParametersWhenOneOrMoreParametersAreInvalid(
        array $queryParameters,
        array $validationMessages,
    ): void {
        try {
            $validator = new VerifyPhoneNumber(
                twilioClient: $this->createMock(Client::class),
                inputFilter: new QueryParametersInputFilter(),
            );
            $validator->setQueryParameters($queryParameters);
        } catch (InvalidQueryParametersException $e) {
            $this->assertSame($validationMessages, $e->getValidationMessages());
        }
    }

    /**
     * @return array<int,array<int,array<string,string>|array<string,array<string,string>>>>
     */
    public static function inValidQueryParameters(): array
    {
        return [
            [
                [
                    'addressCountryCode' => 'AU',
                    'addressLine1'       => '1 Nowhere Road',
                    'addressLine2'       => '',
                    'city'               => 'Nowhere',
                    'countryCode'        => 'AU',
                    'dateOfBirth'        => '19700101',
                    'fields'             => 'sim_swap,call_forwarding',
                    'firstName'          => 'Matthew',
                    'lastName'           => 'Setter',
                    'lastVerifiedDate'   => '20240123',
                    'nationalId'         => 'MX12345678',
                    'postalCode'         => '123456',
                    'state'              => 'NW',
                    'verificationSid'    => '0a2bdbcb53a4632ad7c7b17c20000000',
                ],
                [
                    'verificationSid' => [
                        'stringLengthTooShort' => 'The input is less than 34 characters long',
                        'regexNotMatch'        => "The input does not match against pattern '/VA[0-9a-f]{32}/'",
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string,string> $suppliedQueryParameters
     * @param array<string,string> $retrievedQueryParameters
     */
    #[DataProvider('validQueryParameters')]
    public function testCanSetQueryParameters(
        array $suppliedQueryParameters,
        array $retrievedQueryParameters,
    ): void {
        $validator = new VerifyPhoneNumber(
            twilioClient: $this->createMock(Client::class),
            inputFilter: new QueryParametersInputFilter(),
        );
        $validator->setQueryParameters($suppliedQueryParameters);
        $this->assertSame($retrievedQueryParameters, $validator->getQueryParameters());
    }

    /**
     * @return array<int,array<int,array<string,string>>>
     */
    public static function validQueryParameters(): array
    {
        return [
            [
                [
                    'addressCountryCode' => 'AU',
                    'addressLine1'       => '1 Nowhere Road',
                    'addressLine2'       => '',
                    'city'               => 'Nowhere',
                    'countryCode'        => 'AU',
                    'dateOfBirth'        => '19700101',
                    'fields'             => 'sim_swap,call_forwarding',
                    'firstName'          => 'Matthew',
                    'lastName'           => 'Setter',
                    'lastVerifiedDate'   => '20240123',
                    'nationalId'         => 'MX12345678',
                    'postalCode'         => '123456',
                    'state'              => 'NW',
                    'verificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
                ],
                [
                    'addressCountryCode' => 'AU',
                    'addressLine1'       => '1 Nowhere Road',
                    'city'               => 'Nowhere',
                    'countryCode'        => 'AU',
                    'dateOfBirth'        => '19700101',
                    'fields'             => 'sim_swap,call_forwarding',
                    'firstName'          => 'Matthew',
                    'lastName'           => 'Setter',
                    'lastVerifiedDate'   => '20240123',
                    'nationalId'         => 'MX12345678',
                    'postalCode'         => '123456',
                    'state'              => 'NW',
                    'verificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
                ],
            ],
            [
                [
                    'AddressCountryCode' => 'AU',
                    'AddressLine1'       => '1 Nowhere Road',
                    'City'               => 'Nowhere',
                    'CountryCode'        => 'AU',
                    'DateOfBirth'        => '19700101',
                    'Fields'             => 'sim_swap,call_forwarding',
                    'FirstName'          => 'Matthew',
                    'LastName'           => 'Setter',
                    'LastVerifiedDate'   => '20240123',
                    'NationalId'         => 'MX12345678',
                    'PostalCode'         => '123456',
                    'State'              => 'NW',
                    'VerificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
                ],
                [],
            ],
        ];
    }
}
