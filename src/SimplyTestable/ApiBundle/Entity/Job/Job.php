<?php
namespace SimplyTestable\ApiBundle\Entity\Job;

use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\State;

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
     */
    protected $user;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\WebSite
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\WebSite")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", nullable=false)
     */
    protected $website;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\State
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     */
    protected $state; 
    
    
    /**
     *
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\OneToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Task", mappedBy="job")
     */
    protected $tasks;
    
    
    /**
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $startDateTime;
    
//    public function __construct()
//    {
//        $this->tasks = new \Doctrine\Common\Collections\ArrayCollection();
//    }
    
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
    public function __construct()
    {
        $this->tasks = new \Doctrine\Common\Collections\ArrayCollection();
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
}