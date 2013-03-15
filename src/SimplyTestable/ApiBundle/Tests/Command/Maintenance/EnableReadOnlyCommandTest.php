<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Maintenance;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class EnableReadOnlyCommandTest extends BaseSimplyTestableTestCase {        
    
    const STATE_FILE_RELATIVE_PATH = '/state-test';
    
    public function testEnableReadOnly() {    
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));
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
        return $this->container->get('kernel')->locateResource('@SimplyTestableApiBundle/Resources/config') . self::STATE_FILE_RELATIVE_PATH;
    }

}
