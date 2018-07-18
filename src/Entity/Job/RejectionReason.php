<?php
namespace App\Entity\Job;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Account\Plan\Constraint;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="JobRejectionReason"
 * )
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
     * @ORM\OneToOne(targetEntity="App\Entity\Job\Job")
     */
    protected $job;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $reason;

    /**
     * @var Constraint
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Account\Plan\Constraint")
     * @ORM\JoinColumn(name="constraint_id", referencedColumnName="id", nullable=true)
     */
    protected $constraint;

    /**
     * @return int
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

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $serialisedRejectionReason = [
            'reason' => $this->reason,
        ];

        if (!empty($this->constraint)) {
            $serialisedRejectionReason['constraint'] = $this->constraint->jsonSerialize();
        }

        return $serialisedRejectionReason;
    }
}
