<?php

namespace Tests\ApiBundle\Unit\Services\Job;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Services\Job\RetrievalService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;
use Tests\ApiBundle\Factory\ModelFactory;

class RetrievalServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider retrieveFailureDataProvider
     *
     * @param User $user
     * @param string $expectedExceptionMessage
     * @param int $expectedExceptionCode
     */
    public function testRetrieveFailure(
        $user,
        $expectedExceptionMessage,
        $expectedExceptionCode
    ) {
        $this->expectException(JobRetrievalServiceException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->expectExceptionCode($expectedExceptionCode);

        /* @var TeamService $teamService */
        $teamService = \Mockery::mock(TeamService::class);

        /* @var JobRepository $jobRepository */
        $jobRepository = \Mockery::mock(JobRepository::class);
        $jobRepository
            ->shouldReceive('find')
            ->andReturnNull();

        $retrievalService = new RetrievalService($teamService, $jobRepository);
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
            ModelFactory::USER_EMAIL => 'user1@example.com',
        ]);

        return [
            'user not set' => [
                'user' => null,
                'expectedExceptionMessage' => 'User not set',
                'expectedExceptionCode' => JobRetrievalServiceException::CODE_USER_NOT_SET,
            ],
            'invalid job' => [
                'user' => $user1,
                'expectedExceptionMessage' => 'Job [1] not found',
                'expectedExceptionCode' => JobRetrievalServiceException::CODE_JOB_NOT_FOUND,
            ],
        ];
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
