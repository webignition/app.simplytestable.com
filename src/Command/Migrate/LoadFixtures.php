<?php

namespace App\Command\Migrate;

use App\Services\StateMigrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFixtures extends Command
{
    const RETURN_CODE_OK = 0;

    private $stateMigrator;

    public function __construct(
        StateMigrator $stateMigrator,
        $name = null
    ) {
        parent::__construct($name);

        $this->stateMigrator = $stateMigrator;
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

        $output->writeln('');

        return self::RETURN_CODE_OK;
    }
}
