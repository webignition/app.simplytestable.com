<?php

namespace App\Command\Maintenance;

use App\Command\Job\DeleteCommand;
use App\Repository\JobRepository;
use App\Services\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveJobsForPublicUserWithHttpAuthParameters extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_MISSING_REQUIRED_OPTION = 2;
    const MINIMUM_ID = 35000;
    const PARAMETER_FRAGMENT_PREFIX = '{"http-auth-username%';
    const DEFAULT_LIMIT = 10;

    private $jobRepository;
    private $userService;
    private $jobDeleteCommand;

    public function __construct(
        JobRepository $jobRepository,
        UserService $userService,
        DeleteCommand $jobDeleteCommand,
        $name = null
    ) {
        parent::__construct($name);

        $this->jobRepository = $jobRepository;
        $this->userService = $userService;
        $this->jobDeleteCommand = $jobDeleteCommand;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:remove-jobs-for-public-user-with-http-auth-parameters')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Run through the process without writing any data'
            )
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Limit number of jobs to delete, defaults to ' . self::DEFAULT_LIMIT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isDryRun = filter_var($input->getOption('dry-run'), FILTER_VALIDATE_BOOLEAN);
        $limit = filter_var($input->getOption('limit'), FILTER_VALIDATE_INT);
        if (!is_int($limit)) {
            $limit = self::DEFAULT_LIMIT;
        }

        if ($isDryRun) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
            $output->writeln('');
        }

        $output->write('Finding jobs ... ');

        $jobIds = $this->jobRepository->getIdsByUserAndMinimumIdAndParameterFragment(
            $this->userService->getPublicUser(),
            self::MINIMUM_ID,
            self::PARAMETER_FRAGMENT_PREFIX
        );

        $output->writeln('<comment>' . count($jobIds) . '</comment> found');

        $jobIdsToRemove = array_slice($jobIds, 0, $limit);

        $output->writeln('Deleting <comment>' . count($jobIdsToRemove) . '</comment> jobs ...');

        $deleteCommandArgs = [
            '--force' => true,
        ];

        if ($isDryRun) {
            $deleteCommandArgs['--dry-run'] = true;
        }

        if (count($jobIdsToRemove)) {
            foreach ($jobIdsToRemove as $jobId) {
                $output->writeln('Processing job <comment>' . $jobId . '</comment>');

                $deleteCommandArgs['id'] = $jobId;
                $this->jobDeleteCommand->run(new ArrayInput($deleteCommandArgs), new NullOutput());
            }
        }

        $jobIds = $this->jobRepository->getIdsByUserAndMinimumIdAndParameterFragment(
            $this->userService->getPublicUser(),
            self::MINIMUM_ID,
            self::PARAMETER_FRAGMENT_PREFIX
        );

        $output->writeln('<comment>' . count($jobIds) . '</comment> remaining');

        $output->writeln('');
        $output->writeln('<comment>Done</comment>');

        return self::RETURN_CODE_OK;
    }
}
