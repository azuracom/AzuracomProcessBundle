<?php

namespace Azuracom\ProcessBundle\Messenger;

use Azuracom\ProcessBundle\Handler\HandlerProviderInterface;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class ProcessMessageHandler
{
    /** @var EntityRepository */
    private $repository;

    /** @var EntityManagerInterface */
    private $manager;

    /** @var HandlerProviderInterface */
    private $provider;

    public function __construct(
        EntityRepository $processRepository,
        EntityManagerInterface $processManager,
        HandlerProviderInterface $provider
    ) {

        $this->repository = $processRepository;
        $this->manager = $processManager;
        $this->provider = $provider;
    }

    public function __invoke(ProcessMessage $message)
    {
        /** @var ProcessInterface */
        $process = $this->repository->find($message->getProcessId());

        $process->setStatus(ProcessInterface::STATUS_IN_PROGRESS);
        $this->manager->flush();

        $handler = $this->provider->getHandler($process);
        $process->startProcess();
        try {
            $handler->handle();
        } catch (\Exception $e) {
            $process->setStatus(ProcessInterface::STATUS_HAS_ERROR);
            $handler->getHelper()->error(" Unexcepted error during handle: " . $e->getMessage());
        }

        // End through the helper so the log is persisted on the configured storage.
        // Fall back to the process itself when no handler matched the message.
        if ($handler && $handler->getHelper()) {
            $handler->getHelper()->endProcess();
        } else {
            $process->endProcess();
        }
        $this->manager->flush();
    }
}
