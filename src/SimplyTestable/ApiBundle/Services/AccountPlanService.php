<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;

class AccountPlanService
{
    const PLAN_BASIC = 'basic';

    /**
     * @var EntityRepository
     */
    private $accountPlanRepository;

    /**
     * JobTypeService constructor.
     * @param EntityRepository $accountPlanRepository
     */
    public function __construct(EntityRepository $accountPlanRepository)
    {
        $this->accountPlanRepository = $accountPlanRepository;
    }

    /**
     * @return Plan
     */
    public function getBasicPlan()
    {
        return $this->get(self::PLAN_BASIC);
    }

    /**
     * @param $name
     *
     * @return Plan
     */
    public function get($name)
    {
        /* @var Plan $accountPlan */
        $accountPlan = $this->accountPlanRepository->findOneBy([
            'name' => $name,
        ]);

        return $accountPlan;
    }
}
