<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Maintenance;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class EnableReadOnlyCommandTest extends ConsoleCommandTestCase {        
    
    const STATE_FILE_RELATIVE_PATH = '/test';
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:maintenance:enable-read-only';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand()
        );
    }     
    
    public function testEnableReadOnly() {    
        $this->assertReturnCode(0);
        $this->assertEquals('maintenance-read-only', $this->getApplicationStateService()->getState());
        $this->assertFalse($this->getApplicationStateService()->isInActiveState());
        $this->assertTrue($this->getApplicationStateService()->isInMaintenanceReadOnlyState());
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\ApplicationStateService
     */
    protected function getApplicationStateService() {
        $applicationStateService = $this->container->get('simplytestable.services.applicationStateService');
        $applicationStateService->setStateResourcePath($this->getStateResourcePath());
        
        return $applicationStateService;
    }
    

    /**
     * 
     * @return string
     */
    private function getStateResourcePath() {
        return $this->container->get('kernel')->locateResource('@SimplyTestableApiBundle/Resources/config/state') . self::STATE_FILE_RELATIVE_PATH;
    }

}
