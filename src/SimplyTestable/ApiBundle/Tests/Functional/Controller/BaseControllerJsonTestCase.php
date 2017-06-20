<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

abstract class BaseControllerJsonTestCase extends BaseSimplyTestableTestCase {

    protected function createWebRequest() {
        $request = parent::createWebRequest();
        $request->headers->set('Accept', 'application/json');
        return $request;
    }

}
