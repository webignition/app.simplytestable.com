<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\StatusAction;

use SimplyTestable\ApiBundle\Tests\Controller\Job\Job\Access\PublicUserAccessTest as BasePubilcUserAccessTest;

class PublicUserAccessTest extends BasePubilcUserAccessTest {
    
    protected function getActionName() {
        return 'statusAction';
    }
}