<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Worker\Tasks\RequestAction\MissingQueryData;

use  SimplyTestable\ApiBundle\Tests\Controller\Worker\Tasks\RequestAction\RequestTest;

abstract class MissingQueryDataTest extends RequestTest {

    public function testThrowsHttpException400() {
        try {
            $methodName = $this->getActionNameFromRouter();
            $this->getCurrentController()->$methodName();
            $this->fail('Http 400 exception not thrown');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());
        }
    }
    
}