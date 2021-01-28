<?php

namespace Azuracom\ProcessBundle\Handler;

use Azuracom\ProcessBundle\Model\ProcessInterface;

interface HandlerInterface
{
    public static function getType() : string;
    public static function getTypeLabel() :string;
    
    public function handle(ProcessInterface $process);
    public function isEligible(ProcessInterface $process): bool;
    public function configure(): void;
}
