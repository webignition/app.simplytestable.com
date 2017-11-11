<?php

namespace Tests\ApiBundle\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\StatusController;
use SimplyTestable\ApiBundle\Services\ApplicationStatusFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
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

        $this->statusController = $this->container->get(StatusController::class);
    }

    public function testIndexAction()
    {
        $response = $this->statusController->indexAction(
            $this->container->get(ApplicationStatusFactory::class)
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(JsonResponse::class, $response);
    }
}
