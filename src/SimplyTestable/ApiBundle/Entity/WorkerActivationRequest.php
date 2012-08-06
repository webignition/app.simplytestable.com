<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class WorkerActivationRequest
{
    /**
     * 
     * @var type integer
     * 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Entity\Worker
     * 
     * @ORM\OneToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Worker")
     * 
     */  
    protected $worker;

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
     * Set worker
     *
     * @param SimplyTestable\ApiBundle\Entity\Worker $worker
     * @return WorkerActivationRequest
     */
    public function setWorker(\SimplyTestable\ApiBundle\Entity\Worker $worker = null)
    {
        $this->worker = $worker;
    
        return $this;
    }

    /**
     * Get worker
     *
     * @return SimplyTestable\ApiBundle\Entity\Worker 
     */
    public function getWorker()
    {
        return $this->worker;
    }
}