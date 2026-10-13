<?php

namespace Acme\Scalars;

extension StringHelpers on string $s
{
    public function isBlank(): bool
    {
        return \trim($s) === '';
    }
}

extension ArrayHelpers on array $items
{
    public function isEmpty(): bool
    {
        return $items === [];
    }
}
