<?php
namespace SimplyTestable\ApiBundle\Command\Maintenance;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class EnableReadOnlyCommand extends Command
{ 
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:enable-read-only')
            ->setDescription('Enable read-only mode')
            ->setHelp(<<<EOF
Enable read-only mode
EOF
        );
    }    
    
    public function execute(InputInterface $input, OutputInterface $output)
    {                
        if ($this->getApplicationStateService()->setState(self::STATE_MAINTENANCE_READ_ONLY)) {
            $output->writeln('Set application state to "'.self::STATE_MAINTENANCE_READ_ONLY.'"');
            return 0;
        }
        
        $output->writeln('Failed to set application state to "'.self::STATE_MAINTENANCE_READ_ONLY.'"');
        return 1;
    }
}