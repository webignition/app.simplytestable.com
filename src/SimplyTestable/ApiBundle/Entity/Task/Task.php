<?php
namespace SimplyTestable\ApiBundle\Entity\Task;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="Task",
 *     indexes={
 *        @ORM\Index(name="job_index", columns={"job_id"}),
 *        @ORM\Index(name="url_index", columns={"url"}),
 *        @ORM\Index(name="state_index", columns={"state_id"}),
 *        @ORM\Index(name="worker_index", columns={"worker_id"})}
 * )
 * 
 */
class Task
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
     * @ORM\Column(type="integer", nullable=false, name="job_id")
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Job", inversedBy="tasks")
     */
    protected $job;
    
    
    /**
     *
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    protected $url;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\State
     * 
     * @ORM\Column(type="integer", nullable=false, name="state_id")
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\State")
     */
    protected $state;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\Worker 
     * 
     * @ORM\Column(name="worker_id", type="integer", nullable=true)
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Worker", inversedBy="tasks")
     */
    protected $worker;
}