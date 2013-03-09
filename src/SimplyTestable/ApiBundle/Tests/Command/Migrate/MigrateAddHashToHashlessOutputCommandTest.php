<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Migrate;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class MigrateAddHashToHashlessOutputCommandTest extends BaseSimplyTestableTestCase {        
    
    public function testRunCommandInMaintenanceReadOnlyModeReturnsStatusCode1() {
        $this->assertEquals(1, $this->runConsole('simplytestable:add-hash-to-hashless-output'));
    }

}
