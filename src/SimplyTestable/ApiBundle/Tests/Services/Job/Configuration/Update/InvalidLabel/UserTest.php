<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\InvalidLabel;

class UserTest extends InvalidLabelTest {

    protected function getCurrentUser() {
        return $this->getUserService()->getPublicUser();
    }
}