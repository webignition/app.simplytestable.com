<?php

namespace App\Command\Migrate;

use App\Command\DryRunOptionTrait;
use App\Repository\TaskOutputRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Output;
use App\Services\ApplicationStateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\SymfonyConsole\TypedInput\TypedInput;

class AddHashToHashlessOutputCommand extends Command
{
    use DryRunOptionTrait;

    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    private $applicationStateService;
    private $entityManager;
    private $taskOutputRepository;

    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        TaskOutputRepository $taskOutputRepository,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->taskOutputRepository = $taskOutputRepository;
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
        ;

        $this->addDryRunOption();
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

        $isDryRun = $this->isDryRun($input);

        if ($isDryRun) {
            $this->outputIsDryRunNotification($output);
        }

        $output->writeln('Finding hashless output ...');

        $typedInput = new TypedInput($input);
        $limit = $typedInput->getIntegerOption('limit');

        $hashlessOutputIds = $this->taskOutputRepository->findHashlessOutputIds($limit);
        $hashlessOutputCount = count($hashlessOutputIds);

        if (empty($hashlessOutputIds)) {
            $output->writeln('No task outputs require a hash to be set. Done.');

            return self::RETURN_CODE_OK;
        }

        $output->writeln(count($hashlessOutputIds).' outputs require a hash to be set.');

        $processedTaskOutputCount = 0;

        foreach ($hashlessOutputIds as $hashlessOutputId) {
            /* @var Output $taskOutput */
            $taskOutput = $this->taskOutputRepository->find($hashlessOutputId);

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
}
