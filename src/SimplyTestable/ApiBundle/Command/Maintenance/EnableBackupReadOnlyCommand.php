<?php
namespace SimplyTestable\ApiBundle\Command\Maintenance;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnableBackupReadOnlyCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:enable-backup-read-only')
            ->setDescription('Enable read-only mode for backup purposes')
            ->setHelp('Enable read-only mode for backup purposes');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        return parent::setState($output, self::STATE_MAINTENANCE_BACKUP_READ_ONLY);
    }
}
