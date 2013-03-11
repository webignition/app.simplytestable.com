<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Migrate;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class MigrateCanonicaliseTaskOutputCommandTest extends BaseSimplyTestableTestCase {        
    
    public function testRunCommandInMaintenanceReadOnlyModeReturnsStatusCode1() {
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));
        $this->assertEquals(1, $this->runConsole('simplytestable:migrate:canonicalise-task-output'));
    }

}
