<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * @ORM\Entity
 */
class WorkerTaskAssignment
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
     * @var SimplyTestable\ApiBundle\Entity\Worker
     * 
     * @ORM\OneToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Worker")
     * 
     */  
    protected $worker;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\Task\Task
     * 
     * @ORM\OneToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Task")
     * 
     */    
    protected $task;
    
    
    /**
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $dateTime;    
    
    

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
     * Set dateTime
     *
     * @param \DateTime $dateTime
     * @return WorkerLastTask
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;
    
        return $this;
    }

    /**
     * Get dateTime
     *
     * @return \DateTime 
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Set worker
     *
     * @param SimplyTestable\ApiBundle\Entity\Worker $worker
     * @return WorkerLastTask
     */
    public function setWorker(\SimplyTestable\ApiBundle\Entity\Worker $worker = null)
    {
        $this->worker = $worker;
    
        return $this;
    }

    /**
     * Get worker
     *
     * @return SimplyTestable\ApiBundle\Entity\Worker 
     */
    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * Set task
     *
     * @param SimplyTestable\ApiBundle\Entity\Task\Task $task
     * @return WorkerLastTask
     */
    public function setTask(\SimplyTestable\ApiBundle\Entity\Task\Task $task = null)
    {
        $this->task = $task;
    
        return $this;
    }

    /**
     * Get task
     *
     * @return SimplyTestable\ApiBundle\Entity\Task\Task 
     */
    public function getTask()
    {
        return $this->task;
    }
}