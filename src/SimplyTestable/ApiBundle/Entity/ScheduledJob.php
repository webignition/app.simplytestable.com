<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use Cron\CronBundle\Entity\CronJob;

/**
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="SimplyTestable\ApiBundle\Repository\ScheduledJob\Repository")
 *
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class ScheduledJob
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var JobConfiguration
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Configuration")
     * @ORM\JoinColumn(name="jobconfiguration_id", referencedColumnName="id", nullable=false)
     */
    private $jobConfiguration;

    /**
     * @var CronJob
     *
     * @ORM\OneToOne(targetEntity="Cron\CronBundle\Entity\CronJob")
     * @ORM\JoinColumn(name="cronjob_id", referencedColumnName="id", nullable=false)
     */
    private $cronJob;

    /**
     * @var bool
     *
     * @ORM\Column(name="isRecurring", type="boolean")
     */
    private $isRecurring = true;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $cronModifier = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param JobConfiguration $jobConfiguration
     *
     * @return ScheduledJob
     */
    public function setJobConfiguration(JobConfiguration $jobConfiguration)
    {
        $this->jobConfiguration = $jobConfiguration;

        return $this;
    }

    /**
     * @return JobConfiguration
     */
    public function getJobConfiguration()
    {
        return $this->jobConfiguration;
    }

    /**
     * @param CronJob $cronJob
     *
     * @return ScheduledJob
     */
    public function setCronJob(CronJob $cronJob)
    {
        $this->cronJob = $cronJob;

        return $this;
    }

    /**
     * @return CronJob
     */
    public function getCronJob()
    {
        return $this->cronJob;
    }

    /**
     * @param bool $isRecurring
     *
     * @return ScheduledJob
     */
    public function setIsRecurring($isRecurring)
    {
        $this->isRecurring = $isRecurring;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsRecurring()
    {
        return $this->isRecurring;
    }

    /**
     * @param $cronModifier
     *
     * @return $this
     */
    public function setCronModifier($cronModifier)
    {
        $this->cronModifier = $cronModifier;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCronModifier()
    {
        return $this->cronModifier;
    }

    /**
     * @return bool
     */
    public function hasCronModifier()
    {
        return !is_null($this->cronModifier);
    }
}
