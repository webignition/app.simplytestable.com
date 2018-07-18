<?php
namespace App\Command\Cron;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Cron\CronBundle\Command\CronRunCommand;

class RunCommand extends CronRunCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('simplytestable:cron:run')
            ->setDescription('Runs any currently schedule cron jobs; extends cron:run')
            ->addArgument('job', InputArgument::OPTIONAL, 'Run only this job (if enabled)')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force the current job.');
    }
}
