<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\Account\Plan\Plan as AccountPlan;

/**
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="UserPostActivationProperties"
 * )
 */
class UserPostActivationProperties
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
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var AccountPlan
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Account\Plan\Plan")
     * @ORM\JoinColumn(name="accountplan_id", referencedColumnName="id", nullable=false)
     */
    protected $accountPlan;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $coupon;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $coupon
     */
    public function setCoupon($coupon)
    {
        $this->coupon = $coupon;
    }

    /**
     * @return string
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param AccountPlan $accountPlan
     */
    public function setAccountPlan(AccountPlan $accountPlan)
    {
        $this->accountPlan = $accountPlan;
    }

    /**
     * @return AccountPlan
     */
    public function getAccountPlan()
    {
        return $this->accountPlan;
    }
}
