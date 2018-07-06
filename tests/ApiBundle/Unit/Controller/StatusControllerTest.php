<?php

namespace Tests\ApiBundle\Unit\Controller;

use Mockery\Mock;
use SimplyTestable\ApiBundle\Controller\StatusController;
use SimplyTestable\ApiBundle\Model\ApplicationStatus;
use SimplyTestable\ApiBundle\Services\ApplicationStatusFactory;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @group Controller/StatusController
 */
class StatusControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testIndexAction()
    {
        /* @var Mock|ApplicationStatus $applicationStatus */
        $applicationStatus = \Mockery::mock(ApplicationStatus::class);
        $applicationStatus
            ->shouldReceive('jsonSerialize')
            ->andReturn([]);

        /* @var Mock|ApplicationStatusFactory $applicationStatusFactory */
        $applicationStatusFactory = \Mockery::mock(ApplicationStatusFactory::class);
        $applicationStatusFactory
            ->shouldReceive('create')
            ->andReturn($applicationStatus);

        $statusController = new StatusController();

        $response = $statusController->indexAction($applicationStatusFactory);

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(JsonResponse::class, $response);

        \Mockery::close();
    }
}
