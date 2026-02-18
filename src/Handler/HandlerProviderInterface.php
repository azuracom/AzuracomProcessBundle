<?php

namespace Azuracom\ProcessBundle\Handler;

use Azuracom\ProcessBundle\Model\ProcessInterface;

interface HandlerProviderInterface
{
    public function getHandler(ProcessInterface $process): ?HandlerInterface;
    public function addHandler(HandlerInterface $handler) : self;
}