<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Migrate;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class MigrateCanonicaliseTaskOutputCommandTest extends BaseSimplyTestableTestCase {        
    
    public function testRunCommandInMaintenanceReadOnlyModeReturnsStatusCode1() {
        $this->assertEquals(1, $this->runConsole('simplytestable:migrate:canonicalise-task-output'));
    }

}
