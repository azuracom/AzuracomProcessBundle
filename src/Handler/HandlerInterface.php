<?php

namespace Azuracom\ProcessBundle\Handler;

use Azuracom\ProcessBundle\Helper\ProcessHelperInterface;
use Azuracom\ProcessBundle\Model\ProcessInterface;

interface HandlerInterface
{
    public static function getTypes(): array;
    public static function getTypeLabel(?string $type = null): string;

    public function handle(): void;
    public function isEligible(ProcessInterface $process): bool;
    public function configure(): void;

    public function getHelper(): ?ProcessHelperInterface;
    public function setHelper(?ProcessHelperInterface $helper): self;

    public function getProcess(): ?ProcessInterface;
    public function setProcess(?ProcessInterface $process): self;
}
