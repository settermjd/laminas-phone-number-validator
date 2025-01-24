<?php

declare(strict_types=1);

namespace Settermjd\Filter;

use Laminas\Filter\FilterInterface;

use function array_filter;
use function explode;
use function implode;

use const ARRAY_FILTER_USE_BOTH;

/**
 * Explode executes a filter on each item of an exploded string
 * It supports two options:
 * - filter: The FilterInterface object to use to filter the exploded data items
 * - valueDelimiter: The delimiter to use to explode the provided data ($value))
 */
class Explode implements FilterInterface
{
    protected FilterInterface|null $filter = null;

    /**
     * @see https://www.php.net/manual/en/function.explode.php
     *
     * @var string The delimiter used to explode the provided string
     */
    protected string $valueDelimiter = ',';

    /**
     * @param array<string,FilterInterface|string> $options The options for configuring the filter
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * @param array<string,FilterInterface|string> $options
     */
    private function setOptions(array $options): void
    {
        $this->filter         = $options['filter'] ?? null;
        $this->valueDelimiter = $options['valueDelimiter'] ?? ',';
    }

    /**
     * filter explodes the provided data ($value) and then applies the provided filter
     * to each element of the exploded string before then imploding the filtered data back
     * into a string
     *
     * @inheritDoc
     */
    public function filter($value)
    {
        $data = explode($this->valueDelimiter, (string) $value);

        if ($this->filter instanceof FilterInterface) {
            $tmp = [];
            foreach ($data as $item) {
                $tmp[] = (string) $this->filter->filter($item);
            }
            $data = array_filter($tmp, fn ($value, $key) => $value !== '', ARRAY_FILTER_USE_BOTH);
        }

        return implode($this->valueDelimiter, $data);
    }
}
