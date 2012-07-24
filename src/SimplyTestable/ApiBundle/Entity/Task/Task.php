<?php
namespace SimplyTestable\ApiBundle\Entity\Task;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="Task"
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
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Job\Job", inversedBy="tasks")
     * @ORM\JoinColumn(name="job_id", referencedColumnName="id", nullable=false)
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
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     */
    protected $state;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\Worker 
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Worker", inversedBy="tasks")
     */
    protected $worker;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\Task\Type
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Task\Type")
     * @ORM\JoinColumn(name="tasktype_id", referencedColumnName="id", nullable=false)
     */
    protected $type;
}