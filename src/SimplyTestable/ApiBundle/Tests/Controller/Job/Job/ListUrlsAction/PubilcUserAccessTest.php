<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\ListUrlsAction;

use SimplyTestable\ApiBundle\Tests\Controller\Job\Job\Access\PublicUserAccessTest as BasePubilcUserAccessTest;

class PubilcUserAccessTest extends BasePubilcUserAccessTest {
    
    protected function getActionName() {
        return 'listUrlsAction';
    }
    
}