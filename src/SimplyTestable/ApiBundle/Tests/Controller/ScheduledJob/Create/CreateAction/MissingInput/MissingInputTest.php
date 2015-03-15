<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\MissingInput;

use SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\Create\CreateAction\CreateTest;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class MissingInputTest extends CreateTest {

    private $fullRequestPostData = [
        'job-configuration' => 'foo',
        'schedule' => '* * * * *',
        'is-recurring' =>  '1'
    ];

    private $httpException;

    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        try {
            $methodName = $this->getActionNameFromRouter();
            $this->getCurrentController($this->getRequestPostData())->$methodName();
            $this->fail('HTTP 400 exception not raised when key "' . $this->getMissingRequestValueKey() . '" is missing');
        } catch (HttpException $httpException) {
            $this->httpException = $httpException;
        }
    }

    abstract protected function getMissingRequestValueKey();

    protected function getRequestPostData() {
        $requestPostData = $this->fullRequestPostData;
        unset($requestPostData[$this->getMissingRequestValueKey()]);
        return $requestPostData;
    }

    public function testHttpExceptionStatusCode() {
        $this->assertEquals(400, $this->httpException->getStatusCode());
    }

}