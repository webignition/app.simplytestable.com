<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\Get;

use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobException;

class WithoutUserTest extends ServiceTest {

    public function testCallWithoutSettingUserThrowsException() {
        $this->expectException(ScheduledJobException::class);
        $this->expectExceptionMessage('Matching scheduled job exists');
        $this->expectExceptionCode(ScheduledJobException::CODE_USER_NOT_SET);

        $this->getScheduledJobService()->get(1);
    }

}