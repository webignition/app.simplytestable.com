<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Create;

use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobException;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class WithoutUserTest extends ServiceTest {

    public function testCallWithoutSettingUserThrowsException() {
        $this->expectException(ScheduledJobException::class);
        $this->expectExceptionMessage('User is not set');
        $this->expectExceptionCode(ScheduledJobException::CODE_USER_NOT_SET);

        $this->getScheduledJobService()->create(
            new JobConfiguration()
        );
    }

}