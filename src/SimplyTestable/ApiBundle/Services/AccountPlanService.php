<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
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
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->accountPlanRepository = $entityManager->getRepository(Plan::class);
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
