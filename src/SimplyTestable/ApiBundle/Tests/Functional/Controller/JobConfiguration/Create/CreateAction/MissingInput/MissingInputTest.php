<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Create\CreateAction\MissingInput;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Create\CreateAction\CreateTest;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class MissingInputTest extends CreateTest {

    private $fullRequestPostData = [
        'label' => 'foo',
        'website' => 'http://example.com/',
        'type' => 'Full site',
        'task-configuration' => [
            'HTML validation' => [],
            'CSS validation' => []
        ],
        'parameters' => ''
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