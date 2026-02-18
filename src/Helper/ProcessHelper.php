<?php

namespace Azuracom\ProcessBundle\Helper;

use Azuracom\ProcessBundle\Model\ProcessInterface;
use Azuracom\ProcessBundle\Monolog\Handler\ProcessHandler;
use Psr\Log\LoggerInterface;

class ProcessHelper implements ProcessHelperInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var ProcessInterface|null */
    protected $process;

    public function __construct(LoggerInterface $processLogger,private array $typeList,private array $statusList)
    {
        $this->logger = $processLogger;
    }

    public function setSubject(ProcessInterface $process) : ProcessHelperInterface
    {
        $this->process = $process;
        $this->getHandler()->setSubject($process);

        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getLogAsArray() : array
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
                    if ($this->process->getStatus() != ProcessInterface::STATUS_HAS_ERROR) {
                        $this->process->setStatus(ProcessInterface::STATUS_HAS_WARNING);
                    }
                    break;

                case "error":
                    $this->process->setStatus(ProcessInterface::STATUS_HAS_ERROR);
                    break;
            }
        }
    }

    public function getHandler(): ProcessHandler
    {
        return $this->logger->getHandlers()[0];
    }

    public function getTypeLabel(string $type): ?string
    {
        return isset($this->typeList[$type]) ? $this->typeList[$type] : null;
    }

    public function getStatusLabel(string $status) :?string
    {
        return isset($this->statusList[$status]) ? $this->statusList[$status] : null;
    }

    /**
     * Get the value of typeList
     */ 
    public function getTypeList() : array
    {
        return $this->typeList;
    }

    /**
     * Get the value of statusList
     */ 
    public function getStatusList() :array
    {
        return $this->statusList;
    }
}
