<?php

namespace App\Tests\Unit\Services\Job;

use App\Services\Job\AuthorisationService;
use Mockery\Mock;
use App\Repository\JobRepository;
use App\Services\Job\RetrievalService;
use App\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;

class RetrievalServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testRetrieveFailure()
    {
        $jobId = 1;

        /* @var AuthorisationService $authorisationService */
        $authorisationService = \Mockery::mock(AuthorisationService::class);

        /* @var Mock|JobRepository $jobRepository */
        $jobRepository = \Mockery::mock(JobRepository::class);
        $jobRepository
            ->shouldReceive('exists')
            ->with($jobId)
            ->andReturn(false);

        $retrievalService = new RetrievalService($jobRepository, $authorisationService);

        $this->expectException(JobRetrievalServiceException::class);
        $this->expectExceptionMessage('Job [1] not found');
        $this->expectExceptionCode(JobRetrievalServiceException::CODE_JOB_NOT_FOUND);

        $retrievalService->retrieve($jobId);
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
