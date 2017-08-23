<?php
namespace SimplyTestable\ApiBundle\Command\Job;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompleteAllWithNoIncompleteTasksCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;
    const RETURN_CODE_NO_MATCHING_JOBS = 2;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var JobService
     */
    private $jobService;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param JobService $jobService
     * @param string $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        JobService $jobService,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->jobService = $jobService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:job:complete-all-with-no-incomplete-tasks')
            ->setDescription('Mark as completed all in-progress jobs that have no incomplete tasks')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_OPTIONAL,
                'Run through the process without writing any data',
                false
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $isDryRun = filter_var($input->getOption('dry-run'), FILTER_VALIDATE_BOOLEAN);

        if ($isDryRun) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }

        $output->write('Finding matching jobs (unfinished with more than zero tasks all finished) ... ');

        $jobs = $this->jobService->getUnfinishedJobsWithTasksAndNoIncompleteTasks();
        $jobs = $this->removeCrawlJobsFromJobCollection($jobs);

        if (empty($jobs)) {
            $output->writeln('None found. Done.');

            return self::RETURN_CODE_NO_MATCHING_JOBS;
        }

        $output->writeln(count($jobs) . ' found.');
        $output->writeln('Marking jobs as completed ... ');

        foreach ($jobs as $job) {
            $output->writeln('['.$job->getId().'] ');

            if (false === $isDryRun) {
                $this->jobService->complete($job);
            }
        }

        return self::RETURN_CODE_OK;
    }

    /**
     * @param Job[] $jobs
     *
     * @return Job[] $jobs
     */
    private function removeCrawlJobsFromJobCollection($jobs)
    {
        foreach ($jobs as $jobIndex => $job) {
            if (JobTypeService::CRAWL_NAME === $job->getType()->getName()) {
                unset($jobs[$jobIndex]);
            }
        }

        return $jobs;
    }
}
