<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\JobConfiguration\Get\GetAction;

use Symfony\Component\HttpFoundation\Response;

class NotFoundTest extends GetTest {

    /**
     * @var Response
     */
    private $response;

    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController()->$methodName('foo');
    }

    public function testResponseStatusCode() {
        $this->assertEquals(404, $this->response->getStatusCode());
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'label' => 'foo'
        ];
    }
}