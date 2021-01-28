<?php

namespace Azuracom\ProcessBundle\Monolog\Handler;

use Azuracom\ProcessBundle\Model\ProcessInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Symfony\Component\HttpKernel\KernelInterface;

class ProcessHandler extends StreamHandler
{
    const DEFAULT_PATH = "/process/%s.log";

    /** @var string */
    private $logDir;

    public function __construct(KernelInterface $kernel)
    {
        $this->logDir = $kernel->getLogDir();
        parent::__construct($this->logDir . self::DEFAULT_PATH);
        $this->formatter = new JsonFormatter();
    }

    public function setSubject(ProcessInterface $process)
    {
        if(!$process->getUniqueId()){
            $process->generateUniqueId();
        }

        $this->url = sprintf($this->logDir . self::DEFAULT_PATH, $process->generateUniqueId());
    }
}
