<?php
namespace SimplyTestable\ApiBundle\Entity\Job;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="JobAmmendment"
 * )
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class Ammendment
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
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Job", inversedBy="ammendments")
     * @ORM\JoinColumn(name="job_id", referencedColumnName="id", nullable=false)     
     */
    protected $job;    
    
    
    /**
     *
     * @var string
     * @ORM\Column(type="string", nullable=false)
     * @SerializerAnnotation\Expose
     */
    protected $reason;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint")
     * @ORM\JoinColumn(name="constraint_id", referencedColumnName="id", nullable=true) 
     * @SerializerAnnotation\Expose
     */
    protected $constraint; 

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
     * Set reason
     *
     * @param string $reason
     * @return RejectionReason
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    
        return $this;
    }

    /**
     * Get reason
     *
     * @return string 
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set job
     *
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return RejectionReason
     */
    public function setJob(\SimplyTestable\ApiBundle\Entity\Job\Job $job = null)
    {
        $this->job = $job;
        $job->addAmmendment($this);
    
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

    /**
     * Set constraint
     *
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint $constraint
     * @return RejectionReason
     */
    public function setConstraint(\SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint $constraint = null)
    {
        $this->constraint = $constraint;
    
        return $this;
    }

    /**
     * Get constraint
     *
     * @return \SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint 
     */
    public function getConstraint()
    {
        return $this->constraint;
    }
}