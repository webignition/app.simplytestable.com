<?php
namespace SimplyTestable\ApiBundle\Services\Job;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint as AccountPlanConstraint;
use SimplyTestable\ApiBundle\Entity\Job\RejectionReason as JobRejectionReason;

class RejectionService { 
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\JobService 
     */
    private $jobService;
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Services\JobService $jobService
     */
    public function __construct(\SimplyTestable\ApiBundle\Services\JobService $jobService) {
        $this->jobService = $jobService;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @param string $reason
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint $constraint
     */
    public function reject(Job $job, $reason, AccountPlanConstraint $constraint = null) {
        $this->jobService->reject($job);

        $rejectionReason = new JobRejectionReason();
        $rejectionReason->setConstraint($constraint);
        $rejectionReason->setJob($job);
        $rejectionReason->setReason($reason);

        $this->jobService->getManager()->persist($rejectionReason);
        $this->jobService->getManager()->flush();
    }
  
}