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
    protected static $defaultName = 'azuracom:process:defer-handle';

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

    protected function configure()
    {
        $this
            ->setDescription('Deferred process handle')
            ->addOption(
                'date',
                null,
                InputOption::VALUE_REQUIRED,
                "DateTime::__construct first argument, default is now",
                'now'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = new \DateTime($input->getOption('date'));
        $end = clone $start;

        $processes = $this->repository
            ->createQueryBuilder('p')
            ->where("p.createdAt >= :start AND p.createdAt <= :end")
            ->andWhere('p.status = :status')
            ->setParameter('start', $start->setTime(0, 0, 0))
            ->setParameter('end', $end->setTime(23, 59, 59))
            ->setParameter('status', ProcessInterface::STATUS_WAITING_DEFERRED)
            ->getQuery()
            ->getResult();
        $count = count($processes);
        $output->writeln(sprintf("%s process found", $count));
        if (!$count) {
            return;
        }

        foreach ($processes as $process) {
            $handler = $this->provider->getHandler($process);
            $handler->handle($process);
        }

        $output->write("Save...");
        $this->manager->flush();
        $output->write("<info>Done</info>\n");
    }
}
