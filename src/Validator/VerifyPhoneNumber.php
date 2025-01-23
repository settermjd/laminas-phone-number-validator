<?php

declare(strict_types=1);

namespace Settermjd\Validator;

use Laminas\Validator\AbstractValidator;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

use function array_filter;
use function array_key_exists;
use function assert;
use function explode;
use function implode;
use function in_array;
use function is_string;
use function preg_match;
use function sprintf;

use const ARRAY_FILTER_USE_BOTH;

final class VerifyPhoneNumber extends AbstractValidator
{
    public const string MSG_INVALID_PHONE_NUMBER   = 'msgInvalidPhoneNumber';
    public const string MSG_NETWORK_LOOKUP_FAILURE = 'msgNetworkLookupFailure';
    public const string REGEX_E164                 = "/^\+[1-9]\d{1,14}$/";

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

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::MSG_NETWORK_LOOKUP_FAILURE => "There was a network error while checking if '%value%' is valid",
        self::MSG_INVALID_PHONE_NUMBER   => "'%value%' is not a valid phone number",
    ];

    /**
     * Stores a validated list of query parameters to pass along with lookup requests
     *
     * @var array<string,string>
     */
    private array $queryParameters = [];

    /**
     * @param array<string,string> $queryParameters
     */
    public function __construct(
        private readonly Client $twilioClient,
        array $queryParameters = [],
        private readonly ?CacheInterface $cache = null
    ) {
        parent::__construct();

        $this->queryParameters = array_filter(
            $queryParameters,
            fn ($value, $key) => in_array($key, self::SUPPORTED_QUERY_PARAMS),
            ARRAY_FILTER_USE_BOTH
        );

        if (array_key_exists('Fields', $this->queryParameters)) {
            $fields                          = array_filter(
                explode(',', $this->queryParameters['Fields']),
                fn ($value, $key) => in_array($value, self::SUPPORTED_FIELDS),
                ARRAY_FILTER_USE_BOTH
            );
            $this->queryParameters['Fields'] = implode(',', $fields);
        }
    }

    /**
     * @return array<string,string>
     */
    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    /**
     * The function checks if the supplied value is valid phone number
     *
     * It first checks the number against Twilio's E.164 regex, and if that passes,
     * it makes a request to Twilio's Lookup API (V2).
     *
     * @link https://www.twilio.com/docs/glossary/what-e164
     * @link https://www.twilio.com/docs/lookup/v2-api
     *
     * @throws InvalidArgumentException
     */
    public function isValid(mixed $value): bool
    {
        assert(is_string($value));
        $this->setValue($value);

        $cacheKey = sprintf("key-%s", $value);
        if ($this->cache?->has($cacheKey)) {
            $isValid = (bool) $this->cache->get($cacheKey);
            if ($isValid) {
                return true;
            }
        }

        if (preg_match(self::REGEX_E164, $value) !== 1) {
            $this->error(self::MSG_INVALID_PHONE_NUMBER);
            $this->cache?->set($cacheKey, false);
            return false;
        }

        try {
            $lookups      = $this->twilioClient->lookups;
            $v2           = $lookups->v2;
            $phoneNumbers = $v2->phoneNumbers($value);
            $phoneNumber  = $phoneNumbers->fetch($this->queryParameters);
        } catch (TwilioException $e) {
            $this->error(self::MSG_NETWORK_LOOKUP_FAILURE);
            $this->cache?->set($cacheKey, false);
            return false;
        }

        if (! $phoneNumber->valid) {
            $this->error(self::MSG_INVALID_PHONE_NUMBER);
            $this->cache?->set($cacheKey, false);
            return false;
        }

        $this->cache?->set($cacheKey, true);
        return true;
    }
}
