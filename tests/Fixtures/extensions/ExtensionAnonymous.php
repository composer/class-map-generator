<?php

namespace Acme\Anon;

// anonymous extensions have no name and are not autoloadable, they must not end up in the class map

extension \DateTimeImmutable $date
{
    public function tomorrow(): \DateTimeImmutable
    {
        return $date->modify('+1 day');
    }
}

extension string $s
{
    public function shout(): string
    {
        return \strtoupper($s);
    }
}
