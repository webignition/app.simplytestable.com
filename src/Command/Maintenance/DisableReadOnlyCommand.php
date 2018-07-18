<?php
namespace App\Command\Maintenance;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DisableReadOnlyCommand extends AbstractApplicationStateChangeCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:disable-read-only')
            ->setDescription('Disable read-only mode');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        return parent::setState($output, self::STATE_ACTIVE)
            ? self::RETURN_CODE_OK
            : self::RETURN_CODE_FAILURE;
    }
}
