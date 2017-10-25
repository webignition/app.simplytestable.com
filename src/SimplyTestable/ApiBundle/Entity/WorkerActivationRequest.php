<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class WorkerActivationRequest
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
     * @var Worker
     *
     * @ORM\OneToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Worker")
     */
    protected $worker;

    /**
     * @var State
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\State")
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id", nullable=false)
     */
    protected $state;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $token;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Worker $worker
     */
    public function setWorker(Worker $worker = null)
    {
        $this->worker = $worker;
    }

    /**
     * @return Worker
     */
    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * @param State $state
     */
    public function setState(State $state)
    {
        $this->state = $state;
    }

    /**
     * @return State
     */
    public function getState()
    {
        return $this->state;
    }

    public function setNextState()
    {
        if (!is_null($this->getState()->getNextState())) {
            $this->state = $this->getState()->getNextState();
        }
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
}
