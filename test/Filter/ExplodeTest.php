<?php

declare(strict_types=1);

namespace Settermjd\LaminasPhoneNumberValidatorTest\Filter;

use Laminas\Filter\AllowList;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Settermjd\LaminasPhoneNumberValidator\Filter\Explode;

class ExplodeTest extends TestCase
{
    public const array ALLOWED_VALUES = [
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
     * @param array<int,string> $allowedValues
     */
    #[TestWith([self::ALLOWED_VALUES, 'sam_swap,call_forwarding', 'call_forwarding'])]
    #[TestWith([self::ALLOWED_VALUES, '', ''])]
    #[TestWith([self::ALLOWED_VALUES, null, ''])]
    public function testCanExplodeAndValidateProvidedData(
        array $allowedValues,
        string|null $inputData,
        string $expectedOutput,
    ): void {
        $filter = new Explode([
            'filter' => new AllowList([
                'list' => $allowedValues,
            ]),
        ]);
        $this->assertSame($expectedOutput, $filter->filter($inputData));
    }
}
