<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\EmptyNewLabel;

class UserTest extends EmptyNewLabelTest {

    protected function getCurrentUser() {
        return $this->getUserService()->getPublicUser();
    }
}