<?php
namespace SimplyTestable\ApiBundle\Entity\Job;

use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;
use SimplyTestable\ApiBundle\Entity\Task\Type\Options as TaskType_Options;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="JobTaskConfiguration"
 * )
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class TaskConfiguration
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
    private $id;


    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Configuration
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Configuration", inversedBy="taskConfigurations")
     * @ORM\JoinColumn(name="jobconfiguration_id", referencedColumnName="id", nullable=false)
     *
     */
    protected $jobConfiguration;


    /**
     *
     * @var TaskType
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Type\Type")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id", nullable=true)
     *
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedType")
     * @SerializerAnnotation\Expose
     */
    protected $type;



    /**
     *
     * @var DoctrineCollection
     *
     * @ORM\Column(type="array", name="options", nullable=false)
     *
     * @SerializerAnnotation\Expose
     */
    protected $options;


    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" = false})
     *
     * @SerializerAnnotation\Expose
     */
    private $isEnabled = false;


    public function __construct()
    {
        $this->options = new ArrayCollection();
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
     * Set type
     *
     * @param TaskType $type
     * @return TaskConfiguration
     */
    public function setType(TaskType $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return TaskType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set jobConfiguration
     *
     * @param Configuration $jobConfiguration
     * @return TaskConfiguration
     */
    public function setJobConfiguration(Configuration $jobConfiguration)
    {
        $this->jobConfiguration = $jobConfiguration;

        return $this;
    }

    /**
     * Get jobConfiguration
     *
     * @return \SimplyTestable\ApiBundle\Entity\Job\Configuration 
     */
    public function getJobConfiguration()
    {
        return $this->jobConfiguration;
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



    /**
     * Set options
     *
     * @param array $options
     * @return TaskConfiguration
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
     * @param TaskConfiguration $taskConfiguration
     * @return bool
     */
    public function hasMatchingTypeAndOptions(TaskConfiguration $taskConfiguration) {
        if ($this->getType()->getName() != $taskConfiguration->getType()->getName()) {
            return false;
        }

        if ($this->getOptionCount() != $taskConfiguration->getOptionCount()) {
            return false;
        }

        if ($this->getOptions() != $taskConfiguration->getOptions()) {
            return false;
        }

        return true;
    }


    /**
     * @param $isEnabled
     */
    public function setIsEnabled($isEnabled) {
        $this->isEnabled = $isEnabled;
    }


    /**
     * @return bool
     */
    public function getIsEnabled() {
        return $this->isEnabled;
    }
}
