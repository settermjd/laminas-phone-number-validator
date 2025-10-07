<?php

declare(strict_types=1);

namespace Settermjd\LaminasPhoneNumberValidatorTest\InputFilter;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Settermjd\LaminasPhoneNumberValidator\InputFilter\QueryParametersInputFilter;

use function array_filter;
use function var_export;

use const ARRAY_FILTER_USE_BOTH;

class QueryParametersInputFilterTest extends TestCase
{
    /**
     * @param array<string,string> $suppliedQueryParameters
     * @param array<string,string> $filteredQueryParameters
     */
    #[TestWith([
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
        true,
    ])]
    #[TestWith([
        [
            'countryCode'        => 'AU',
            'firstName'          => 'Matthew',
            'lastName'           => 'Setter',
            'addressLine1'       => '1 Nowhere Road',
            'addressLine2'       => '',
            'city'               => 'Nowhere',
            'state'              => 'NW',
            'postCode'           => '123456',
            'addressCountryCode' => 'AU',
            'nationalId'         => 'MX12345678',
            'dateOfBirth'        => '19700101',
            'lastVerifiedDate'   => '20240123',
            'verificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
            'fields'             => 'sim_swap,call_forwarding',
        ],
        [
            'countryCode'        => 'AU',
            'firstName'          => 'Matthew',
            'lastName'           => 'Setter',
            'addressLine1'       => '1 Nowhere Road',
            'city'               => 'Nowhere',
            'state'              => 'NW',
            'addressCountryCode' => 'AU',
            'nationalId'         => 'MX12345678',
            'dateOfBirth'        => '19700101',
            'lastVerifiedDate'   => '20240123',
            'verificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
            'fields'             => 'sim_swap,call_forwarding',
        ],
        true,
    ])]
    #[TestWith([
        [
            'countryCode'        => 'AU',
            'firstName'          => 'Matthew',
            'lastName'           => 'Setter',
            'addressLine1'       => '1 Nowhere Road',
            'addressLine2'       => '',
            'city'               => 'Nowhere',
            'state'              => 'NW',
            'postCode'           => '123456',
            'addressCountryCode' => 'AU',
            'nationalId'         => 'MX12345678',
            'dateOfBirth'        => '19700101',
            'lastVerifiedDate'   => '20240123',
            'verificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
            'fields'             => 'sam_swap,call_forwarding',
        ],
        [
            'countryCode'        => 'AU',
            'firstName'          => 'Matthew',
            'lastName'           => 'Setter',
            'addressLine1'       => '1 Nowhere Road',
            'city'               => 'Nowhere',
            'state'              => 'NW',
            'addressCountryCode' => 'AU',
            'nationalId'         => 'MX12345678',
            'dateOfBirth'        => '19700101',
            'lastVerifiedDate'   => '20240123',
            'verificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
            'fields'             => 'call_forwarding',
        ],
        true,
    ])]
    #[TestWith([
        [
            'countryCode'        => 'AU',
            'firstName'          => 'Matthew',
            'lastName'           => 'Setter',
            'addressLine1'       => '1 Nowhere Road',
            'addressLine2'       => '',
            'city'               => 'Nowhere',
            'state'              => 'NW',
            'postCode'           => '123456',
            'addressCountryCode' => 'AU',
            'nationalId'         => 'MX12345678',
            'dateOfBirth'        => '19700101',
            'lastVerifiedDate'   => '20240123',
            'verificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
        ],
        [
            'countryCode'        => 'AU',
            'firstName'          => 'Matthew',
            'lastName'           => 'Setter',
            'addressLine1'       => '1 Nowhere Road',
            'city'               => 'Nowhere',
            'state'              => 'NW',
            'addressCountryCode' => 'AU',
            'nationalId'         => 'MX12345678',
            'dateOfBirth'        => '19700101',
            'lastVerifiedDate'   => '20240123',
            'verificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
        ],
        true,
    ])]
    #[TestWith([[], [], true])]
    public function testCanFilterDataCorrectly(
        array $suppliedQueryParameters,
        array $filteredQueryParameters,
        bool $isValid
    ): void {
        $inputFilter = new QueryParametersInputFilter();
        $inputFilter->setData($suppliedQueryParameters);
        $this->assertSame($isValid, $inputFilter->isValid(), var_export($inputFilter->getMessages(), true));

        /** @var array<string,string|null> $data */
        $data = $inputFilter->getValues();
        $this->assertEquals(
            $filteredQueryParameters,
            array_filter(
                $data,
                fn ($value, $key) => $value !== null && $value !== '',
                ARRAY_FILTER_USE_BOTH
            )
        );
    }
}
