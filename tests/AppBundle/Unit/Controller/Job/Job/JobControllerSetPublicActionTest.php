<?php

namespace Tests\AppBundle\Unit\Controller\Job\Job;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\AppBundle\Factory\MockFactory;
use AppBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

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

        $jobController = $this->createJobController($jobRetrievalService);

        $this->expectException(AccessDeniedHttpException::class);

        $jobController->setPublicAction(
            $userService,
            $user,
            self::CANONICAL_URL,
            self::JOB_ID
        );
    }
}
