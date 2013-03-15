<?php
namespace SimplyTestable\ApiBundle\Command\Maintenance;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class EnableBackupReadOnlyCommand extends Command
{ 
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:enable-backup-read-only')
            ->setDescription('Enable read-only mode for backup purposes')
            ->setHelp(<<<EOF
Enable read-only mode for backup purposes
EOF
        );
    }    
    
    public function execute(InputInterface $input, OutputInterface $output)
    { 
        return parent::setState($output, self::STATE_MAINTENANCE_BACKUP_READ_ONLY);
    }
}