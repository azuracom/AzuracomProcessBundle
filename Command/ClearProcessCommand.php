<?php

namespace Azuracom\ProcessBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClearProcessCommand extends Command
{
    protected static $defaultName = 'azuracom:process:clear';

    /** @var RepositoryInterface */
    private $repository;

    /** @var EntityManagerInterface */
    private $manager;

    const DEFAULT_MODIFY = '6 months';

    public function __construct(RepositoryInterface $processRepository, EntityManagerInterface $processManager)
    {
        parent::__construct();
        $this->repository = $processRepository;
        $this->manager = $processManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Clear process using a delay')
            ->addOption(
                'modify',
                null,
                InputOption::VALUE_REQUIRED,
                "DateTime::modify first argument, if value doesn't contains '-' it will be added",
                self::DEFAULT_MODIFY
            )
            ->setHelp('All process created before the delay will be deleted');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $modify = $input->getOption('modify');
        if (substr($modify, 0, 1) != '-') {
            $modify = '-' . $modify;
        }
        $date = (new \DateTime())->modify($modify);
        $processes = $this->repository
            ->createQueryBuilder('p')
            ->where("p.createdAt <= :date")
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
        $count = count($processes);
        $output->writeln(sprintf("%s process found", $count));
        if (!$count) {
            return self::SUCCESS;
        }

        foreach ($processes as $process) {
            $this->manager->remove($process);
        }

        $output->write("Save...");
        $this->manager->flush();
        $output->write("<info>Done</info>\n");

        return self::SUCCESS;
    }
}
