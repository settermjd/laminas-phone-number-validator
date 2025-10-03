<?php

declare(strict_types=1);

namespace Settermjd\InputFilter;

use Laminas\Filter\AllowList;
use Laminas\Filter\StringToUpper;
use Laminas\Filter\StripNewlines;
use Laminas\Filter\StripTags;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Date;
use Laminas\Validator\Explode;
use Laminas\Validator\InArray;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

/**
 * @extends InputFilter<QueryParametersInputFilter>
 */
class QueryParametersInputFilter extends InputFilter
{
    /**
     * This contains the query parameter "Field" element's supported values
     *
     * @var array<int,string>
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

    public function __construct()
    {
        $addressCountryCode = new Input("addressCountryCode");
        $addressCountryCode->setRequired(false);
        $addressCountryCode
            ->getValidatorChain()
                ->attachByName('stringLength', [
                    'min' => 2,
                    'max' => 2,
                ]);
        $addressCountryCode
            ->getFilterChain()
            ->attach(new StringToUpper())
            ->attach(new StripNewlines())
            ->attach(new StripTags());

        $addressLine1 = new Input("addressLine1");
        $addressLine1->setRequired(false);
        $addressLine1
            ->getFilterChain()
            ->attach(new StripNewlines())
            ->attach(new StripTags());

        $addressLine2 = new Input("addressLine2");
        $addressLine2->setRequired(false);
        $addressLine2
            ->getFilterChain()
            ->attach(new StripNewlines())
            ->attach(new StripTags());

        $city = new Input("city");
        $city->setRequired(false);
        $city
            ->getFilterChain()
            ->attach(new StripNewlines())
            ->attach(new StripTags());

        $countryCode = new Input("countryCode");
        $countryCode->setRequired(false);
        $countryCode->setRequired(false);
        $countryCode
            ->getValidatorChain()
            ->attachByName('stringLength', [
                'min' => 2,
                'max' => 2,
            ]);
        $countryCode
            ->getFilterChain()
            ->attach(new StringToUpper())
            ->attach(new StripNewlines())
            ->attach(new StripTags());

        $dateOfBirth = new Input("dateOfBirth");
        $dateOfBirth->setRequired(false);
        $dateOfBirth
            ->getValidatorChain()
            ->attach(new Date([
                'format' => 'Ymd',
                'strict' => true,
            ]));
        $dateOfBirth
            ->getFilterChain()
            ->attach(new StripNewlines())
            ->attach(new StripTags());

        $fields = new Input("fields");
        $fields->setRequired(false);
        $fields
            ->getFilterChain()
            ->attach(new StripNewlines())
            ->attach(new StripTags())
            ->attach(new \Settermjd\Filter\Explode([
                'filter' => new AllowList([
                    'list' => self::SUPPORTED_FIELDS,
                ]),
            ]));
        $fields
            ->getValidatorChain()
            ->attach(new Explode([
                'validator' => new InArray([
                    'haystack' => self::SUPPORTED_FIELDS,
                ]),
            ]));

        $firstName = new Input("firstName");
        $firstName->setRequired(false);
        $firstName
            ->getFilterChain()
            ->attach(new StripNewlines())
            ->attach(new StripTags());

        $lastName = new Input("lastName");
        $lastName->setRequired(false);
        $lastName
            ->getFilterChain()
            ->attach(new StripNewlines())
            ->attach(new StripTags());

        $lastVerificationDate = new Input("lastVerifiedDate");
        $lastVerificationDate->setRequired(false);
        $lastVerificationDate
            ->getFilterChain()
            ->attach(new StripNewlines())
            ->attach(new StripTags());
        $lastVerificationDate
            ->getValidatorChain()
            ->attach(new Date([
                'format' => 'Ymd',
                'strict' => true,
            ]));

        $nationalId = new Input("nationalId");
        $nationalId->setRequired(false);
        $nationalId
            ->getFilterChain()
            ->attach(new StripNewlines())
            ->attach(new StripTags());

        $postCode = new Input("postalCode");
        $postCode->setRequired(false);
        $postCode
            ->getFilterChain()
            ->attach(new StripNewlines())
            ->attach(new StripTags());

        $state = new Input("state");
        $state->setRequired(false);
        $state
            ->getFilterChain()
            ->attach(new StripNewlines())
            ->attach(new StripTags());

        $verificationSid = new Input("verificationSid");
        $verificationSid->setRequired(false);
        $verificationSid->setAllowEmpty(true);
        $verificationSid
            ->getValidatorChain()
            ->attach(new StringLength([
                'min' => 34,
                'max' => 34,
            ]))
            ->attach(new Regex("/VA[0-9a-f]{32}/"));
        $verificationSid
            ->getFilterChain()
            ->attach(new StripNewlines())
            ->attach(new StripTags());

        $this->add($addressCountryCode);
        $this->add($addressLine1);
        $this->add($addressLine2);
        $this->add($city);
        $this->add($countryCode);
        $this->add($dateOfBirth);
        $this->add($fields);
        $this->add($firstName);
        $this->add($lastName);
        $this->add($lastVerificationDate);
        $this->add($nationalId);
        $this->add($postCode);
        $this->add($state);
        $this->add($verificationSid);
    }
}
