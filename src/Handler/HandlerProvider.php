<?php

namespace Azuracom\ProcessBundle\Handler;

use Azuracom\ProcessBundle\Model\ProcessInterface;

class HandlerProvider implements HandlerProviderInterface
{
    /** @var HandlerInterface[] */
    private $handlers = [];

    public function getHandler(ProcessInterface $process): ?HandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->isEligible($process)) {
                $handler->setProcess($process);
                $handler->configure();
                return $handler;
            }
        }
        
        return null;
    }

    public function addHandler(HandlerInterface $handler) : HandlerProviderInterface
    {
        $this->handlers[] = $handler;

        return $this;
    }
}
