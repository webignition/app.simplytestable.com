<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Delete\DeleteAction;

use Symfony\Component\HttpFoundation\Response;

class NotFoundTest extends DeleteTest {

    /**
     * @var Response
     */
    private $response;

    protected function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController()->$methodName(1);
    }

    public function testResponseStatusCode() {
        $this->assertEquals(404, $this->response->getStatusCode());
    }

}