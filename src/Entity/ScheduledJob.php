<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Job\Configuration as JobConfiguration;
use Cron\CronBundle\Entity\CronJob;

/**
 * @ORM\Entity
 */
class ScheduledJob implements \JsonSerializable
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Job\Configuration")
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
     */
    public function setJobConfiguration(JobConfiguration $jobConfiguration)
    {
        $this->jobConfiguration = $jobConfiguration;
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
     */
    public function setCronJob(CronJob $cronJob)
    {
        $this->cronJob = $cronJob;
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
     */
    public function setIsRecurring($isRecurring)
    {
        $this->isRecurring = $isRecurring;
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
     */
    public function setCronModifier($cronModifier)
    {
        $this->cronModifier = $cronModifier;
    }

    /**
     * @return string|null
     */
    public function getCronModifier()
    {
        return $this->cronModifier;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $data = [
            'id' => $this->getId(),
            'jobconfiguration' => $this->getJobConfiguration()->getLabel(),
            'schedule' => $this->getCronJob()->getSchedule(),
            'isrecurring' => (int)$this->getIsRecurring(),
        ];

        if (!empty($this->cronModifier)) {
            $data['schedule-modifier'] = $this->getCronModifier();
        }

        return $data;
    }
}
