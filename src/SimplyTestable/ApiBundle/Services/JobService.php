<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class JobService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Job\Job';
    
    /**
     *
     * @var \webignition\Http\Client\Client
     */
    private $stateService;    
    
    
    /**
     *
     * @param EntityManager $entityManager
     * @param \SimplyTestable\ApiBundle\Services\StateService $stateService 
     */
    public function __construct(EntityManager $entityManager, \SimplyTestable\ApiBundle\Services\StateService $stateService) {
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
     * @return \SimplyTestable\ApiBundle\Services\StateService
     */
    public function getStateService() {
        return $this->stateService;                
    }
    
    
    /**
     *
     * @param User $user
     * @param WebSite $website
     * @param array $taskTypes
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job 
     */
    public function create(User $user, WebSite $website, array $taskTypes) {
        $job = new Job();
        $job->setUser($user);
        $job->setWebsite($website);
        
        foreach ($taskTypes as $taskType) {
            if ($taskType instanceof TaskType) {
                $job->addRequestedTaskType($taskType);
            }
        }
        
        $statesToCheckFor = array(
            'job-queued',
            'job-new'
        );
        
        foreach($statesToCheckFor as $stateToCheckFor) {
            $job->setState($this->stateService->fetch($stateToCheckFor));
            if ($this->has($job)) {
                return $this->fetch($job);
            }
        }
        
        return $this->persistAndFlush($job);
    }
    
    
    /**
     *
     * @param Job $job
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job|boolean 
     */
    public function fetch(Job $job) {
        $jobs = $this->getEntityRepository()->findBy(array(
            'state' => $job->getState(),
            'user' => $job->getUser(),
            'website' => $job->getWebsite()
        ));
        
        /* @var $comparator Job */
        foreach ($jobs as $comparator) {
            if ($job->equals($comparator)) {
                return $comparator;
            }
        }
        
        return false;  
    }
    
    
    /**
     *
     * @param Job $job
     * @return boolean
     */
    public function has(Job $job) {        
        return $this->fetch($job) !== false;
    }
    
    
    /**
     *
     * @param Job $job
     * @return Job
     */
    public function persistAndFlush(Job $job) {
        $this->getEntityManager()->persist($job);
        $this->getEntityManager()->flush();
        return $job;
    }
}