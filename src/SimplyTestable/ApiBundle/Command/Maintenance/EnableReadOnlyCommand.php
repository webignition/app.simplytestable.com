<?php
namespace SimplyTestable\ApiBundle\Command\Maintenance;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnableReadOnlyCommand extends AbstractApplicationStateChangeCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:enable-read-only')
            ->setDescription('Enable read-only mode');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        return parent::setState($output, self::STATE_MAINTENANCE_READ_ONLY)
            ? self::RETURN_CODE_OK
            : self::RETURN_CODE_FAILURE;
    }
}
