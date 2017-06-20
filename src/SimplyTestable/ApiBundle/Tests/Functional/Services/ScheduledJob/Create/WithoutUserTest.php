<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Create;

use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobException;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class WithoutUserTest extends ServiceTest {

    public function testCallWithoutSettingUserThrowsException() {
        $this->setExpectedException(
            'SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception',
            'User is not set',
            ScheduledJobException::CODE_USER_NOT_SET
        );

        $this->getScheduledJobService()->create(
            new JobConfiguration()
        );
    }

}