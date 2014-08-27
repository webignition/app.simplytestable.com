<?php
namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan as AccountPlan;

use JMS\SerializerBundle\Annotation as SerializerAnnotation;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(
 *     name="UserPostActivationProperties"
 * )
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class UserPostActivationProperties
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
     * @var User
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * 
     */
    protected $user;
    
    
    /**
     *
     * @var AccountPlan
     * 
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Account\Plan\Plan")
     * @ORM\JoinColumn(name="accountplan_id", referencedColumnName="id", nullable=false)
     * 
     */
    protected $accountPlan;
    
    /**
     *
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $coupon;



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
     * Set coupon
     *
     * @param string $coupon
     * @return UserPostActivationProperties
     */
    public function setCoupon($coupon)
    {
        $this->coupon = $coupon;

        return $this;
    }

    /**
     * Get coupon
     *
     * @return string 
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return UserPostActivationProperties
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set accountPlan
     *
     * @param AccountPlan $accountPlan
     * @return UserPostActivationProperties
     */
    public function setAccountPlan(AccountPlan $accountPlan)
    {
        $this->accountPlan = $accountPlan;

        return $this;
    }

    /**
     * Get accountPlan
     *
     * @return AccountPlan
     */
    public function getAccountPlan()
    {
        return $this->accountPlan;
    }
}
