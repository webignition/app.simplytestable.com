<?php

namespace App\Command\Migrate;

use App\Services\AccountPlanMigrator;
use App\Services\JobTypeMigrator;
use App\Services\StateMigrator;
use App\Services\TaskTypeMigrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFixtures extends Command
{
    const RETURN_CODE_OK = 0;

    private $stateMigrator;
    private $accountPlanMigrator;
    private $jobTypeMigrator;
    private $taskTypeMigrator;

    public function __construct(
        StateMigrator $stateMigrator,
        AccountPlanMigrator $accountPlanMigrator,
        JobTypeMigrator $jobTypeMigrator,
        TaskTypeMigrator $taskTypeMigrator,
        $name = null
    ) {
        parent::__construct($name);

        $this->stateMigrator = $stateMigrator;
        $this->accountPlanMigrator = $accountPlanMigrator;
        $this->jobTypeMigrator = $jobTypeMigrator;
        $this->taskTypeMigrator = $taskTypeMigrator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:migrate:load-fixtures')
            ->setDescription('Load basic data required for service to operate');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stateMigrator->migrate($output);
        $this->accountPlanMigrator->migrate($output);
        $this->jobTypeMigrator->migrate($output);
        $this->taskTypeMigrator->migrate($output);

        $output->writeln('');

        return self::RETURN_CODE_OK;
    }
}