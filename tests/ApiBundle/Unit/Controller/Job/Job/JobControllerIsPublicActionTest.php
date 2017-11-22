<?php

namespace Tests\ApiBundle\Unit\Controller\Job\Job;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use Tests\ApiBundle\Factory\MockFactory;

/**
 * @group Controller/Job/JobController
 */
class JobControllerIsPublicActionTest extends AbstractJobControllerTest
{
    const TEST_ID = 1;

    /**
     * @dataProvider isPublicActionDataProvider
     *
     * @param JobRepository $jobRepository
     * @param int $expectedResponseStatusCode
     */
    public function testIsPublicAction(JobRepository $jobRepository, $expectedResponseStatusCode)
    {
        $entityManager = MockFactory::createEntityManager();
        $entityManager
            ->shouldReceive('getRepository')
            ->with(Job::class)
            ->andReturn($jobRepository);

        $jobController = $this->createJobController(null, $entityManager);

        $response = $jobController->isPublicAction('', self::TEST_ID);

        $this->assertEquals($expectedResponseStatusCode, $response->getStatusCode());
    }

    /**
     * @return array
     */
    public function isPublicActionDataProvider()
    {
        return [
            'is public' => [
                'jobRepository' => MockFactory::createJobRepository([
                    'getIsPublicByJobId' => [
                        'with' => self::TEST_ID,
                        'return' => true,
                    ],
                ]),
                'expectedStatusCode' => 200,
            ],
            'is not public' => [
                'jobRepository' => MockFactory::createJobRepository([
                    'getIsPublicByJobId' => [
                        'with' => self::TEST_ID,
                        'return' => false,
                    ],
                ]),
                'expectedStatusCode' => 404,
            ],
        ];
    }
}
