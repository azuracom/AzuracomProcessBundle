<?php

namespace Azuracom\ProcessBundle\Messenger;

use Azuracom\ProcessBundle\Handler\HandlerProviderInterface;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


#[AsMessageHandler]
class ProcessMessageHandler
{
    /** @var RepositoryInterface */
    private $repository;

    /** @var EntityManagerInterface */
    private $manager;

    /** @var HandlerProviderInterface */
    private $provider;

    public function __construct(
        RepositoryInterface $processRepository,
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

        $process->endProcess();
        $this->manager->flush();
    }
}
