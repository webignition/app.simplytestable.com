<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Services\Job;

use Mockery\MockInterface;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\Job\RetrievalService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;
use SimplyTestable\ApiBundle\Tests\Factory\ModelFactory;

class RetrievalServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider retrieveFailureDataProvider
     *
     * @param JobService $jobService
     * @param User $user
     * @param string $expectedExceptionMessage
     * @param int $expectedExceptionCode
     */
    public function testRetrieveFailure(
        JobService $jobService,
        $user,
        $expectedExceptionMessage,
        $expectedExceptionCode
    ) {
        $this->setExpectedException(
            JobRetrievalServiceException::class,
            $expectedExceptionMessage,
            $expectedExceptionCode
        );

        /* @var TeamService $teamService */
        $teamService = \Mockery::mock(TeamService::class);

        $retrievalService = new RetrievalService($jobService, $teamService);
        if (!empty($user)) {
            $retrievalService->setUser($user);
        }

        $retrievalService->retrieve(1);
    }

    /**
     * @return array
     */
    public function retrieveFailureDataProvider()
    {
        $user1 = ModelFactory::createUser([
            'email' => 'user1@example.com',
        ]);

        return [
            'user not set' => [
                'jobService' => $this->createJobService(null),
                'user' => null,
                'expectedExceptionMessage' => 'User not set',
                'expectedExceptionCode' => JobRetrievalServiceException::CODE_USER_NOT_SET,
            ],
            'invalid job' => [
                'jobService' => $this->createJobService(null),
                'user' => $user1,
                'expectedExceptionMessage' => 'Job [1] not found',
                'expectedExceptionCode' => JobRetrievalServiceException::CODE_JOB_NOT_FOUND,
            ],
        ];
    }

    /**
     * @param Job|null $getByIdReturnValue
     * @return MockInterface|JobService
     */
    private function createJobService($getByIdReturnValue)
    {
        $jobService = \Mockery::mock(JobService::class);
        $jobService
            ->shouldReceive('getById')
            ->andReturn($getByIdReturnValue);

        return $jobService;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
