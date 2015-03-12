<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use Cron\CronBundle\Entity\CronJob;

/**
 * @ORM\Entity
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class ScheduledJob
{
    /**
     * 
     * @var integer
     * 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


    /**
     *
     * @var JobConfiguration
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Configuration")
     * @ORM\JoinColumn(name="jobconfiguration_id", referencedColumnName="id", nullable=false)
     *
     * @SerializerAnnotation\Expose
     */
    private $jobConfiguration;


    /**
     *
     * @var CronJob
     *
     * @ORM\OneToOne(targetEntity="Cron\CronBundle\Entity\CronJob")
     * @ORM\JoinColumn(name="cronjob_id", referencedColumnName="id", nullable=false)
     */
    private $cronJob;


    /**
     * @var boolean
     *
     * @ORM\Column(name="isRecurring", type="boolean")
     */
    private $isRecurring = true;


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
     * Set jobConfiguration
     *
     * @param JobConfiguration $jobConfiguration
     * @return ScheduledJob
     */
    public function setJobConfiguration(JobConfiguration $jobConfiguration)
    {
        $this->jobConfiguration = $jobConfiguration;

        return $this;
    }

    /**
     * Get jobConfiguration
     *
     * @return JobConfiguration
     */
    public function getJobConfiguration()
    {
        return $this->jobConfiguration;
    }

    /**
     * Set cronJob
     *
     * @param CronJob $cronJob
     * @return ScheduledJob
     */
    public function setCronJob(CronJob $cronJob)
    {
        $this->cronJob = $cronJob;

        return $this;
    }

    /**
     * Get cronJob
     *
     * @return CronJob
     */
    public function getCronJob()
    {
        return $this->cronJob;
    }

    /**
     * Set isRecurring
     *
     * @param boolean $isRecurring
     * @return ScheduledJob
     */
    public function setIsRecurring($isRecurring)
    {
        $this->isRecurring = $isRecurring;

        return $this;
    }

    /**
     * Get isRecurring
     *
     * @return boolean 
     */
    public function getIsRecurring()
    {
        return $this->isRecurring;
    }
}
