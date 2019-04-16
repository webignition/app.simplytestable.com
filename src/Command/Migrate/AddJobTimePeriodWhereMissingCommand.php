<?php
namespace App\Command\Migrate;

use App\Entity\Job\Job;
use App\Repository\JobRepository;
use App\Services\StateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddJobTimePeriodWhereMissingCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const FLUSH_THRESHOLD = 100;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @var StateService
     */
    private $stateService;

    public function __construct(
        EntityManagerInterface $entityManager,
        JobRepository $jobRepository,
        StateService $stateService,
        string $name = null
    ) {
        parent::__construct($name);

        $this->entityManager = $entityManager;
        $this->jobRepository = $jobRepository;
        $this->stateService = $stateService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:migrate:add-job-timeperiod-where-missing')
            ->setDescription('Add a timeperiod of now to failed-no-sitemap and rejected jobs lacking a timeperiod')
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

        if ($isDryRun) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }

        $output->write('Finding jobs ...');

        /* @var Job[] $jobs */
        $jobs = $this->jobRepository->findBy([
            'state' => [
                $this->stateService->get(Job::STATE_FAILED_NO_SITEMAP),
                $this->stateService->get(Job::STATE_REJECTED),
            ],
            'timePeriod' => null,
        ]);

        $jobCount = count($jobs);

        $output->writeln('<comment>' . $jobCount . '</comment> found');

        $now = new \DateTime();

        $flushCount = 0;
        $processedJobCount = 0;

        foreach ($jobs as $job) {
            $processedJobCount++;
            $flushCount++;

            $completionPercent = $processedJobCount === $jobCount
                ? 100
                : floor(($processedJobCount / $jobCount) * 100);

            $output->write('Processing job <comment>' . $job->getId() . '</comment> ... ');
            $output->writeln('<comment>' . $completionPercent . '%</comment> complete');

            if (empty($job->getTimePeriod())) {
                $job->setStartDateTime($now);
            }

            $job->setEndDateTime($now);

            $this->entityManager->persist($job);

            if (!$isDryRun && $flushCount >= self::FLUSH_THRESHOLD) {
                $this->entityManager->flush();
                $flushCount = 0;
            }
        }

        if (!$isDryRun && $flushCount >= 0) {
            $this->entityManager->flush();
        }

        return self::RETURN_CODE_OK;
    }
}
