<?php
namespace SimplyTestable\ApiBundle\Entity;;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="UserAccountPlan"
 * )
 * @ORM\Entity(repositoryClass="SimplyTestable\ApiBundle\Repository\UserAccountPlanRepository")
 */
class UserAccountPlan
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
     * @var \SimplyTestable\ApiBundle\Entity\User
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */    
    private $user;
    
    

    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Account\Plan\Plan")
     * @ORM\JoinColumn(name="accountplan_id", referencedColumnName="id", nullable=false)
     */    
    private $plan;

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
     * Set user
     *
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return UserAccountPlan
     */
    public function setUser(\SimplyTestable\ApiBundle\Entity\User $user)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \SimplyTestable\ApiBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set plan
     *
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $accountPlan
     * @return UserAccountPlan
     */
    public function setPlan(\SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $plan)
    {
        $this->plan = $plan;
    
        return $this;
    }

    /**
     * Get plan
     *
     * @return \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan 
     */
    public function getPlan()
    {
        return $this->plan;
    }
}