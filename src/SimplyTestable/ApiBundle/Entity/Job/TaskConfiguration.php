<?php
namespace SimplyTestable\ApiBundle\Entity\Job;

use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;
use SimplyTestable\ApiBundle\Entity\Task\Type\Options as TaskType_Options;

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
     * @SerializerAnnotation\Expose 
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
     * @var \SimplyTestable\ApiBundle\Entity\Task\Type\Options
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Type\Options")
     * @ORM\JoinColumn(name="options_id", referencedColumnName="id", nullable=true)
     *
     */
    protected $options;


    
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
     * Set options
     *
     * @param TaskType_Options $options
     * @return TaskConfiguration
     */
    public function setOptions(TaskType_Options $options = null)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get options
     *
     * @return TaskType_Options
     */
    public function getOptions()
    {
        return $this->options;
    }
}
