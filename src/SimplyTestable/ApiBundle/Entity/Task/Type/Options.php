<?php
namespace SimplyTestable\ApiBundle\Entity\Task\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="TaskTypeOptions"
 * )
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class Options
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
     * @var TaskType
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Type\Type")
     * @ORM\JoinColumn(name="tasktype_id", referencedColumnName="id", nullable=false)
     */
    protected $taskType;
    
    
    /**
     * 
     * @var DoctrineCollection
     *
     * @ORM\Column(type="array", name="options", nullable=false)
     */
    protected $options;

    
    public function __construct()
    {
        $this->options = new ArrayCollection();
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
     * @return Options
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
     * Set taskType
     *
     * @param TaskType $taskType
     * @return Options
     */
    public function setTaskType(TaskType $taskType)
    {
        $this->taskType = $taskType;
        return $this;
    }

    /**
     * Get taskType
     *
     * @return TaskType
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
