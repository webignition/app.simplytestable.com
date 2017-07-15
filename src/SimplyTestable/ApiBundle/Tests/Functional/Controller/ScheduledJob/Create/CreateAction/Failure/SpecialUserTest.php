<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Create\CreateAction\Failure;

class SpecialUserTest extends FailureTest {

    protected function getCurrentUser() {
        return $this->getUserService()->getPublicUser();
    }

    protected function getHeaderErrorCode()
    {
        return 99;
    }

    protected function getHeaderErrorMessage()
    {
        return 'Special users cannot create scheduled jobs';
    }
}