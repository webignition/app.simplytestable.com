<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Owns;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class WithoutServiceUserSetTest extends ServiceTest {

    public function testReturnsFalseIfServiceUserIsNotSet() {
        $this->assertFalse($this->getJobConfigurationService()->owns(new JobConfiguration()));
    }

}