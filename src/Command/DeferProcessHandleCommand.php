<?php

namespace Azuracom\ProcessBundle\Command;

use Azuracom\ProcessBundle\Handler\HandlerProviderInterface;
use Azuracom\ProcessBundle\Model\ProcessInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeferProcessHandleCommand extends Command
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
        parent::__construct();
        $this->repository = $processRepository;
        $this->manager = $processManager;
        $this->provider = $provider;
    }

    protected function configure(): void
    {
        $this
            ->setName('azuracom:process:defer-handle')
            ->setDescription('Deferred process handle')
            ->addOption(
                'date',
                null,
                InputOption::VALUE_REQUIRED,
                "DateTime::__construct first argument, default is now",
                'now'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = new \DateTime($input->getOption('date'));
        $end = clone $start;

        /** @var ProcessInterface[] */
        $processes = $this->repository
            ->createQueryBuilder('p')
            ->where("p.createdAt >= :start AND p.createdAt <= :end")
            ->andWhere('p.status = :status')
            ->andWhere('p.useMessenger = 0')
            ->setParameter('start', $start->setTime(0, 0, 0))
            ->setParameter('end', $end->setTime(23, 59, 59))
            ->setParameter('status', ProcessInterface::STATUS_WAITING_DEFERRED)
            ->getQuery()
            ->getResult();
        $count = count($processes);
        $output->writeln(sprintf("%s process found", $count));
        if (!$count) {
            return self::SUCCESS;
        }

        foreach ($processes as $process) {
            $output->writeln(sprintf("Start handle #%s of type %s", $process->getId(), $process->getType()));
            //set process in progress to avoid conflict when several commands are running at the same time
            $process->setStatus(ProcessInterface::STATUS_IN_PROGRESS);
            $this->manager->flush();

            $handler = $this->provider->getHandler($process);
            try {
                $handler->handle();
            } catch (\Exception $e) {
                $process->setStatus(ProcessInterface::STATUS_HAS_ERROR);
                $handler->getHelper()->error(" Unexcepted error during handle: " . $e->getMessage());
            }
        }

        $output->write("Save...");
        $this->manager->flush();
        $output->write("<info>Done</info>\n");

        return self::SUCCESS;
    }
}
