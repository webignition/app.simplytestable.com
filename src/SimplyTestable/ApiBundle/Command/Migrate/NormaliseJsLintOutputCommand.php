<?php

namespace SimplyTestable\ApiBundle\Command\Migrate;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NormaliseJsLintOutputCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var EntityRepository
     */
    private $taskTypeRepository;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManager $entityManager
     * @param TaskRepository $taskRepository
     * @param EntityRepository $taskTypeRepository
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManager $entityManager,
        TaskRepository $taskRepository,
        EntityRepository $taskTypeRepository,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->taskRepository = $taskRepository;
        $this->taskTypeRepository = $taskTypeRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:migrate:normalise-jslint-output')
            ->setDescription(
                'Normalise the tmp paths in JSLint output and truncate JSLint fragment lines to 256 characters'
            )
            ->addOption('limit')
            ->addOption('dry-run')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInMaintenanceReadOnlyState()) {
            $output->writeln('In maintenance-read-only mode, I can\'t do that right now');

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $isDryRun = $input->getOption('dry-run');

        $output->writeln('Finding jslint output ...');

        $taskOutputRepository = $this->entityManager->getRepository(Output::class);

        /* @var TaskType $jsStaticAnalysisType */
        $jsStaticAnalysisType = $this->taskTypeRepository->findOneBy([
            'name' => TaskTypeService::JS_STATIC_ANALYSIS_TYPE,
        ]);

        $jsLintOutputIds = $this->taskRepository->getTaskOutputByType($jsStaticAnalysisType);

        $output->writeln('['.count($jsLintOutputIds).'] outputs to examine');

        $jsLintOutputCount = count($jsLintOutputIds);
        $processedJsLintOutputCount = 0;

        $totalTmpReferenceCount = 0;
        $totalFragmentLengthFixCount = 0;
        $totalReduction = 0;

        foreach ($jsLintOutputIds as $jsLintOutputId) {
            $processedJsLintOutputCount++;
            $output->writeln(sprintf(
                'Examining %s (%s remaining)',
                $jsLintOutputId,
                ($jsLintOutputCount - $processedJsLintOutputCount)
            ));

            /* @var Output $taskOutput */
            $taskOutput = $taskOutputRepository->find((int)$jsLintOutputId);

            $beforeLength = strlen($taskOutput->getOutput());

            $jsLintObject = json_decode($taskOutput->getOutput());

            if (is_int($jsLintObject)) {
                continue;
            }

            $matches = array();

            $tmpReferenceFixCount = 0;
            $fragmentLengthFixCount = 0;

            $statusLinePattern = '/"statusLine":"\\\\\/tmp\\\\\/[a-z0-9]{32}:[0-9]+:[0-9]+\.[0-9]+/';

            if (preg_match_all($statusLinePattern, $taskOutput->getOutput(), $matches)) {
                $output->write('    Fixing tmp file references ['.count($matches[0]).'] ... ');

                foreach ($jsLintObject as $sourcePath => $sourcePathOutput) {
                    // \/tmp\/b8d64d0bc142b3f670cc0611b0aebcae:113:1358342896.7425
                    // \/tmp\/dac78adf714a30493e2def48b5234dcf:308:1358373039.394 is OK

                    $tmpPathPattern = '/^\/tmp\/[a-z0-9]{32}:[0-9]+:[0-9]+\.[0-9]+$/';

                    if (preg_match($tmpPathPattern, $sourcePathOutput->statusLine)) {
                        $sourcePathOutput->statusLine = substr(
                            $sourcePathOutput->statusLine,
                            0,
                            strpos($sourcePathOutput->statusLine, ':')
                        );
                        $tmpReferenceFixCount++;
                    }

                    $tmpPathOkPattern = '/^\/tmp\/[a-z0-9]{32}:[0-9]+:[0-9]+\.[0-9]+ is OK.$/';

                    if (preg_match($tmpPathOkPattern, $sourcePathOutput->statusLine)) {
                        $sourcePathOutput->statusLine = substr(
                            $sourcePathOutput->statusLine,
                            0,
                            strpos($sourcePathOutput->statusLine, ':')
                        );
                        $tmpReferenceFixCount++;
                    }
                }

                $output->writeln('fixed '.$tmpReferenceFixCount.' tmp file references');
            }

            $output->write('    Fixing fragment lengths ... ');

            foreach ($jsLintObject as $sourcePath => $sourcePathOutput) {
                if (isset($sourcePathOutput->entries)) {
                    foreach ($sourcePathOutput->entries as $entry) {
                        if (strlen($entry->fragmentLine->fragment) > 256) {
                            $entry->fragmentLine->fragment = substr($entry->fragmentLine->fragment, 0, 256);
                            $fragmentLengthFixCount++;
                        }
                    }
                }
            }
            $output->writeln('fixed '.$fragmentLengthFixCount.' fragment lengths');

            if ($tmpReferenceFixCount > 0 || $fragmentLengthFixCount > 0) {
                $totalTmpReferenceCount += $tmpReferenceFixCount;
                $totalFragmentLengthFixCount += $fragmentLengthFixCount;

                $taskOutput->setOutput(json_encode($jsLintObject));
                $taskOutput->generateHash();

                $afterLength = strlen($taskOutput->getOutput());

                $reduction = $beforeLength - $afterLength;
                $totalReduction += $reduction;

                $reductionInK = round($reduction / (1024), 2);
                $reductionInM = round($reduction / (1024 * 1024), 2);
                $reductionInG = round($reduction / (1024 * 1024 * 1024), 2);

                if ($reductionInG > 1) {
                    $output->writeln('    Reduced output by '.$reductionInG.'Gb');
                } elseif ($reductionInM > 1) {
                    $output->writeln('    Reduced output by '.$reductionInM.'Mb');
                } else {
                    $output->writeln('    Reduced output by '.$reductionInK.'Kb');
                }

                if (!$isDryRun) {
                    $this->entityManager->persist($taskOutput);
                    $this->entityManager->flush();
                }
            }

            $this->entityManager->detach($taskOutput);
        }

        $output->writeln('==========================================');

        $output->writeln('Fixed '.$totalTmpReferenceCount.' tmp references');
        $output->writeln('Fixed '.$totalFragmentLengthFixCount.' fragment lengths');

        $totalReductionInK = round($totalReduction / (1024), 2);
        $totalReductionInM = round($totalReduction / (1024 * 1024), 2);
        $totalReductionInG = round($totalReduction / (1024 * 1024 * 1024), 2);

        if ($totalReductionInG > 1) {
            $output->writeln('Reduced total output by '.$totalReductionInG.'Gb');
        } elseif ($totalReductionInM > 1) {
            $output->writeln('Reduced total output by '.$totalReductionInM.'Mb');
        } else {
            $output->writeln('Reduced total output by '.$totalReductionInK.'Kb');
        }

        return self::RETURN_CODE_OK;
    }
}
