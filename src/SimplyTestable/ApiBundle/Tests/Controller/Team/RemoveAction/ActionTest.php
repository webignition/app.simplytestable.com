<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Team\RemoveAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

abstract class ActionTest extends BaseControllerJsonTestCase {


    protected function getCurrentController(array $postData = [], array $queryData = []) {
        return parent::getCurrentController($postData, $queryData);
    }

}