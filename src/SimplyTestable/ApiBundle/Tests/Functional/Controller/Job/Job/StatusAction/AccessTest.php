<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\Access\AccessTest as BaseAccessTest;

class AccessTest extends BaseAccessTest {

    protected function getActionName() {
        return 'statusAction';
    }
}