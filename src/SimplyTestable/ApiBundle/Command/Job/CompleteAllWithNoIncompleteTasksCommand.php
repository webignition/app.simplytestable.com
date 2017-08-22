<?php
namespace SimplyTestable\ApiBundle\Command\Job;

use SimplyTestable\ApiBundle\Command\BaseCommand;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompleteAllWithNoIncompleteTasksCommand extends BaseCommand
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;
    const RETURN_CODE_NO_MATCHING_JOBS = 2;

    /**
     * @var InputInterface
     */
    protected $input;

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
                'Run through the process without writing any data'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $applicationStateService = $this->getContainer()->get('simplytestable.services.applicationstateservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $this->input = $input;

        if ($this->isDryRun()) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }

        $output->write('Finding matching jobs (unfinished with more than zero tasks all finished) ... ');

        $jobService = $this->getContainer()->get('simplytestable.services.jobservice');

        $jobs = $jobService->getUnfinishedJobsWithTasksAndNoIncompleteTasks();

        // Exclude crawl jobs
        $jobTypeService = $this->getContainer()->get('simplytestable.services.jobtypeservice');
        $crawlJobType = $jobTypeService->getByName(JobTypeService::CRAWL_NAME);

        foreach ($jobs as $jobIndex => $job) {
            if ($job->getType() === $crawlJobType) {
                unset($jobs[$jobIndex]);
            }
        }

        if (count($jobs) === 0) {
            $output->writeln('None found. Done.');

            return self::RETURN_CODE_NO_MATCHING_JOBS;
        }

        $output->writeln(count($jobs) . ' found.');
        $output->writeln('Marking jobs as completed ... ');

        foreach ($jobs as $job) {
            $output->writeln('['.$job->getId().'] ');

            if ($this->isDryRun() === false) {
                $jobService->complete($job);
            }
        }

        return 0;
    }

    /**
     * @return int
     */
    protected function isDryRun()
    {
        return $this->input->getOption('dry-run') == 'true';
    }
}
