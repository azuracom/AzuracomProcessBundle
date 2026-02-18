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

    protected function configure(): void
    {
        $this
            ->setName('azuracom:process:clear')
            ->setDescription('Clear process using a delay')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Process type (coma separated for multiple values)', null)
            ->addOption('status', null, InputOption::VALUE_REQUIRED, 'Process status (coma separated for multiple values)', null)
            ->addOption('resolved', null, InputOption::VALUE_NEGATABLE, 'Only resolved process', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run mode, no process will be deleted')
            ->addOption(
                'modify',
                null,
                InputOption::VALUE_REQUIRED,
                "DateTime::modify first argument, if value doesn't contains '-' it will be added, if value is a number it will be converted to days, default is 6 months",
                self::DEFAULT_MODIFY
            )
            ->setHelp('All process created before the delay will be deleted');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $modify = $input->getOption('modify');

        if(preg_match('/\d{+}/', $modify)) {
            $modify = $modify . ' days';
        }

        if (substr($modify, 0, 1) != '-') {
            $modify = '-' . $modify;
        }

        $date = (new \DateTime())->modify($modify);
        $qb = $this->repository
            ->createQueryBuilder('p')
            ->where("p.createdAt <= :date")
            ->setParameter('date', $date);

        $type = $input->getOption('type');
        if ($type) {
            $types = explode(',', $type);
            $qb->andWhere("p.type IN (:types)")
                ->setParameter('types', $types);
        }

        $status = $input->getOption('status');
        if ($status) {
            $statuses = explode(',', $status);
            $qb->andWhere("p.status IN (:statuses)")
                ->setParameter('statuses', $statuses);
        }

        $resolved = $input->getOption('resolved');
        if($resolved !== null){
            $qb->andWhere("p.resolved = :resolved")
                ->setParameter('resolved', $resolved);
        }

        $processes = $qb
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

        if ($input->getOption('dry-run')) {
            $output->writeln("<info>Dry run mode, no process will be deleted</info>");
            return self::SUCCESS;
        }

        $output->write("Save...");
        $this->manager->flush();
        $output->write("<info>Done</info>\n");

        return self::SUCCESS;
    }
}
