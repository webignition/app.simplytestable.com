<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Delete\DeleteAction;

use SimplyTestable\ApiBundle\Controller\ScheduledJob\DeleteController;
use Symfony\Component\HttpFoundation\Response;

class NotFoundTest extends DeleteTest {

    /**
     * @var Response
     */
    private $response;

    protected function setUp() {
        parent::setUp();

        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $controller = new DeleteController();
        $controller->setContainer($this->container);

        $this->response = $controller->deleteAction(1);
    }

    public function testResponseStatusCode() {
        $this->assertEquals(404, $this->response->getStatusCode());
    }

}