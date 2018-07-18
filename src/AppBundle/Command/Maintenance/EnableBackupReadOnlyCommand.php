<?php
namespace AppBundle\Command\Maintenance;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnableBackupReadOnlyCommand extends AbstractApplicationStateChangeCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:enable-backup-read-only')
            ->setDescription('Enable read-only mode for backup purposes');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        return parent::setState($output, self::STATE_MAINTENANCE_BACKUP_READ_ONLY)
            ? self::RETURN_CODE_OK
            : self::RETURN_CODE_FAILURE;
    }
}
