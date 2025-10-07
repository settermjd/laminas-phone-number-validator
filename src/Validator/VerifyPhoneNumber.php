<?php

declare(strict_types=1);

namespace Settermjd\LaminasPhoneNumberValidator\Validator;

use Laminas\Validator\AbstractValidator;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Settermjd\LaminasPhoneNumberValidator\Exception\InvalidQueryParametersException;
use Settermjd\LaminasPhoneNumberValidator\InputFilter\QueryParametersInputFilter;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

use function array_filter;
use function assert;
use function is_string;
use function preg_match;
use function sprintf;

/**
 * VerifyPhoneNumber is a custom laminas-validator class that checks if a phone number is valid by using
 * a combination of Twilio's E.164 regular expression and Lookup (V2) API. This combination provides a
 * simple way of validating phone numbers are valid.
 */
final class VerifyPhoneNumber extends AbstractValidator
{
    public const string MSG_INVALID_PHONE_NUMBER   = 'msgInvalidPhoneNumber';
    public const string MSG_NETWORK_LOOKUP_FAILURE = 'msgNetworkLookupFailure';

    /**
     * This is Twilio's E.164 regular expression
     *
     * @see https://www.twilio.com/docs/glossary/what-e164#regex-matching-for-e164
     */
    public const string REGEX_E164 = "/^\+[1-9]\d{1,14}$/";

    /**
     * An array of one or more query parameters supported by Twilio's Lookup (V2) API
     *
     * @see https://www.twilio.com/docs/lookup/v2-api#query-parameters-1
     *
     * @var array<string,string>
     */
    private array $queryParameters = [];

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::MSG_NETWORK_LOOKUP_FAILURE => "There was a network error while checking if '%value%' is valid",
        self::MSG_INVALID_PHONE_NUMBER   => "'%value%' is not a valid phone number",
    ];

    /**
     * @param array<string,string> $queryParameters
     */
    public function __construct(
        private readonly Client $twilioClient,
        private readonly QueryParametersInputFilter $inputFilter,
        array $queryParameters = [],
        private readonly ?CacheInterface $cache = null
    ) {
        parent::__construct();

        $this->setQueryParameters($queryParameters);
    }

    /**
     * The function checks if the supplied value is valid phone number
     *
     * It first checks the number against Twilio's E.164 regex, and if that passes,
     * it makes a request to Twilio's Lookup API (V2).
     *
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

    /**
     * setQueryParameters sets query parameters for requests to the Lookup (V2) API.
     *
     * @param array<string,string> $queryParameters
     */
    public function setQueryParameters(array $queryParameters = []): void
    {
        $this->inputFilter->setData($queryParameters);
        if (! $this->inputFilter->isValid()) {
            throw new InvalidQueryParametersException(
                message: 'Invalid query parameters.',
                validationMessages: $this->inputFilter->getMessages(),
            );
        }

        if ($this->inputFilter->getValues() === []) {
            return;
        }
        $this->queryParameters = array_filter($this->inputFilter->getValues());
    }

    /**
     * getQueryParameters returns the currently set query parameters for the Lookup (V2) API
     *
     * @return array<string,string>
     */
    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }
}
