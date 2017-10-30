<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\StatusController;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class StatusControllerTest extends AbstractBaseTestCase
{
    /**
     * @var StatusController
     */
    private $statusController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->statusController = new StatusController();
        $this->statusController->setContainer($this->container);
    }

    public function testIndexActionFoo()
    {
        $response = $this->statusController->indexAction();

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(JsonResponse::class, $response);
    }
}
