<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\TasksAction;

use SimplyTestable\ApiBundle\Tests\Controller\Job\Job\Access\PublicUserAccessTest as BasePubilcUserAccessTest;

class PublicUserAccessTest extends BasePubilcUserAccessTest {
    
    protected function getActionName() {
        return 'tasksAction';
    }

}