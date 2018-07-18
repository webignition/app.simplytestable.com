<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\Account\Plan\Plan as AccountPlan;

/**
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="UserAccountPlan"
 * )
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserAccountPlanRepository")
 */
class UserAccountPlan implements \JsonSerializable
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
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @var AccountPlan
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Account\Plan\Plan")
     * @ORM\JoinColumn(name="accountplan_id", referencedColumnName="id", nullable=false)
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
     */
    private $stripeCustomer = null;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
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

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $userAccountPlanData = [
            'plan' => $this->plan->jsonSerialize(),
            'start_trial_period' => $this->startTrialPeriod,
        ];

        if (!empty($this->stripeCustomer)) {
            $userAccountPlanData['stripe_customer'] = $this->stripeCustomer;
        }

        return $userAccountPlanData;
    }
}
