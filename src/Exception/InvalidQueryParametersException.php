<?php

declare(strict_types=1);

namespace Settermjd\LaminasPhoneNumberValidator\Exception;

use LogicException;
use Throwable;

/**
 * InvalidQueryParametersException is a custom exception that stores validation messages
 * when an input filter fails validation.
 *
 * @see https://docs.laminas.dev/laminas-validator/v3/intro/
 */
class InvalidQueryParametersException extends LogicException
{
    /**
     * @param string $message
     * @param int $code
     * @param Throwable $previous
     * @param array<string,array<string,string>> $validationMessages
     */
    public function __construct(
        protected $message = "",
        protected $code = 0,
        private $previous = null,
        private readonly array $validationMessages = [],
    ) {
        parent::__construct($this->message, $this->code, $this->previous);
    }

    /**
     * getValidationMessages returns a nested array of error messages
     *
     * The array matches that returned by \Laminas\InputFilter\BaseInputFilter::getMessages())
     * where the array key is a string, the name of the property that failed validation, and
     * the array value is an associative array of validation errors.
     *
     * Here is an example:
     *
     * [
     *     'verificationSid' => [
     *         'stringLengthTooShort' => 'The input is less than 34 characters long',
     *         'regexNotMatch'        => "The input does not match against pattern '/VA[0-9a-f]{32}/'",
     *     ],
     * ],
     *
     * @return array<string,array<string,string>>
     */
    public function getValidationMessages(): array
    {
        return $this->validationMessages;
    }
}
