<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\ApiBundle\Entity\State;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="CrawlJobContainer"
 * )
 * @ORM\Entity(repositoryClass="SimplyTestable\ApiBundle\Repository\CrawlJobContainerRepository")
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class CrawlJobContainer
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
    protected $id;    
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     * 
     * @ORM\OneToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Job")
     * @ORM\JoinColumn(name="crawl_job_id", referencedColumnName="id", nullable=false)
     * 
     */
    protected $crawlJob;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     * 
     * @ORM\OneToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Job")
     * @ORM\JoinColumn(name="parent_job_id", referencedColumnName="id", nullable=false)
     * 
     */
    protected $parentJob;
    
    
    public function __construct()
    {
        $this->tasks = new \Doctrine\Common\Collections\ArrayCollection();
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
     *
     * @param SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return CrawlJobContainer
     */
    public function setCrawlJob(\SimplyTestable\ApiBundle\Entity\Job\Job $job)
    {
        $this->crawlJob = $job;
    
        return $this;
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    public function getCrawlJob()
    {
        return $this->crawlJob;
    }    
    

    /**
     *
     * @param SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return CrawlJobContainer
     */
    public function setParentJob(\SimplyTestable\ApiBundle\Entity\Job\Job $job)
    {
        $this->parentJob = $job;
    
        return $this;
    }

    /**
     *
     * @return SimplyTestable\ApiBundle\Entity\Job\Job
     */
    public function getParentJob()
    {
        return $this->parentJob;
    }
}