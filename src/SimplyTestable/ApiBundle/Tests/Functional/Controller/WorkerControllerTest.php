<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

class WorkerControllerTest extends BaseControllerJsonTestCase {

    public function testActivateAction() {
        $this->assertEquals(200, $this->getWorkerController('activateAction', array(
            'hostname' => 'test.worker.simplytestable.com',
            'token' => 'valid-token'
        ))->activateAction()->getStatusCode());
    }

    public function testActivateActionWithMissingHostname() {
        try {
            $this->getWorkerController('activateAction', array(
                'token' => 'valid-token'
            ))->activateAction()->getStatusCode();
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $httpException) {
            return $this->assertEquals(400, $httpException->getStatusCode());
        }

        $this->fail('WorkerController::activateAction() didn\'t throw a 400 HttpException for a missing hostname');
    }

    public function testActivateActionWithMissingToken() {
        try {
            $this->getWorkerController('activateAction', array(
                'hostname' => 'test.worker.simplytestable.com'
            ))->activateAction()->getStatusCode();
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $httpException) {
            return $this->assertEquals(400, $httpException->getStatusCode());
        }

        $this->fail('WorkerController::activateAction() didn\'t throw a 400 HttpException for a missing token');
    }
}
