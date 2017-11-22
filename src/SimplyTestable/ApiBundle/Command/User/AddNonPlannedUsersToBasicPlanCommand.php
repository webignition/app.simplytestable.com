<?php
namespace SimplyTestable\ApiBundle\Command\User;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Command\BaseCommand;

class AddNonPlannedUsersToBasicPlanCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AccountPlanService
     */
    private $accountPlanService;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param UserAccountPlanService $userAccountPlanService
     * @param EntityManagerInterface $entityManager
     * @param AccountPlanService $accountPlanService
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        UserAccountPlanService $userAccountPlanService,
        EntityManagerInterface $entityManager,
        AccountPlanService $accountPlanService,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->entityManager = $entityManager;
        $this->accountPlanService = $accountPlanService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:user:add-non-planned-users-to-basic-plan')
            ->setDescription('Assign all users without a plan the basic plan')
            ->addOption('dry-run')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
            $output->writeln('In maintenance-read-only mode, I can\'t do that right now');

            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $isDryRun = $input->getOption('dry-run');

        if ($isDryRun) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }

        $output->writeln('Finding users that have no plan ...');

        /* @var User[] $users */
        $users = $this->userAccountPlanService->findUsersWithNoPlan();

        if (empty($users)) {
            $output->writeln('No users found that have no plan. Done.');

            return self::RETURN_CODE_OK;
        }

        $output->writeln('['.count($users).'] users found with no plan');

        $basicPlan = $this->accountPlanService->getBasicPlan();

        foreach ($users as $user) {
            $output->writeln('Setting basic plan for ' . $user->getUsername());

            if (!$isDryRun) {
                $this->userAccountPlanService->subscribe($user, $basicPlan);
            }
        }

        $output->writeln('');

        return self::RETURN_CODE_OK;
    }
}
