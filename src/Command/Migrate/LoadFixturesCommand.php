<?php

namespace App\Command\Migrate;

use App\Services\FixtureLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFixturesCommand extends Command
{
    const RETURN_CODE_OK = 0;

    private $fixtureLoader;

    public function __construct(FixtureLoader $fixtureLoader, $name = null)
    {
        parent::__construct($name);

        $this->fixtureLoader = $fixtureLoader;
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
        $this->fixtureLoader->load($output);
        $output->writeln('');

        return self::RETURN_CODE_OK;
    }
}
