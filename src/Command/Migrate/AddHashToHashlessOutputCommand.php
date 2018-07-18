<?php

namespace App\Command\Migrate;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Output;
use App\Services\ApplicationStateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddHashToHashlessOutputCommand extends Command
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
            ->setName('simplytestable:migrate:add-hash-to-hashless-output')
            ->setDescription('Set the hash property on TaskOutput objects that have no hash set')
            ->addOption('limit')
            ->addOption('dry-run')
        ;
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

        $output->writeln('Finding hashless output ...');

        $taskOutputRepository = $this->entityManager->getRepository(Output::class);

        $hashlessOutputIds = $taskOutputRepository->findHashlessOutputIds($this->getLimit($input));
        $hashlessOutputCount = count($hashlessOutputIds);

        if (empty($hashlessOutputIds)) {
            $output->writeln('No task outputs require a hash to be set. Done.');

            return self::RETURN_CODE_OK;
        }

        $output->writeln(count($hashlessOutputIds).' outputs require a hash to be set.');

        $processedTaskOutputCount = 0;

        foreach ($hashlessOutputIds as $hashlessOutputId) {
            /* @var Output $taskOutput */
            $taskOutput = $taskOutputRepository->find($hashlessOutputId);

            $processedTaskOutputCount++;
            $remainingTaskCount = $hashlessOutputCount - $processedTaskOutputCount;

            $output->writeln(sprintf(
                'Setting hash for [%s] (%s remaining)',
                $taskOutput->getId(),
                $remainingTaskCount
            ));

            if (!$isDryRun) {
                $taskOutput->generateHash();
                $this->entityManager->persist($taskOutput);
                $this->entityManager->flush();
            }

            $this->entityManager->detach($taskOutput);
        }

        return self::RETURN_CODE_OK;
    }

    /**
     * @param InputInterface $input
     *
     * @return int
     */
    private function getLimit(InputInterface $input)
    {
        if ($input->getOption('limit') === false) {
            return 0;
        }

        $limit = filter_var($input->getOption('limit'), FILTER_VALIDATE_INT);

        return ($limit <= 0) ? 0 : $limit;
    }
}
