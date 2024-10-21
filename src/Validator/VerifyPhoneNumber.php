<?php

declare(strict_types=1);

namespace Settermjd\Validator;

use Laminas\Validator\AbstractValidator;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

use function preg_match;

final class VerifyPhoneNumber extends AbstractValidator
{
    public const string MSG_INVALID_PHONE_NUMBER   = 'msgInvalidPhoneNumber';
    public const string MSG_NETWORK_LOOKUP_FAILURE = 'msgNetworkLookupFailure';

    protected array $messageTemplates = [
        self::MSG_NETWORK_LOOKUP_FAILURE => "There was a network error while checking if '%value%' is valid",
        self::MSG_INVALID_PHONE_NUMBER   => "'%value%' is not a valid phone number",
    ];

    public function __construct(private readonly Client $twilio)
    {
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
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        $this->setValue($value);

        if (preg_match("/^\+[1-9]\d{1,14}$/", (string) $value) !== 1) {
            $this->error(self::MSG_INVALID_PHONE_NUMBER);
            return false;
        }

        try {
            $lookups      = $this->twilio->lookups;
            $v2           = $lookups->v2;
            $phoneNumbers = $v2->phoneNumbers((string) $value);
            $phoneNumber  = $phoneNumbers->fetch();
        } catch (TwilioException $e) {
            $this->error(self::MSG_NETWORK_LOOKUP_FAILURE);
            return false;
        }

        if (! $phoneNumber->valid) {
            $this->error(self::MSG_INVALID_PHONE_NUMBER);
            return false;
        }

        return true;
    }
}
