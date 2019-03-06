<?php

namespace App\Tests\Unit\Controller\Job\Job;

use App\Services\Job\RetrievalService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Tests\Factory\MockFactory;
use App\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

/**
 * @group Controller/Job/JobController
 */
class JobControllerSetPublicActionTest extends AbstractJobControllerTest
{
    const CANONICAL_URL = 'http://example.com/';
    const JOB_ID = 1;

    public function testSetPublicActionJobRetrievalFailure()
    {
        $user = MockFactory::createUser();

        $userService = MockFactory::createUserService([
            'isPublicUser' => [
                'with' => $user,
                'return' => false,
            ],
        ]);

        $jobRetrievalService = MockFactory::createJobRetrievalService([
            'retrieve' => [
                'with' => self::JOB_ID,
                'throw' => new JobRetrievalServiceException(),
            ],
        ]);

        $jobController = $this->createJobController([
            RetrievalService::class => $jobRetrievalService,
        ]);

        $this->expectException(AccessDeniedHttpException::class);

        $jobController->setPublicAction(
            $userService,
            $user,
            self::CANONICAL_URL,
            self::JOB_ID
        );
    }
}
