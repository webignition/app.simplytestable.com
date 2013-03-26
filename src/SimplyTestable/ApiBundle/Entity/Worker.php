<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * @ORM\Entity
 */
class Worker
{
    /**
     * 
     * @var type integer
     * 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     *
     * @var string 
     * 
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected $hostname;
    
    
    /**
     *
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\OneToMany(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Task", mappedBy="worker")
     */
    protected $tasks;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\State
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=true)
     * 
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedState")
     * @SerializerAnnotation\Expose 
     */
    protected $state; 
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tasks = new \Doctrine\Common\Collections\ArrayCollection();
    }    
    
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedState() {
        return str_replace('worker-', '', (string)$this->getState());
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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set hostname
     *
     * @param string $hostname
     * @return Worker
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    
        return $this;
    }

    /**
     * Get hostname
     *
     * @return string 
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Add tasks
     *
     * @param SimplyTestable\ApiBundle\Entity\Task\Task $tasks
     * @return Worker
     */
    public function addTask(\SimplyTestable\ApiBundle\Entity\Task\Task $tasks)
    {
        $this->tasks[] = $tasks;
    
        return $this;
    }

    /**
     * Remove tasks
     *
     * @param SimplyTestable\ApiBundle\Entity\Task\Task $tasks
     */
    public function removeTask(\SimplyTestable\ApiBundle\Entity\Task\Task $tasks)
    {
        $this->tasks->removeElement($tasks);
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
     *
     * @param Worker $worker
     * @return boolean
     */
    public function equals(Worker $worker) {
        return $this->getHostname() == $worker->getHostname();
    }
}