<?php
namespace App\Command\Job;

use App\Command\DryRunOptionTrait;
use App\Entity\Job\Job;
use App\Services\ApplicationStateService;
use App\Services\JobService;
use App\Services\JobTypeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompleteAllWithNoIncompleteTasksCommand extends Command
{
    use DryRunOptionTrait;

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
            ->setDescription('Mark as completed all in-progress jobs that have no incomplete tasks');

        $this->addDryRunOption();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $isDryRun = $this->isDryRun($input);

        if ($isDryRun) {
            $this->outputIsDryRunNotification($output);
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
