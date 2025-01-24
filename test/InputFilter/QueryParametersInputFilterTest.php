<?php

declare(strict_types=1);

namespace SettermjdTest\InputFilter;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Settermjd\InputFilter\QueryParametersInputFilter;

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
            'AddressCountryCode' => 'AU',
            'AddressLine1'       => '1 Nowhere Road',
            'AddressLine2'       => '',
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
        true,
    ])]
    #[TestWith([
        [
            'CountryCode'        => 'AU',
            'FirstName'          => 'Matthew',
            'LastName'           => 'Setter',
            'AddressLine1'       => '1 Nowhere Road',
            'AddressLine2'       => '',
            'City'               => 'Nowhere',
            'State'              => 'NW',
            'PostCode'           => '123456',
            'AddressCountryCode' => 'AU',
            'NationalId'         => 'MX12345678',
            'DateOfBirth'        => '19700101',
            'LastVerifiedDate'   => '20240123',
            'VerificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
            'Fields'             => 'sim_swap,call_forwarding',
        ],
        [
            'CountryCode'        => 'AU',
            'FirstName'          => 'Matthew',
            'LastName'           => 'Setter',
            'AddressLine1'       => '1 Nowhere Road',
            'City'               => 'Nowhere',
            'State'              => 'NW',
            'AddressCountryCode' => 'AU',
            'NationalId'         => 'MX12345678',
            'DateOfBirth'        => '19700101',
            'LastVerifiedDate'   => '20240123',
            'VerificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
            'Fields'             => 'sim_swap,call_forwarding',
        ],
        true,
    ])]
    #[TestWith([
        [
            'CountryCode'        => 'AU',
            'FirstName'          => 'Matthew',
            'LastName'           => 'Setter',
            'AddressLine1'       => '1 Nowhere Road',
            'AddressLine2'       => '',
            'City'               => 'Nowhere',
            'State'              => 'NW',
            'PostCode'           => '123456',
            'AddressCountryCode' => 'AU',
            'NationalId'         => 'MX12345678',
            'DateOfBirth'        => '19700101',
            'LastVerifiedDate'   => '20240123',
            'VerificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
            'Fields'             => 'sam_swap,call_forwarding',
        ],
        [
            'CountryCode'        => 'AU',
            'FirstName'          => 'Matthew',
            'LastName'           => 'Setter',
            'AddressLine1'       => '1 Nowhere Road',
            'City'               => 'Nowhere',
            'State'              => 'NW',
            'AddressCountryCode' => 'AU',
            'NationalId'         => 'MX12345678',
            'DateOfBirth'        => '19700101',
            'LastVerifiedDate'   => '20240123',
            'VerificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
            'Fields'             => 'call_forwarding',
        ],
        true,
    ])]
    #[TestWith([
        [
            'CountryCode'        => 'AU',
            'FirstName'          => 'Matthew',
            'LastName'           => 'Setter',
            'AddressLine1'       => '1 Nowhere Road',
            'AddressLine2'       => '',
            'City'               => 'Nowhere',
            'State'              => 'NW',
            'PostCode'           => '123456',
            'AddressCountryCode' => 'AU',
            'NationalId'         => 'MX12345678',
            'DateOfBirth'        => '19700101',
            'LastVerifiedDate'   => '20240123',
            'VerificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
        ],
        [
            'CountryCode'        => 'AU',
            'FirstName'          => 'Matthew',
            'LastName'           => 'Setter',
            'AddressLine1'       => '1 Nowhere Road',
            'City'               => 'Nowhere',
            'State'              => 'NW',
            'AddressCountryCode' => 'AU',
            'NationalId'         => 'MX12345678',
            'DateOfBirth'        => '19700101',
            'LastVerifiedDate'   => '20240123',
            'VerificationSid'    => 'VA0a2bdbcb53a4632ad7c7b17c20000000',
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
