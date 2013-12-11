<?php
namespace SimplyTestable\ApiBundle\Entity\Job;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="JobFeatureOptions"
 * )
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class FeatureOptions
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
     * @ORM\OneToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Job", inversedBy="featureOptions", cascade={"persist"})
     * @ORM\JoinColumn(name="job_id", referencedColumnName="id", nullable=false)     
     */
    protected $job;    
    
    
    /**
     *
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */    
    protected $options;

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
     * @param string $options
     * @return FeatureOptions
     */
    public function setOtions($options)
    {
        $this->options = $options;    
        return $this;
    }

    /**
     * Get options
     *
     * @return string 
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set job
     *
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return FeatureOptions
     */
    public function setJob(\SimplyTestable\ApiBundle\Entity\Job\Job $job = null)
    {
        $this->job = $job;
        $job->setFeatureOptions($this);
    
        return $this;
    }

    /**
     * Get job
     *
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job 
     */
    public function getJob()
    {
        return $this->job;
    }
}