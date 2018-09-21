<?php

namespace App\Command\Migrate;

use App\Entity\Job\TaskTypeOptions;
use App\Entity\Task\Type\Type;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\ApplicationStateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveJSJobTaskTypeOptionsCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManagerInterface $entityManager
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:migrate:remove-js-job-task-type-options')
            ->setDescription('Remove JS JobTaskTypeOption entities')
            ->addOption('dry-run');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
            $output->writeln('In maintenance-read-only mode, I can\'t do that right now');
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $isDryRun = $input->getOption('dry-run');

        $jobTaskTypeOptionsRepository = $this->entityManager->getRepository(TaskTypeOptions::class);
        $taskTypeRepository = $this->entityManager->getRepository(Type::class);
        $jsTaskType = $taskTypeRepository->findOneBy([
            'name' => 'JS static analysis',
        ]);

        $output->write('<info>Finding JS JobTaskTypeOption entities ... </info>');

        $jobTaskTypeOptionsCollection = $jobTaskTypeOptionsRepository->findBy([
            'taskType' => $jsTaskType,
        ]);
        $jobTaskTypeOptionsCount = count($jobTaskTypeOptionsCollection);

        $output->writeln('found <comment>' . $jobTaskTypeOptionsCount . '</comment>');

        foreach ($jobTaskTypeOptionsCollection as $jobTaskTypeOptionsIndex => $jobTaskTypeOptions) {
            $jobTaskTypeOptionsNumber = $jobTaskTypeOptionsIndex + 1;

            $output->writeln(sprintf(
                '<info>Removing</info> #%s (%s of %s)',
                $jobTaskTypeOptions->getId(),
                $jobTaskTypeOptionsNumber,
                $jobTaskTypeOptionsCount
            ));

            $this->entityManager->remove($jobTaskTypeOptions);

            if (!$isDryRun) {
                $this->entityManager->flush();
            }
        }

        $output->writeln('<info>Done</info>');

        return self::RETURN_CODE_OK;
    }
}
