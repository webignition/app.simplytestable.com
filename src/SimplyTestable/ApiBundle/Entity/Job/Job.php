<?php
namespace SimplyTestable\ApiBundle\Entity\Job;

use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="Job"
 * )
 * @ORM\Entity(repositoryClass="SimplyTestable\ApiBundle\Repository\JobRepository")
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class Job
{    
    /**
     * 
     * @var integer
     * 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * 
     * @SerializerAnnotation\Expose 
     */
    protected $id;
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\User
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * 
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedUser")
     * @SerializerAnnotation\Expose 
     */
    protected $user;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\WebSite
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\WebSite")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=false)
     * 
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedWebsite")
     * @SerializerAnnotation\Expose 
     */
    protected $website;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\State
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     * 
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedState")
     * @SerializerAnnotation\Expose 
     */
    protected $state; 
    
    
    /**
     * @var int
     * @SerializerAnnotation\Expose 
     */
    protected $urlCount;    
    
    
    /**
     *
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\OneToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Task", mappedBy="job")
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedTasks")     
     */
    private $tasks;
    

    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Type
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Type")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id", nullable=true)
     * 
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedType")
     * @SerializerAnnotation\Expose 
     */
    protected $type;     
    
    
    
    /**
     *
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\ManyToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Type\Type")
     * @ORM\JoinTable(name="JobTaskTypes",
     *      inverseJoinColumns={@ORM\JoinColumn(name="tasktype_id", referencedColumnName="id")},
     *      joinColumns={@ORM\JoinColumn(name="job_id", referencedColumnName="id")}
     * )
     * 
     * @SerializerAnnotation\SerializedName("task_types")
     * @SerializerAnnotation\Expose 
     */
    private $requestedTaskTypes;
    
    
    /**
     *
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\OneToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions", mappedBy="job")
     * 
     */    
    private $taskTypeOptions;  
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\TimePeriod
     * 
     * @ORM\OneToOne(targetEntity="SimplyTestable\ApiBundle\Entity\TimePeriod", cascade={"persist"})
     * @SerializerAnnotation\Expose 
     */
    protected $timePeriod;
    
    public function __construct()
    {
        $this->tasks = new \Doctrine\Common\Collections\ArrayCollection();
        $this->requestedTaskTypes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->taskTypeOptions = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedUser() {
        return $this->getUser()->getUsername();
    }
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedWebsite() {
        return (string)$this->getWebsite();
    }
    
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedState() {
        return str_replace('job-', '', (string)$this->getState());
    }
    
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedType() {
        return (string)$this->getType();
    }
        
    

    /**
     * 
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getPublicSerializedTasks() {
        $tasks = clone $this->getTasks();        
        foreach ($tasks as $task) {
            /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
            $task->setOutput(null);
        }
        
        return $tasks;
    }
    
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return Job
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get user
     *
     * @return SimplyTestable\ApiBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set website
     *
     * @param  $website
     * @return Job
     */
    public function setWebsite(WebSite $website)
    {
        $this->website = $website;
        return $this;
    }

    /**
     * Get website
     *
     * @return SimplyTestable\ApiBundle\Entity\Website 
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set state
     *
     * @param use SimplyTestable\ApiBundle\Entity\State $state
     * @return Job
     */
    public function setState(State $state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Get state
     *
     * @return use SimplyTestable\ApiBundle\Entity\State 
     */
    public function getState()
    {
        return $this->state;
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job 
     */
    public function setNextState() {
        if (!is_null($this->getState()->getNextState())) {
            $this->state = $this->getState()->getNextState();
        }        
        
        return $this;
    }   
    

    /**
     * Add tasks
     *
     * @param SimplyTestable\ApiBundle\Entity\Task\Task $task
     * @return Job
     */
    public function addTask(\SimplyTestable\ApiBundle\Entity\Task\Task $task)
    {
        $this->tasks[] = $task;
        return $this;
    }

    /**
     * Remove tasks
     *
     * @param <SimplyTestable\ApiBundle\Entity\Task\Task $task
     */
    public function removeTask(\SimplyTestable\ApiBundle\Entity\Task\Task $task)
    {
        $this->tasks->removeElement($task);
    }

    /**
     * Get tasks
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getTasks()
    {        
        return $this->tasks;
    }
    
    /**
     * Set timePeriod
     *
     * @param \SimplyTestable\ApiBundle\Entity\TimePeriod $timePeriod
     * @return Task
     */
    public function setTimePeriod(\SimplyTestable\ApiBundle\Entity\TimePeriod $timePeriod = null)
    {
        $this->timePeriod = $timePeriod;
    
        return $this;
    }

    /**
     * Get timePeriod
     *
     * @return \SimplyTestable\ApiBundle\Entity\TimePeriod 
     */
    public function getTimePeriod()
    {
        return $this->timePeriod;
    } 

    /**
     * Add requestedTaskTypeClasses
     *
     * @param SimplyTestable\ApiBundle\Entity\Task\Type\Type $requestedTaskType
     * @return Job
     */
    public function addRequestedTaskType(\SimplyTestable\ApiBundle\Entity\Task\Type\Type $requestedTaskType)
    {        
        if (!$this->requestedTaskTypes->contains($requestedTaskType)) {            
            $this->requestedTaskTypes[] = $requestedTaskType;            
        }
        
        return $this;
    }
    
    /**
     * Remove requestedTaskTypeClasses
     *
     * @param <variableType$requestedTaskTypeClasses
     */
    public function removeRequestedTaskType(\SimplyTestable\ApiBundle\Entity\Task\Type\Type $requestedTaskType)
    {
        $this->requestedTaskTypes->removeElement($requestedTaskType);
    }

    /**
     * Get requestedTaskTypeClasses
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getRequestedTaskTypes()
    {
        return $this->requestedTaskTypes;
    }
    
    
    /**
     *
     * @param Job $job
     * @return boolean 
     */
    public function equals(Job $job) {
        if (!$this->getState()->equals($job->getState())) {
            return false;
        }
        
        if (!$this->getUser()->equals($job->getUser())) {
            return false;
        }
        
        if (!$this->getWebsite()->equals($job->getWebsite())) {
            return false;
        }
        
        if (!$this->requestedTaskTypesEquals($job->getRequestedTaskTypes())) {
            return false;
        }
        
        return true;
    }
    
    
    /**
     *
     * @param \Doctrine\Common\Collections\Collection $requestedTaskTypes
     * @return boolean
     */
    private function requestedTaskTypesEquals(\Doctrine\Common\Collections\Collection $requestedTaskTypes) {
        /* @var $comparatorTaskType TaskType */
        /* @var $requestedTaskType TaskType */
        foreach ($requestedTaskTypes as $comparatorTaskType) {            
            foreach ($this->getRequestedTaskTypes() as $requestedTaskType) {
                if (!$requestedTaskType->equals(($comparatorTaskType))) {
                    return false;
                }
            }            
        }
        
        return true;
    }
    
    
    /**
     *
     * @return int 
     */
    public function getUrlCount() {
        return $this->urlCount;
    }
    
    
    /**
     *
     * @param int $urlCount
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job 
     */    
    public function setUrlCount($urlCount) {
        $this->urlCount = $urlCount;
        return $this;                
    }

    /**
     * Add taskTypeOptions
     *
     * @param SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions $taskTypeOptions
     * @return Job
     */
    public function addTaskTypeOption(\SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions $taskTypeOptions)
    {
        $this->taskTypeOptions[] = $taskTypeOptions;
    
        return $this;
    }

    /**
     * Remove taskTypeOptions
     *
     * @param SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions $taskTypeOptions
     */
    public function removeTaskTypeOption(\SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions $taskTypeOptions)
    {
        $this->taskTypeOptions->removeElement($taskTypeOptions);
    }

    /**
     * Get taskTypeOptions
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getTaskTypeOptions()
    {
        return $this->taskTypeOptions;
    }
    
    /**
     * Set type
     *
     * @param use SimplyTestable\ApiBundle\Entity\Job\Type $type
     * @return Job
     */
    public function setType(JobType $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return use SimplyTestable\ApiBundle\Entity\Job\Type
     */
    public function getType()
    {
        return $this->type;
    }    
}