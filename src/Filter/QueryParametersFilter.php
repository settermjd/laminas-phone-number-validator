<?php

declare(strict_types=1);

namespace Settermjd\LaminasPhoneNumberValidator\Filter;

use Laminas\Filter\FilterInterface;

use function array_filter;
use function array_key_exists;
use function explode;
use function implode;
use function in_array;

use const ARRAY_FILTER_USE_BOTH;

class QueryParametersFilter implements FilterInterface
{
    /**
     * Contains the supported query parameters
     */
    public const array SUPPORTED_QUERY_PARAMS = [
        'AddressCountryCode',
        'AddressLine1',
        'AddressLine2',
        'City',
        'CountryCode',
        'DateOfBirth',
        'Fields',
        'FirstName',
        'LastName',
        'LastVerifiedDate',
        'NationalId',
        'PostalCode',
        'State',
        'VerificationSid',
    ];

    /**
     * This contains the query parameter "Field" element's supported values
     */
    public const array SUPPORTED_FIELDS = [
        'call_forwarding',
        'caller_name',
        'identity_match',
        'line_status',
        'line_type_intelligence',
        'phone_number_quality_score',
        'pre_fill',
        'reassigned_number',
        'sim_swap',
        'sms_pumping_risk',
        'validation',
    ];

    /**
     * filter filters out any elements from the supplied array data ($value) that are not supported
     * and unsupported values from the supported elements, such as "Field".
     *
     * @see https://www.twilio.com/docs/lookup/v2-api
     *
     * @param array<string,string> $value
     * @return mixed
     */
    public function filter($value)
    {
        if (empty($value)) {
            return [];
        }

        $filteredQueryParameters = array_filter(
            $value,
            fn ($value, $key) => in_array($key, self::SUPPORTED_QUERY_PARAMS),
            ARRAY_FILTER_USE_BOTH
        );

        if (array_key_exists('Fields', $filteredQueryParameters)) {
            $fields = array_filter(
                explode(',', $filteredQueryParameters['Fields']),
                fn ($value, $key) => in_array($value, self::SUPPORTED_FIELDS),
                ARRAY_FILTER_USE_BOTH
            );

            $filteredQueryParameters['Fields'] = implode(',', $fields);
        }

        return $filteredQueryParameters;
    }
}
