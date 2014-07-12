<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\StatusAction;

use SimplyTestable\ApiBundle\Tests\Controller\Job\Job\Access\AbstractAccessTest;

class AccessTest extends AbstractAccessTest {
    
    protected function getActionName() {
        return 'statusAction';
    }
}