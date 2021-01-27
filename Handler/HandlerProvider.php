<?php

namespace Azuracom\ProcessBundle\Handler;

use Azuracom\ProcessBundle\Model\Process;

class HandlerProvider
{
    /** @var HandlerInterface[] */
    private $handlers = [];

    public function getHandler(Process $process): ?HandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->isEligible($process)) {
                return $handler;
            }
        }
        
        return null;
    }

    public function addHandler(HandlerInterface $handler)
    {
        $this->handlers[] = $handler;
    }
}
