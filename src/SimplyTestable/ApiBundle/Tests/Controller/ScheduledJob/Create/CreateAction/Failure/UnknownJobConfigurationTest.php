<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\Failure;

class UnknownJobConfigurationTest extends FailureTest {

    protected function getCurrentUser() {
        return $this->createAndActivateUser('user@example.com');
    }

    protected function getHeaderErrorCode()
    {
        return 98;
    }

    protected function getHeaderErrorMessage()
    {
        return 'Unknown job configuration';
    }
}