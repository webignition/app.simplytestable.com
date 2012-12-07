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
 *     name="JobTaskTypeOptions"
 * )
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class TaskTypeOptions
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
     * @var SimplyTestable\ApiBundle\Entity\Job\Job 
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Job", inversedBy="taskTypeOptions")
     * @ORM\JoinColumn(name="job_id", referencedColumnName="id", nullable=false)     
     */
    protected $job;    
    
    
    /**
     *
     * @var TaskType
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Type\Type")
     * @ORM\JoinColumn(name="tasktype_id", referencedColumnName="id", nullable=false)
     */
    protected $taskType;
    
    
    /**
     * 
     * @var \Doctrine\Common\Collections\Collection
     * 
     * @ORM\Column(type="array", name="options", nullable=false)
     */
    protected $options;

    
    public function __construct()
    {
        $this->options = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set options
     *
     * @param array $options
     * @return TaskTypeOptions
     */
    public function setOptions($options)
    {
        $this->options = $options;
    
        return $this;
    }

    /**
     * Get options
     *
     * @return array 
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set job
     *
     * @param SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return TaskTypeOptions
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
     * Set taskType
     *
     * @param SimplyTestable\ApiBundle\Entity\Task\Type\Type $taskType
     * @return TaskTypeOptions
     */
    public function setTaskType(\SimplyTestable\ApiBundle\Entity\Task\Type\Type $taskType)
    {
        $this->taskType = $taskType;
    
        return $this;
    }

    /**
     * Get taskType
     *
     * @return SimplyTestable\ApiBundle\Entity\Task\Type\Type 
     */
    public function getTaskType()
    {
        return $this->taskType;
    }
    
    
    /**
     * 
     * @return int
     */
    public function getOptionCount() {
        return count($this->getOptions());
    }
    
    
    /**
     * 
     * @param string $optionName
     * @return mixed
     */
    public function getOption($optionName) {
        $options = $this->getOptions();
        return (isset($options[$optionName])) ? $options[$optionName] : null;
    }
    
    
    /**
     * 
     * @param string $optionName
     * @return boolean
     */
    public function hasOption($optionName) {
        return !is_null($this->getOption($optionName));
    }
}