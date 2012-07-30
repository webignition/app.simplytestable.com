<?php
namespace SimplyTestable\ApiBundle\Entity\Task;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="Task"
 * )
 * 
 */
class Task
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
     * @var SimplyTestable\ApiBundle\Entity\Job\Job 
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Job", inversedBy="tasks")
     * @ORM\JoinColumn(name="job_id", referencedColumnName="id", nullable=false)
     */
    protected $job;
    
    
    /**
     *
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    protected $url;
    
    
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
     * @var SimplyTestable\ApiBundle\Entity\Worker 
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Worker", inversedBy="tasks")
     */
    protected $worker;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\Task\Type\Type
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Type\Type")
     * @ORM\JoinColumn(name="tasktype_id", referencedColumnName="id", nullable=false)
     * 
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedType")
     */
    protected $type;
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedState() {
        return (string)$this->getState();
    }  
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedType() {
        return (string)$this->getType();
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
     * Set url
     *
     * @param text $url
     * @return Task
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Get url
     *
     * @return text 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set job
     *
     * @param SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return Task
     */
    public function setJob(\SimplyTestable\ApiBundle\Entity\Job\Job $job)
    {
        $this->job = $job;
        return $this;
    }

    /**
     * Get job
     *
     * @return SimplyTestable\ApiBundle\Entity\Job\Job 
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Set state
     *
     * @param SimplyTestable\ApiBundle\Entity\State $state
     * @return Task
     */
    public function setState(\SimplyTestable\ApiBundle\Entity\State $state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Get state
     *
     * @return SimplyTestable\ApiBundle\Entity\State 
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set worker
     *
     * @param SimplyTestable\ApiBundle\Entity\Worker $worker
     * @return Task
     */
    public function setWorker(\SimplyTestable\ApiBundle\Entity\Worker $worker = null)
    {
        $this->worker = $worker;
        return $this;
    }
    
    
    /**
     *
     * @return Task
     */
    public function clearWorker() {
        return $this->setWorker(null);
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
     * Set type
     *
     * @param SimplyTestable\ApiBundle\Entity\Task\Type\Type $type
     * @return Task
     */
    public function setType(\SimplyTestable\ApiBundle\Entity\Task\Type\Type $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return SimplyTestable\ApiBundle\Entity\Task\Type\Type 
     */
    public function getType()
    {
        return $this->type;
    }
}