<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Maintenance;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class EnableBackupReadOnlyCommandTest extends BaseSimplyTestableTestCase {        
    
    const STATE_FILE_RELATIVE_PATH = '/test';
    
    public function testEnableBackupReadOnly() {    
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-backup-read-only'));
        $this->assertEquals('maintenance-backup-read-only', $this->getApplicationStateService()->getState());
        $this->assertFalse($this->getApplicationStateService()->isInActiveState());
        $this->assertFalse($this->getApplicationStateService()->isInMaintenanceReadOnlyState());
        $this->assertTrue($this->getApplicationStateService()->isInMaintenanceBackupReadOnlyState());
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
