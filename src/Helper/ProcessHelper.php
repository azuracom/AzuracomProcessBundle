<?php

namespace Azuracom\ProcessBundle\Helper;

use Azuracom\ProcessBundle\Model\ProcessInterface;
use Azuracom\ProcessBundle\Monolog\Handler\ProcessHandler;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class ProcessHelper implements ProcessHelperInterface
{
    /**
     * Sub-folder used to store the logs inside the configured storage, keeping them
     * separate from the uploaded source files that also live in that storage.
     */
    private const LOG_STORAGE_PREFIX = 'logs/';

    protected LoggerInterface $logger;

    protected ?ProcessInterface $process = null;

    /**
     * @param FilesystemOperator|null $processStorage Storage where the log file is persisted when a
     *                                                process ends. Autowired from the flysystem storage
     *                                                bound to the "$processStorage" argument (see the
     *                                                consuming project's flysystem.yaml). When null, the
     *                                                log stays on the local filesystem (legacy behaviour).
     */
    public function __construct(
        LoggerInterface $processLogger,
        private array $typeList,
        private array $statusList,
        private ?FilesystemOperator $processStorage = null,
    ) {
        $this->logger = $processLogger;
    }

    public function setSubject(ProcessInterface $process): ProcessHelperInterface
    {
        $this->process = $process;
        $this->getHandler()->setSubject($process);

        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function endProcess(): ProcessInterface
    {
        $process = $this->requireProcess();
        $process->endProcess();
        $this->persistLog();

        return $process;
    }

    public function deleteLog(): void
    {
        // Copy stored on the configured storage (local adapter, S3, ...).
        if ($this->processStorage && $this->process) {
            try {
                $storagePath = $this->getStoragePath();
                if ($this->processStorage->fileExists($storagePath)) {
                    $this->processStorage->delete($storagePath);
                }
            } catch (FilesystemException) {
                // The log may simply never have been persisted: nothing to clean up.
            }
        }

        // Local working copy (still present if the process never reached endProcess()).
        $localPath = $this->getHandler()->getUrl();
        if ($localPath && is_file($localPath)) {
            @unlink($localPath);
        }
    }

    public function getLogAsArray(): array
    {
        $content = $this->readLogContent();
        $array = [];
        foreach (explode("\n", $content) as $row) {
            if (!$row) {
                continue;
            }

            $array[] = json_decode($row, true);
        }

        return $array;
    }

    /*
     * ---------------------------------------------------------------------
     * PSR-3 logging API (explicit methods, replaces the previous __call()).
     * Every method forwards to the underlying logger and reflects the
     * severity on the process status.
     * ---------------------------------------------------------------------
     */

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->write('emergency', $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->write('alert', $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->write('critical', $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->write('error', $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->write('warning', $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->write('notice', $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->write('info', $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->write('debug', $message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->write((string) $level, $message, $context);
    }

    private function write(string $level, string|\Stringable $message, array $context): void
    {
        $this->logger->log($level, $message, $context);
        $this->applyStatus($level);
    }

    /**
     * Reflect the logged severity on the process status, without downgrading an existing error.
     */
    private function applyStatus(string $level): void
    {
        if (!$this->process) {
            return;
        }

        switch ($level) {
            case 'warning':
                if ($this->process->getStatus() !== ProcessInterface::STATUS_HAS_ERROR) {
                    $this->process->setStatus(ProcessInterface::STATUS_HAS_WARNING);
                }
                break;

            case 'error':
            case 'critical':
            case 'alert':
            case 'emergency':
                $this->process->setStatus(ProcessInterface::STATUS_HAS_ERROR);
                break;
        }
    }

    /*
     * ---------------------------------------------------------------------
     * Storage handling: local while running, persisted on endProcess().
     * ---------------------------------------------------------------------
     */

    private function persistLog(): void
    {
        if (!$this->processStorage) {
            // No configured storage: keep the local log file as-is (legacy behaviour).
            return;
        }

        $handler = $this->getHandler();
        // Flush and release the local stream before reading it back.
        $handler->close();

        $localPath = $handler->getUrl();
        if (!$localPath || !is_file($localPath)) {
            return;
        }

        $stream = fopen($localPath, 'rb');
        if ($stream === false) {
            return;
        }

        try {
            $this->processStorage->writeStream($this->getStoragePath(), $stream);
        } catch (FilesystemException) {
            // Upload failed: keep the local file so the log is not lost.
            if (is_resource($stream)) {
                fclose($stream);
            }

            return;
        }

        if (is_resource($stream)) {
            fclose($stream);
        }

        // The log is safely stored: drop the local working copy.
        @unlink($localPath);
    }

    private function readLogContent(): string
    {
        if ($this->processStorage && $this->process) {
            try {
                $storagePath = $this->getStoragePath();
                if ($this->processStorage->fileExists($storagePath)) {
                    return $this->processStorage->read($storagePath);
                }
            } catch (FilesystemException) {
                // Fall back to the local file below.
            }
        }

        $localPath = $this->getHandler()->getUrl();
        if ($localPath && is_file($localPath)) {
            return (string) file_get_contents($localPath);
        }

        return '';
    }

    private function getStoragePath(): string
    {
        return self::LOG_STORAGE_PREFIX . $this->requireProcess()->getUniqueId() . '.log';
    }

    private function requireProcess(): ProcessInterface
    {
        if (!$this->process) {
            throw new \LogicException('No process set on the ProcessHelper. Call setSubject() first.');
        }

        return $this->process;
    }

    public function getHandler(): ProcessHandler
    {
        return $this->logger->getHandlers()[0];
    }

    public function getTypeLabel(string $type): ?string
    {
        return $this->typeList[$type] ?? null;
    }

    public function getStatusLabel(string $status): ?string
    {
        return $this->statusList[$status] ?? null;
    }

    /**
     * Get the value of typeList
     */
    public function getTypeList(): array
    {
        return $this->typeList;
    }

    /**
     * Get the value of statusList
     */
    public function getStatusList(): array
    {
        return $this->statusList;
    }
}
