<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\EmptyNewLabel;

class UserTest extends EmptyNewLabelTest {

    protected function getCurrentUser() {
        return $this->getUserService()->getPublicUser();
    }
}