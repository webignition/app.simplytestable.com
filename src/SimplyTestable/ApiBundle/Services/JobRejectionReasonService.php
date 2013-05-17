<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;

class JobRejectionReasonService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Job\RejectionReason';
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return \SimplyTestable\ApiBundle\Entity\Job\RejectionReason
     */
    public function getForJob(Job $job) {
        return $this->getEntityRepository()->findOneByJob($job);
    }
  
}