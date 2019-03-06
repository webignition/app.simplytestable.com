<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Unit\Controller\Job\Job;

use App\Services\Job\AuthorisationService;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @group Controller/Job/JobController
 */
class JobControllerIsAuthorisedActionTest extends AbstractJobControllerTest
{
    const CANONICAL_URL = 'http://example.com/';
    const JOB_ID = 1;

    /**
     * @dataProvider isAuthorisedDataProvider
     */
    public function testIsAuthorised(int $jobAuthorisationServiceIsAuthorised)
    {
        $jobId = 1;

        $jobAuthorisationService = \Mockery::mock(AuthorisationService::class);
        $jobAuthorisationService
            ->shouldReceive('isAuthorised')
            ->with($jobId)
            ->andReturn($jobAuthorisationServiceIsAuthorised);

        $jobController = $this->createJobController([
            AuthorisationService::class => $jobAuthorisationService,
        ]);

        /* @var JsonResponse $response */
        $response = $jobController->isAuthorisedAction(1);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $decodedResponseData = json_decode($response->getContent());

        $this->assertEquals($jobAuthorisationServiceIsAuthorised, $decodedResponseData);
    }

    public function isAuthorisedDataProvider(): array
    {
        return [
            'is authorised' => [
                'jobAuthorisationServiceIsAuthorised' => true,
            ],
            'is not authorised' => [
                'jobAuthorisationServiceIsAuthorised' => false,
            ],
        ];
    }
}
