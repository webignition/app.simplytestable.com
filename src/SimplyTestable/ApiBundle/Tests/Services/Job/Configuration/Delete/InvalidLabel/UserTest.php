<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Delete\InvalidLabel;

class UserTest extends InvalidLabelTest {

    protected function getCurrentUser() {
        return $this->getUserService()->getPublicUser();
    }
}