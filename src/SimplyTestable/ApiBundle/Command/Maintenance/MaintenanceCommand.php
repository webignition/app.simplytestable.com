<?php
namespace SimplyTestable\ApiBundle\Command\Maintenance;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


abstract class MaintenanceCommand extends BaseCommand
{ 
    const STATE_ACTIVE = 'active';
    const STATE_MAINTENANCE_READ_ONLY = 'maintenance-read-only';
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\ApplicationStateServic
     */
    private $applicationStateService;
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\ApplicationStateService
     */
    protected function getApplicationStateService() {         
        if (is_null($this->applicationStateService)) {
            $this->applicationStateService = $this->getContainer()->get('simplytestable.services.applicationStateService');
            $this->applicationStateService->setStateResourcePath($this->getStateResourcePath());
        }
        
        return $this->applicationStateService;
    }
    

    /**
     * 
     * @return string
     */
    private function getStateResourcePath() {
        return $this->getContainer()->get('kernel')->locateResource('@SimplyTestableApiBundle/Resources/config') . '/state-' . $this->getContainer()->get('kernel')->getEnvironment();
    }    
}