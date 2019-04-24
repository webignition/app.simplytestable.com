<?php

namespace App\Command\Migrate;

use App\Entity\Job\Job;
use App\Repository\JobRepository;
use App\Services\JobService;
use App\Services\StateService;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\Factory as LockFactory;

class ExpirePublicUserJobs extends Command
{
    const NAME = 'simplytestable:migrate:expire-public-user-jobs';
    const LOCK_KEY = 'cmd:' . self::NAME;
    const LOCK_TTL = 1800; // 30 minutes in seconds

    const OPTION_MAX_AGE = 'max-age';
    const OPTION_LIMIT = 'limit';

    const RETURN_CODE_OK = 0;
    const RETURN_CODE_UNPARSEABLE_MAX_AGE = 1;
    const RETURN_CODE_UNABLE_TO_ACQUIRE_LOCK = 2;

    const DEFAULT_AGE = '24 HOUR';
    const DEFAULT_LIMIT = null;

    const FLUSH_THRESHOLD = 10;


    private $jobRepository;
    private $userService;
    private $stateService;
    private $jobService;
    private $entityManager;
    private $lockFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        JobRepository $jobRepository,
        UserService $userService,
        StateService $stateService,
        JobService $jobService,
        LockFactory $lockFactory,
        $name = null
    ) {
        parent::__construct($name);

        $this->jobRepository = $jobRepository;
        $this->userService = $userService;
        $this->stateService = $stateService;
        $this->jobService = $jobService;
        $this->entityManager = $entityManager;
        $this->lockFactory = $lockFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Expire old public user tests')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Run through the process without writing any data'
            )
            ->addOption(
                self::OPTION_MAX_AGE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Maximum age beyond which all tests will be expired (defaults to ' . self::DEFAULT_AGE . ')',
                self::DEFAULT_AGE
            )
            ->addOption(
                self::OPTION_LIMIT,
                null,
                InputOption::VALUE_OPTIONAL,
                'Limit to updating only N jobs (defaults to all)',
                self::DEFAULT_LIMIT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isDryRun = filter_var($input->getOption('dry-run'), FILTER_VALIDATE_BOOLEAN);

        if ($isDryRun) {
            $output->writeln([
                '<comment>This is a DRY RUN, no data will be written</comment>',
                '',
            ]);
        }

        $lock = null;

        if (!$isDryRun) {
            $lock = $this->lockFactory->createLock(self::LOCK_KEY, self::LOCK_TTL);
            if (!$lock->acquire()) {
                $output->writeln('Unable to acquire lock, ending');

                return self::RETURN_CODE_UNABLE_TO_ACQUIRE_LOCK;
            }
        }

        $limit = $input->getOption(self::OPTION_LIMIT);
        if (ctype_digit($limit)) {
            $limit = (int) $limit;
        }

        $maxAge = $input->getOption(self::OPTION_MAX_AGE);
        try {
            new \DateTimeImmutable('-' . $maxAge);
        } catch (\Exception $exception) {
            $output->writeln([
                '',
                '<error>Error:</error> unable to parse ' . self::OPTION_MAX_AGE .
                ' "<comment>' . $maxAge . '</comment>"'
            ]);

            return self::RETURN_CODE_UNPARSEABLE_MAX_AGE;
        }

        $states = [
            $this->stateService->get(Job::STATE_COMPLETED),
            $this->stateService->get(Job::STATE_CANCELLED),
        ];

        $output->write(
            'Finding <comment>completed or cancelled</comment> '.
            'public user jobs older than <comment>' . $maxAge . '</comment> ... '
        );

        $publicUser = $this->userService->getPublicUser();

        /* @var Job[] $jobs */
        $jobs = $this->jobRepository->findJobsForUserOlderThanMaxAgeWithStates($publicUser, self::DEFAULT_AGE, $states);
        $output->writeln([
            '<comment>' . count($jobs) . '</comment> found',
            '',
        ]);

        if (null === $limit) {
            $output->writeln('Processing <comment>all</comment> jobs ...');
        } else {
            $output->writeln('Processing up to <comment>' . $limit . '</comment> jobs ...');
        }

        $output->writeln('');

        if (is_int($limit)) {
            $jobs = array_slice($jobs, 0, $limit);
        }

        $jobCount = count($jobs);

        $flushCount = 0;
        $processedJobCount = 0;

        foreach ($jobs as $job) {
            $processedJobCount++;
            $completionPercent = $processedJobCount === $jobCount
                ? 100
                : (int) floor(($processedJobCount / $jobCount) * 100);

            $output->write('Processing job <comment>' . $job->getId() . '</comment> ... ');

            if (!$isDryRun) {
                $this->jobService->expire($job);
                $flushCount++;
            }

            if ($flushCount === self::FLUSH_THRESHOLD) {
                $this->entityManager->flush();
                $flushCount = 0;
            }

            $output->writeln(
                $completionPercent . '% done'
            );
        }

        if ($flushCount > 0) {
            $this->entityManager->flush();
        }

        if ($lock) {
            $lock->release();
        }

        return self::RETURN_CODE_OK;
    }
}
