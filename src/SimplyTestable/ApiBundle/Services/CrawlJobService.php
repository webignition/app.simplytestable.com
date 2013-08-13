<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Job\CrawlJob;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class CrawlJobService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Job\CrawlJob';

    const COMPLETED_STATE = 'crawl-completed';
    const IN_PROGRESS_STATE = 'crawl-in-progress';
    const QUEUED_STATE = 'crawl-queued';
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\StateService
     */
    private $stateService;
    
    
    
    /**
     *
     * @param EntityManager $entityManager
     * @param \SimplyTestable\ApiBundle\Services\StateService $stateService 
     */
    public function __construct(
            EntityManager $entityManager,
            \SimplyTestable\ApiBundle\Services\StateService $stateService)
    {
        parent::__construct($entityManager);        
        $this->stateService = $stateService;
    }
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    /**
     *
     * @param int $id
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    public function getById($id) {
        return $this->getEntityRepository()->find($id);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return boolean
     */
    public function hasForJob(Job $job) {        
        return count($this->getEntityRepository()->findAllByJobAndStates($job, array(
            $this->getInProgressState(),
            $this->getQueuedState()
        ))) > 0;
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function getCompletedState() {
        return $this->stateService->fetch(self::COMPLETED_STATE);
    }

    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function getInProgressState() {
        return $this->stateService->fetch(self::IN_PROGRESS_STATE);
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\State
     */
    public function getQueuedState() {
        return $this->stateService->fetch(self::QUEUED_STATE);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return CrawlJob
     */
    public function create(Job $job) {        
        $crawlJob = new CrawlJob();
        $crawlJob->setJob($job);
        $crawlJob->setState($this->getQueuedState());
        
        return $this->persistAndFlush($crawlJob);
    }
    
    
    /**
     *
     * @param CrawlJob $job
     * @return CrawlJob
     */
    public function persistAndFlush(CrawlJob $crawlJob) {
        $this->getEntityManager()->persist($crawlJob);
        $this->getEntityManager()->flush();
        return $crawlJob;
    }

    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Repository\CrawlJobRepository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    }    
}