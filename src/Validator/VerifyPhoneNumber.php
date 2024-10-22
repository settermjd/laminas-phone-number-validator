<?php

declare(strict_types=1);

namespace Settermjd\Validator;

use Laminas\Validator\AbstractValidator;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

use function assert;
use function is_string;
use function preg_match;
use function sprintf;

final class VerifyPhoneNumber extends AbstractValidator
{
    public const string MSG_INVALID_PHONE_NUMBER   = 'msgInvalidPhoneNumber';
    public const string MSG_NETWORK_LOOKUP_FAILURE = 'msgNetworkLookupFailure';
    public const string REGEX_E164                 = "/^\+[1-9]\d{1,14}$/";

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::MSG_NETWORK_LOOKUP_FAILURE => "There was a network error while checking if '%value%' is valid",
        self::MSG_INVALID_PHONE_NUMBER   => "'%value%' is not a valid phone number",
    ];

    public function __construct(
        private readonly Client $twilio,
        private readonly ?CacheInterface $cache = null
    ) {
        parent::__construct();
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
            $lookups      = $this->twilio->lookups;
            $v2           = $lookups->v2;
            $phoneNumbers = $v2->phoneNumbers($value);
            $phoneNumber  = $phoneNumbers->fetch();
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
