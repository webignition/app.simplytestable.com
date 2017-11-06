<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;

class AccountPlanService extends EntityService
{
    /**
     *
     * @return string
     */
    protected function getEntityName()
    {
        return Plan::class;
    }

    /**
     * @param string $name
     *
     * @return Plan
     */
    public function find($name)
    {
        return $this->getEntityRepository()->findOneByName($name);
    }
}
