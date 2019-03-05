<?php

namespace App\Tests\Unit\Services\Job;

use Mockery\Mock;
use App\Repository\JobRepository;
use App\Services\Job\RetrievalService;
use App\Services\Team\Service as TeamService;
use App\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use App\Tests\Factory\ModelFactory;

class RetrievalServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testRetrieveFailure()
    {
        $user = ModelFactory::createUser([
            ModelFactory::USER_EMAIL => 'user1@example.com',
        ]);

        /* @var Mock|TeamService $teamService */
        $teamService = \Mockery::mock(TeamService::class);

        /* @var Mock|JobRepository $jobRepository */
        $jobRepository = \Mockery::mock(JobRepository::class);
        $jobRepository
            ->shouldReceive('find')
            ->andReturnNull();

        /* @var Mock|TokenInterface $token */
        $token = \Mockery::mock(TokenInterface::class);
        $token
            ->shouldReceive('getUser')
            ->andReturn($user);

        /* @var TokenStorageInterface|Mock $tokenStorage */
        $tokenStorage = \Mockery::mock(TokenStorageInterface::class);
        $tokenStorage
            ->shouldReceive('getToken')
            ->andReturn($token);

        $retrievalService = new RetrievalService($teamService, $tokenStorage, $jobRepository);

        $this->expectException(JobRetrievalServiceException::class);
        $this->expectExceptionMessage('Job [1] not found');
        $this->expectExceptionCode(JobRetrievalServiceException::CODE_JOB_NOT_FOUND);

        $retrievalService->retrieve(1);
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
