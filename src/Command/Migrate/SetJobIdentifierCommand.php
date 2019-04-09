<?php

namespace App\Command\Migrate;

use App\Entity\Job\Job;
use App\Repository\JobRepository;
use App\Services\JobIdentifierFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetJobIdentifierCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const FLUSH_THRESHOLD = 100;

    private $jobRepository;
    private $entityManager;
    private $jobIdentifierFactory;

    public function __construct(
        JobRepository $jobRepository,
        EntityManagerInterface $entityManager,
        JobIdentifierFactory $jobIdentifierFactory,
        $name = null
    ) {
        parent::__construct($name);

        $this->jobRepository = $jobRepository;
        $this->entityManager = $entityManager;
        $this->jobIdentifierFactory = $jobIdentifierFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:migrate:set-job-identifier')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Run through the process without writing any data'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isDryRun = filter_var($input->getOption('dry-run'), FILTER_VALIDATE_BOOLEAN);

        $output->write('Finding jobs ... ');

        /* @var Job[] $jobs */
        $jobs = $this->jobRepository->findBy([
            'identifier' => null,
        ]);

        $jobCount = count($jobs);

        $output->writeln('<comment>' . $jobCount . '</comment> found');

        $flushableCount = 0;
        $processedJobCount = 0;

        foreach ($jobs as $job) {
            $completionPercent = (int) ($processedJobCount / $jobCount * 100);

            $output->writeln(
                'Processing job <comment>' . $job->getId() . '</comment> ... ' .
                '<comment>' . $completionPercent . '%</comment> done'
            );

            $identifier = $this->jobIdentifierFactory->create($job);
            $job->setIdentifier($identifier);
            $this->entityManager->persist($job);

            $flushableCount++;
            $processedJobCount++;

            if ($flushableCount === self::FLUSH_THRESHOLD && !$isDryRun) {
                $this->entityManager->flush();
                $flushableCount = 0;
            }
        }

        if (!$isDryRun) {
            $this->entityManager->flush();
        }

        return self::RETURN_CODE_OK;
    }
}