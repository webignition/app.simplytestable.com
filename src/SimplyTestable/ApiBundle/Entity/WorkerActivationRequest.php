<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;

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
     *
     * @var SimplyTestable\ApiBundle\Entity\State
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     * 
     * @SerializerAnnotation\Accessor(getter="getPublicSerializedState")
     */
    protected $state;
    
    
    /**
     *
     * @var string 
     * 
     * @ORM\Column(type="string", nullable=false)
     */  
    protected $token;
    
    
    /**
     *
     * @return string
     */
    public function getPublicSerializedState() {
        return str_replace('worker-activation-request-', '', (string)$this->getState());
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
    
    
    /**
     * Set state
     *
     * @param SimplyTestable\ApiBundle\Entity\State $state
     * @return \SimplyTestable\ApiBundle\Entity\WorkerActivationRequest
     */
    public function setState(State $state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Get state
     *
     * @return use SimplyTestable\ApiBundle\Entity\State 
     */
    public function getState()
    {
        return $this->state;
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\WorkerActivationRequest 
     */
    public function setNextState() {
        if (!is_null($this->getState()->getNextState())) {
            $this->state = $this->getState()->getNextState();
        }        
        
        return $this;
    } 
    
    
    /**
     *
     * @param type $token
     * @return \SimplyTestable\ApiBundle\Entity\WorkerActivationRequest 
     */
    public function setToken($token) {
        $this->token = $token;
        return $this;
    }
    
    
    /**
     *
     * @return string
     */
    public function getToken() {
        return $this->token;
    }
}