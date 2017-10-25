<?php
namespace SimplyTestable\ApiBundle\Entity\Job;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="JobRejectionReason"
 * )
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class RejectionReason
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Job
     *
     * @ORM\OneToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Job")
     */
    protected $job;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @SerializerAnnotation\Expose
     */
    protected $reason;

    /**
     * @var Constraint
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint")
     * @ORM\JoinColumn(name="constraint_id", referencedColumnName="id", nullable=true)
     * @SerializerAnnotation\Expose
     */
    protected $constraint;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $reason
     */
    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    /**
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param Job $job
     */
    public function setJob(Job $job = null)
    {
        $this->job = $job;
    }

    /**
     * @return Job
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param Constraint $constraint
     */
    public function setConstraint(Constraint $constraint = null)
    {
        $this->constraint = $constraint;
    }

    /**
     * @return Constraint
     */
    public function getConstraint()
    {
        return $this->constraint;
    }
}
