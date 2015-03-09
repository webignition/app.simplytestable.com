<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update\UpdateAction\MissingInput;

use SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Update\UpdateAction\UpdateTest;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class MissingInputTest extends UpdateTest {

    private $httpException;

    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        try {
            $methodName = $this->getActionNameFromRouter();
            $this->getCurrentController($this->getRequestPostData())->$methodName(self::LABEL);
            $this->fail('HTTP 400 exception not raised when key "' . $this->getMissingRequestValueKey() . '" is missing');
        } catch (HttpException $httpException) {
            $this->httpException = $httpException;
        }
    }

    abstract protected function getMissingRequestValueKey();

    protected function getRequestPostData() {
        $requestPostData = parent::getRequestPostData();
        unset($requestPostData[$this->getMissingRequestValueKey()]);
        return $requestPostData;
    }

    public function testHttpExceptionStatusCode() {
        $this->assertEquals(400, $this->httpException->getStatusCode());
    }

}