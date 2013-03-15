<?php
namespace SimplyTestable\ApiBundle\Command\Maintenance;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class DisableReadOnlyCommand extends Command
{  

    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:disable-read-only')
            ->setDescription('Disable read-only mode')
            ->setHelp(<<<EOF
Disable read-only mode
EOF
        );
    }    
    
    public function execute(InputInterface $input, OutputInterface $output)
    {                
        if ($this->getApplicationStateService()->setState(self::STATE_ACTIVE)) {
            $output->writeln('Set application state to "'.self::STATE_ACTIVE.'"');
            return 0;
        }
        
        $output->writeln('Failed to set application state to "'.self::STATE_ACTIVE.'"');
        return 1;
    }  
}