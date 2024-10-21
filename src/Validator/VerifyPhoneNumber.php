<?php

declare(strict_types=1);

namespace Settermjd\Validator;

use Laminas\Validator\AbstractValidator;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;
use Twilio\Rest\Lookups;

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
     * @param mixed $value
     * @return bool
     */
    public function isValid($value)
    {
        $this->setValue($value);

        try {
            $lookups = $this->twilio->__get("lookups");
            /** @var Lookups\V2 $v2 */
            $v2           = $lookups->__call("getV2", []);
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
