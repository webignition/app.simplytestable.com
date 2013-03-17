<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Maintenance;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class DisableReadOnlyCommandTest extends BaseSimplyTestableTestCase {        
    
    const STATE_FILE_RELATIVE_PATH = '/test';
    
    public function testEnableReadOnly() {    
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:disable-read-only'));
        $this->assertEquals('active', $this->getApplicationStateService()->getState());
        $this->assertTrue($this->getApplicationStateService()->isInActiveState());
        $this->assertFalse($this->getApplicationStateService()->isInMaintenanceReadOnlyState());        
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
