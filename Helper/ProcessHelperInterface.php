<?php

namespace Azuracom\ProcessBundle\Helper;

use Azuracom\ProcessBundle\Model\ProcessInterface;
use Azuracom\ProcessBundle\Monolog\Handler\ProcessHandler;
use Psr\Log\LoggerInterface;

interface ProcessHelperInterface
{
    public function setSubject(ProcessInterface $process): self;

    public function getLogger(): LoggerInterface;

    public function getLogAsArray(): array;

    public function getHandler(): ProcessHandler;
}
