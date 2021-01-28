<?php

namespace Azuracom\ProcessBundle\Handler;

use Azuracom\ProcessBundle\Model\ProcessInterface;
use Psr\Log\LoggerInterface;

interface ProcessHelperInterface
{
    public function setSubject(ProcessInterface $process): self;

    public function getLogger(): LoggerInterface;

    public function getLogAsArray(): array;

    public function getHandler(): ProcessHandler;
}
