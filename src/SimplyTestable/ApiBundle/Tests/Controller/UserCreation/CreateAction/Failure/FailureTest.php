<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserCreation\CreateAction;

use SimplyTestable\ApiBundle\Tests\Controller\UserCreation\CreateAction\ActionTest;

abstract class FailureTest extends ActionTest {

    public function testCreateThrowsHttp400Exception() {
        try {
            $methodName = $this->getActionNameFromRouter();
            $this->getCurrentController($this->getRequestPostData())->$methodName();
            $this->fail('Attempt to create user with no email address did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());
        }
    }
    
}

