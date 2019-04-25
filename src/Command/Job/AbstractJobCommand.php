<?php

namespace App\Command\Job;

use App\Entity\Job\Job;
use App\Repository\JobRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

abstract class AbstractJobCommand extends Command
{
    private $jobRepository;

    public function __construct(JobRepository $jobRepository, string $name = null)
    {
        parent::__construct($name);

        $this->jobRepository = $jobRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addArgument(
            'id',
            InputArgument::REQUIRED,
            'id of job to handle'
        );
    }

    protected function getJob(InputInterface $input): ?Job
    {
        /* @var Job $job */
        $job =  $this->jobRepository->find((int) $input->getArgument('id'));

        return $job;
    }
}
