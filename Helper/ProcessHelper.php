<?php

namespace Azuracom\ProcessBundle\Process;

use Azuracom\ProcessBundle\Model\Process;
use Azuracom\ProcessBundle\Monolog\Handler\ProcessHandler;
use Psr\Log\LoggerInterface;

class ProcessHelper
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var Process|null */
    protected $process;

    public function __construct(LoggerInterface $processLogger)
    {
        $this->logger = $processLogger;
    }

    public function setSubject(Process $process)
    {
        $this->process = $process;
        $this->getHandler()->setSubject($process);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getLogAsArray()
    {
        $content = file_get_contents($this->getHandler()->getUrl());
        $rows = explode("\n", $content);
        $array = [];
        foreach ($rows as $row) {
            if (!$row) {
                continue;
            }

            $array[] = json_decode($row, true);
        }

        return $array;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->logger, $name)) {
            call_user_func_array([$this->logger, $name], $arguments);
            switch ($name) {
                case "warning":
                    if ($this->process->getStatus() != Process::STATUS_HAS_ERROR) {
                        $this->process->setStatus(Process::STATUS_HAS_WARNING);
                    }
                    break;

                case "error":
                    $this->process->setStatus(Process::STATUS_HAS_ERROR);
                    break;
            }
        }
    }

    private function getHandler(): ProcessHandler
    {
        return $this->logger->getHandlers()[0];
    }
}
