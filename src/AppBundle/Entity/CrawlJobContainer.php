<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\Job\Job;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="CrawlJobContainer"
 * )
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CrawlJobContainerRepository")
 */
class CrawlJobContainer
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
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Job\Job")
     * @ORM\JoinColumn(name="crawl_job_id", referencedColumnName="id", nullable=false)
     */
    protected $crawlJob;

    /**
     * @var Job
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Job\Job")
     * @ORM\JoinColumn(name="parent_job_id", referencedColumnName="id", nullable=false)
     */
    protected $parentJob;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Job $job
     */
    public function setCrawlJob(Job $job)
    {
        $this->crawlJob = $job;
    }

    /**
     *
     * @return Job
     */
    public function getCrawlJob()
    {
        return $this->crawlJob;
    }

    /**
     * @param Job $job
     */
    public function setParentJob(Job $job)
    {
        $this->parentJob = $job;
    }

    /**
     * @return Job
     */
    public function getParentJob()
    {
        return $this->parentJob;
    }
}
