<?php

namespace SimplyTestable\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\SerializerBundle\Annotation as SerializerAnnotation;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan as AccountPlan;

/**
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="UserAccountPlan"
 * )
 * @ORM\Entity(repositoryClass="SimplyTestable\ApiBundle\Repository\UserAccountPlanRepository")
 * @SerializerAnnotation\ExclusionPolicy("all")
 */
class UserAccountPlan
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @var AccountPlan
     *
     * @ORM\ManyToOne(targetEntity="SimplyTestable\ApiBundle\Entity\Account\Plan\Plan")
     * @ORM\JoinColumn(name="accountplan_id", referencedColumnName="id", nullable=false)
     * @SerializerAnnotation\Expose
     */
    private $plan;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isActive = true;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @SerializerAnnotation\Expose
     */
    private $stripeCustomer = null;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     * @SerializerAnnotation\Expose
     */
    private $startTrialPeriod = 30;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @param AccountPlan $plan
     */
    public function setPlan(AccountPlan $plan)
    {
        $this->plan = $plan;
    }

    /**
     * @return AccountPlan
     */
    public function getPlan()
    {
        return $this->plan;
    }

    /**
     * @param bool $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }

    /**
     * @return bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @param string $stripeCustomer
     */
    public function setStripeCustomer($stripeCustomer)
    {
        $this->stripeCustomer = $stripeCustomer;
    }

    /**
     * @return string
     */
    public function getStripeCustomer()
    {
        return $this->stripeCustomer;
    }

    /**
     * @param integer $startTrialPeriod
     */
    public function setStartTrialPeriod($startTrialPeriod)
    {
        $this->startTrialPeriod = $startTrialPeriod;
    }

    /**
     * @return int
     */
    public function getStartTrialPeriod()
    {
        return $this->startTrialPeriod;
    }
}
