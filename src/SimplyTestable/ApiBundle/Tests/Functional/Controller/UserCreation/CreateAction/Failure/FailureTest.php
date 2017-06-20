<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\CreateAction\Failure;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\ActionTest;

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

