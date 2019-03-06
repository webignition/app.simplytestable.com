<?php

namespace App\Services\Job;

use App\Entity\Job\Job;
use App\Exception\Services\Job\RetrievalServiceException as JobRetrievalServiceException;
use App\Repository\JobRepository;

class RetrievalService
{
    private $jobRepository;
    private $authorisationService;

    public function __construct(JobRepository $jobRepository, AuthorisationService $authorisationService)
    {
        $this->jobRepository = $jobRepository;
        $this->authorisationService = $authorisationService;
    }

    /**
     * @param int $jobId
     * @throws JobRetrievalServiceException
     *
     * @return Job
     */
    public function retrieve($jobId)
    {
        if (!$this->jobRepository->exists($jobId)) {
            throw new JobRetrievalServiceException(
                'Job [' . $jobId . '] not found',
                JobRetrievalServiceException::CODE_JOB_NOT_FOUND
            );
        }

        if (!$this->authorisationService->isAuthorised($jobId)) {
            throw new JobRetrievalServiceException(
                'Not authorised',
                JobRetrievalServiceException::CODE_NOT_AUTHORISED
            );
        }

        /* @var Job $job */
        $job = $this->jobRepository->find($jobId);

        return $job;
    }
}
