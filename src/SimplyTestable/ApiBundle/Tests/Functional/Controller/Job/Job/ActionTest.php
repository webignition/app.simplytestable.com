<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

abstract class ActionTest extends BaseControllerJsonTestCase {


    /**
     * @param array $postData
     * @param array $queryData
     * @return \SimplyTestable\ApiBundle\Controller\Job\JobController
     */
    protected function getCurrentController(array $postData = [], array $queryData = []) {
        return parent::getCurrentController($postData, $queryData);
    }

}