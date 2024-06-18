<?php

namespace Azuracom\ProcessBundle\Messenger;

class ProcessMessage
{
    private $processId;

    public function __construct(int $processId)
    {
        $this->processId = $processId;
    }

    public function getProcessId(): int
    {
        return $this->processId;
    }
}
