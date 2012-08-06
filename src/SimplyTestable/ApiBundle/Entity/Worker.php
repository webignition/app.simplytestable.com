<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
     * Constructor
     */
    public function __construct()
    {
        $this->tasks = new \Doctrine\Common\Collections\ArrayCollection();
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