<?php
namespace SimplyTestable\ApiBundle\Entity\Job;

use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="Job"
 * )
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
     */
    protected $id;
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\User
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * 
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedUser")
     */
    protected $user;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\WebSite
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\WebSite")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=false)
     * 
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedWebsite")
     */
    protected $website;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\State
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     * 
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedState")
     */
    protected $state; 
    
    
    /**
     *
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\OneToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Task", mappedBy="job")
     * @SerializerAnnotation\Exclude
     */
    private $tasks;
    
    
    /**
     *
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\ManyToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Type\Type")
     * @ORM\JoinTable(name="JobTaskTypes",
     *      joinColumns={@ORM\JoinColumn(name="tasktype_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="job_id", referencedColumnName="id")}
     * )
     * 
     * @SerializerAnnotation\SerializedName("task_types")
     */
    private $requestedTaskTypes;
    
    
    /**
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $startDateTime;
    
    public function __construct()
    {
        $this->tasks = new \Doctrine\Common\Collections\ArrayCollection();
        $this->requestedTaskTypes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->startDateTime = new \DateTime();
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
        return (string)$this->getState();
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
        $this->state = $this->getState()->getNextState();
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
     * Set startDateTime
     *
     * @param datetime $startDateTime
     * @return Job
     */
    public function setStartDateTime($startDateTime)
    {
        $this->startDateTime = $startDateTime;
        return $this;
    }

    /**
     * Get startDateTime
     *
     * @return datetime 
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
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
}