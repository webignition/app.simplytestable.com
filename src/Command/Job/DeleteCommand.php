<?php

namespace App\Command\Job;

use App\Command\DryRunOptionTrait;
use App\Entity\Job\Ammendment;
use App\Entity\Job\RejectionReason;
use App\Entity\Job\TaskTypeOptions;
use App\Entity\Task\Task;
use App\Repository\CrawlJobContainerRepository;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\ApplicationStateService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeleteCommand extends AbstractJobCommand
{
    use DryRunOptionTrait;

    const NAME = 'simplytestable:job:delete';
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    private $applicationStateService;
    private $entityManager;
    private $crawlJobContainerRepository;

    public function __construct(
        JobRepository $jobRepository,
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        CrawlJobContainerRepository $crawlJobContainerRepository,
        $name = null
    ) {
        parent::__construct($jobRepository, $name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->crawlJobContainerRepository = $crawlJobContainerRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Delete a job')
            ->addArgument('id', InputArgument::REQUIRED, 'id of job to delete')
            ->addOption('force');

        $this->addDryRunOption();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $job = $this->getJob($input);
        if (empty($job)) {
            return self::RETURN_CODE_OK;
        }

        $isDryRun = $this->isDryRun($input);

        if ($isDryRun) {
            $this->outputIsDryRunNotification($output);
        }

        $output->writeln('<info>Processing job</info> <comment>' . $job->getId() . '</comment>');

        $confirmDelete = $input->getOption('force');

        if (!$confirmDelete) {
            $questionHelper = $this->getHelper('question');
            $confirmationQuestion = new ConfirmationQuestion(
                '<comment>Delete job</comment> ' . $job->getId() . '? (y/<comment>n</comment>) ',
                false
            );

            $confirmDelete = $questionHelper->ask($input, $output, $confirmationQuestion);
        }

        if (!$confirmDelete) {
            $output->writeln('<error>Cancelled</error>');

            return self::RETURN_CODE_OK;
        }

        /* @var Task[] $tasks */
        $tasks = $job->getTasks();
        $taskCount = count($tasks);

        $output->writeln([
            '',
            '<comment>' . $taskCount . '</comment> tasks to remove ...'
        ]);

        foreach ($tasks as $taskIndex => $task) {
            $taskNumber = $taskIndex + 1;

            $output->writeln(sprintf(
                '    <info>%s of %s</info> ... removing task <comment>%s</comment>',
                $taskNumber,
                $taskCount,
                $task->getId()
            ));

            $this->entityManager->remove($task);

            if (!$isDryRun) {
                $this->entityManager->flush();
            }
        }

        /* @var TaskTypeOptions[] $jobTaskTypeOptions */
        $jobTaskTypeOptions = $job->getTaskTypeOptions();
        $jobTaskTypeOptionsCount = count($jobTaskTypeOptions);

        $output->writeln([
            '',
            '<comment>' . $jobTaskTypeOptionsCount . '</comment> task type options to remove ...'
        ]);

        foreach ($jobTaskTypeOptions as $taskTypeOption) {
            $output->writeln(sprintf(
                '    <info>Removing task type options for</info> <comment>%s</comment>',
                $taskTypeOption->getTaskType()->getName()
            ));

            $this->entityManager->remove($taskTypeOption);

            if (!$isDryRun) {
                $this->entityManager->flush();
            }
        }

        $output->writeln([
            '',
            'Removing <comment>ammendments</comment>',
        ]);

        $jobAmmendmentRepository = $this->entityManager->getRepository(Ammendment::class);
        $jobAmmendments = $jobAmmendmentRepository->findBy([
            'job' => $job,
        ]);

        foreach ($jobAmmendments as $ammendment) {
            $this->entityManager->remove($ammendment);

            if (!$isDryRun) {
                $this->entityManager->flush();
            }
        }

        $output->writeln([
            '',
            'Removing <comment>rejection reasons</comment>',
        ]);

        $jobRejectionReasonRepository = $this->entityManager->getRepository(RejectionReason::class);
        $rejectionReasons = $jobRejectionReasonRepository->findBy([
            'job' => $job,
        ]);

        foreach ($rejectionReasons as $rejectionReason) {
            $this->entityManager->remove($rejectionReason);

            if (!$isDryRun) {
                $this->entityManager->flush();
            }
        }

        $output->writeln([
            '',
            'Removing <comment>crawl job container</comment>',
        ]);

        $crawlJobContainer = $this->crawlJobContainerRepository->findOneBy([
            'parentJob' => $job,
        ]);

        if (!empty($crawlJobContainer)) {
            $this->entityManager->remove($crawlJobContainer);

            if (!$isDryRun) {
                $this->entityManager->flush();
            }
        }

        $output->writeln([
            '',
            'Removing job <comment>' . $job->getId() . '</comment>',
            '',
        ]);

        $this->entityManager->remove($job);

        if (!$isDryRun) {
            $this->entityManager->flush();
        }

        if ($isDryRun) {
            $this->outputIsDryRunNotification($output);
        }

        $output->writeln('<info>Done!</info>');

        return self::RETURN_CODE_OK;
    }
}
