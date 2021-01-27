<?php

namespace Azuracom\ProcessBundle\Handler;

use Azuracom\ProcessBundle\Model\Process;

interface HandlerInterface
{
    public static function getType() : string;
    public static function getTypeLabel() :string;
    
    public function handle(Process $process);
    public function isEligible(Process $process): bool;
    public function configure(): void;
}
