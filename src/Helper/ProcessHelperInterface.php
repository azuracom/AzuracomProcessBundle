<?php

namespace Azuracom\ProcessBundle\Helper;

use Azuracom\ProcessBundle\Model\ProcessInterface;
use Azuracom\ProcessBundle\Monolog\Handler\ProcessHandler;
use Psr\Log\LoggerInterface;

/**
 * The helper is a PSR-3 logger (it exposes emergency()/alert()/critical()/error()/
 * warning()/notice()/info()/debug()/log()) so a process can be logged with a fully
 * type-hinted API instead of the previous magic __call().
 *
 * On top of logging, it owns the lifecycle of the log file: written locally while the
 * process runs, then flushed to the configured storage (any Flysystem adapter, e.g. S3)
 * when the process ends.
 */
interface ProcessHelperInterface extends LoggerInterface
{
    public function setSubject(ProcessInterface $process): self;

    public function getLogger(): LoggerInterface;

    /**
     * Finalize the current process: mark it as ended (delegates to Process::endProcess())
     * and persist its log file to the configured storage. Call this instead of
     * ProcessInterface::endProcess() so the log is not left on the local filesystem.
     */
    public function endProcess(): ProcessInterface;

    /**
     * Remove the process log from both the configured storage and any local working copy.
     */
    public function deleteLog(): void;

    public function getLogAsArray(): array;

    public function getHandler(): ProcessHandler;

    public function getStatusList(): array;

    public function getTypeList(): array;

    public function getTypeLabel(string $type): ?string;

    public function getStatusLabel(string $status): ?string;
}
