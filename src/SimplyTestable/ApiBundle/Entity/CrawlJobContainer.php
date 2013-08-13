<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\ApiBundle\Entity\State;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="CrawlJobContainer"
 * )
 * @ORM\Entity(repositoryClass="SimplyTestable\ApiBundle\Repository\CrawlJobContainerRepository")
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class CrawlJobContainer
{    
    /**
     * 
     * @var integer
     * 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * 
     */
    protected $id;    
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     * 
     * @ORM\OneToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Job")
     * @ORM\JoinColumn(name="crawl_job_id", referencedColumnName="id", nullable=false)
     * 
     */
    protected $crawlJob;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     * 
     * @ORM\OneToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Job")
     * @ORM\JoinColumn(name="parent_job_id", referencedColumnName="id", nullable=false)
     * 
     */
    protected $parentJob;    
    
    
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
    
    
    public function __construct()
    {
        $this->tasks = new \Doctrine\Common\Collections\ArrayCollection();
    }

    
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedState() {
        return str_replace('crawl-', '', (string)$this->getState());
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
     * @param SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return CrawlJobContainer
     */
    public function setCrawlJob(\SimplyTestable\ApiBundle\Entity\Job\Job $job)
    {
        $this->crawlJob = $job;
    
        return $this;
    }

    /**
     *
     * @return SimplyTestable\ApiBundle\Entity\Job\Job
     */
    public function getCrawlJob()
    {
        return $this->crawlJob;
    }    
    

    /**
     *
     * @param SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return CrawlJobContainer
     */
    public function setParentJob(\SimplyTestable\ApiBundle\Entity\Job\Job $job)
    {
        $this->parentJob = $job;
    
        return $this;
    }

    /**
     *
     * @return SimplyTestable\ApiBundle\Entity\Job\Job
     */
    public function getParentJob()
    {
        return $this->parentJob;
    }
}