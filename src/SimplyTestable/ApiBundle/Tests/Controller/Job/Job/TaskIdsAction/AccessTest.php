<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\TaskIdsAction;

use SimplyTestable\ApiBundle\Tests\Controller\Job\Job\Access\AccessTest as BaseAccessTest;

class AccessTest extends BaseAccessTest {
    
    protected function getActionName() {
        return 'taskIdsAction';
    }
    
}