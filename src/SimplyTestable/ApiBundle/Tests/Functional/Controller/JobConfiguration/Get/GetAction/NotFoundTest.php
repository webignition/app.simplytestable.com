<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Get\GetAction;

use SimplyTestable\ApiBundle\Controller\JobConfiguration\GetController;
use Symfony\Component\HttpFoundation\Response;

class NotFoundTest extends GetTest {

    /**
     * @var Response
     */
    private $response;

    protected function setUp() {
        parent::setUp();

        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $controller = new GetController();
        $controller->setContainer($this->container);

        $this->response = $controller->getAction('foo');
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