<?php

namespace Azuracom\ProcessBundle\Helper;

class Counter
{
    private array $types = [];

    public function increment($type = 'default')
    {
        if (!isset($this->types[$type])) {
            $this->types[$type] = 0;
        }

        $this->types[$type]++;
    }

    public function get($type = 'default')
    {
        return isset($this->types[$type]) ? $this->types[$type] : 0;
    }

    public function reset()
    {
        $this->types = [];
    }
}
