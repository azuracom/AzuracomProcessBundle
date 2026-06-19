<?php

namespace Azuracom\ProcessBundle\Helper;

class Counter
{
    private array $types = [];

    public function increment(string $type = 'default'): void
    {
        if (!isset($this->types[$type])) {
            $this->types[$type] = 0;
        }

        $this->types[$type]++;
    }

    public function get(string $type = 'default'): int
    {
        return isset($this->types[$type]) ? $this->types[$type] : 0;
    }

    public function reset(): void
    {
        $this->types = [];
    }
}
