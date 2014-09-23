<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\UserPostActivationProperties;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan as AccountPlan;

class UserPostActivationPropertiesService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\UserPostActivationProperties';
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }


    /**
     * @param User $user
     * @return UserPostActivationProperties
     */
    public function getForUser(User $user) {
        return $this->getEntityRepository()->findOneBy([
            'user' => $user
        ]);
    }


    /**
     * @param User $user
     * @param AccountPlan $accountPlan
     * @param string|null $coupon
     * @return UserPostActivationProperties
     */
    public function create(User $user, AccountPlan $accountPlan, $coupon = null) {
        if ($this->hasForUser($user)) {
            return $this->updateForUser($this->getForUser($user), $accountPlan, $coupon);
        }

        $userPostActivationProperties = new UserPostActivationProperties();
        $userPostActivationProperties->setUser($user);

        return $this->updateForUser($userPostActivationProperties, $accountPlan, $coupon);
    }


    /**
     * @param User $user
     * @return bool
     */
    public function hasForUser(User $user) {
        return !is_null($this->getForUser($user));
    }


    /**
     * @param UserPostActivationProperties $userPostActivationProperties
     * @param AccountPlan $accountPlan
     * @param string|null $coupon
     * @return UserPostActivationProperties
     */
    private function updateForUser(UserPostActivationProperties $userPostActivationProperties, AccountPlan $accountPlan, $coupon = null) {
        $userPostActivationProperties->setAccountPlan($accountPlan);
        $userPostActivationProperties->setCoupon($coupon);

        $this->getManager()->persist($userPostActivationProperties);
        $this->getManager()->flush($userPostActivationProperties);

        return $userPostActivationProperties;
    }
}