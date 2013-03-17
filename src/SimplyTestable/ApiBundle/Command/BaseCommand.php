<?php

namespace SimplyTestable\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;

abstract class BaseCommand extends ContainerAwareCommand {
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\ApplicationStateServic
     */
    private $applicationStateService;
    
    
    /**
     *
     * @return \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    protected function getLogger() {
        return $this->getContainer()->get('logger');
    }
    
    
    /**
     * 
     * @param int $number
     * @return boolean
     */
    protected function isHttpStatusCode($number) {
        return strlen($number) == 3;
    } 
    

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
        return $this->getContainer()->get('kernel')->locateResource('@SimplyTestableApiBundle/Resources/config/state/') . $this->getContainer()->get('kernel')->getEnvironment();
    }    
    
}
