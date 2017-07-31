<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Create\CreateAction\Failure;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class UnknownJobConfigurationTest extends FailureTest {

    protected function getCurrentUser() {
        $userFactory = new UserFactory($this->container);

        return $userFactory->createAndActivateUser();
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