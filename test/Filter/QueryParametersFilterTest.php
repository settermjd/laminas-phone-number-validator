<?php

declare(strict_types=1);

namespace Settermjd\LaminasPhoneNumberValidatorTest\Filter;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Settermjd\LaminasPhoneNumberValidator\Filter\QueryParametersFilter;

class QueryParametersFilterTest extends TestCase
{
    /**
     * @param array<string,string> $suppliedQueryParameters
     * @param array<string,string> $filteredQueryParameters
     * @throws Exception
     */
    #[TestWith([
        [
            'CountryCode'        => 'AU',
            'FirstName'          => 'Matthew',
            'LastName'           => 'Setter',
            'AddressLine1'       => '1 Nowhere Road',
            'AddressLine2'       => '',
            'City'               => 'Nowhere',
            'State'              => 'NW',
            'PostalCode'         => '123456',
            'AddressCountryCode' => 'AU',
            'NationalId'         => 'MX12345678',
            'DateOfBirth'        => '19700101',
            'LastVerifiedDate'   => '20240123',
            'VerificationSid'    => 'SX12345678',
            'Fields'             => 'sim_swap,call_forwarding',
        ],
        [
            'CountryCode'        => 'AU',
            'FirstName'          => 'Matthew',
            'LastName'           => 'Setter',
            'AddressLine1'       => '1 Nowhere Road',
            'AddressLine2'       => '',
            'City'               => 'Nowhere',
            'State'              => 'NW',
            'PostalCode'         => '123456',
            'AddressCountryCode' => 'AU',
            'NationalId'         => 'MX12345678',
            'DateOfBirth'        => '19700101',
            'LastVerifiedDate'   => '20240123',
            'VerificationSid'    => 'SX12345678',
            'Fields'             => 'sim_swap,call_forwarding',
        ],
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
            'VerificationSid'    => 'SX12345678',
            'Fields'             => 'sim_swap,call_forwarding',
        ],
        [
            'CountryCode'        => 'AU',
            'FirstName'          => 'Matthew',
            'LastName'           => 'Setter',
            'AddressLine1'       => '1 Nowhere Road',
            'AddressLine2'       => '',
            'City'               => 'Nowhere',
            'State'              => 'NW',
            'AddressCountryCode' => 'AU',
            'NationalId'         => 'MX12345678',
            'DateOfBirth'        => '19700101',
            'LastVerifiedDate'   => '20240123',
            'VerificationSid'    => 'SX12345678',
            'Fields'             => 'sim_swap,call_forwarding',
        ],
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
            'VerificationSid'    => 'SX12345678',
            'Fields'             => 'sam_swap,call_forwarding',
        ],
        [
            'CountryCode'        => 'AU',
            'FirstName'          => 'Matthew',
            'LastName'           => 'Setter',
            'AddressLine1'       => '1 Nowhere Road',
            'AddressLine2'       => '',
            'City'               => 'Nowhere',
            'State'              => 'NW',
            'AddressCountryCode' => 'AU',
            'NationalId'         => 'MX12345678',
            'DateOfBirth'        => '19700101',
            'LastVerifiedDate'   => '20240123',
            'VerificationSid'    => 'SX12345678',
            'Fields'             => 'call_forwarding',
        ],
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
            'VerificationSid'    => 'SX12345678',
        ],
        [
            'CountryCode'        => 'AU',
            'FirstName'          => 'Matthew',
            'LastName'           => 'Setter',
            'AddressLine1'       => '1 Nowhere Road',
            'AddressLine2'       => '',
            'City'               => 'Nowhere',
            'State'              => 'NW',
            'AddressCountryCode' => 'AU',
            'NationalId'         => 'MX12345678',
            'DateOfBirth'        => '19700101',
            'LastVerifiedDate'   => '20240123',
            'VerificationSid'    => 'SX12345678',
        ],
    ])]
    #[TestWith([[], []])]
    public function testCanFilterOutInvalidQueryParameters(
        array $suppliedQueryParameters,
        array $filteredQueryParameters
    ): void {
        $this->assertSame($filteredQueryParameters, (new QueryParametersFilter())->filter($suppliedQueryParameters));
    }
}
