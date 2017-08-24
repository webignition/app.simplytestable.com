<?php
namespace SimplyTestable\ApiBundle\Command\Maintenance;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;

class ModifyInitialStartTrialPeriodCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_MISSING_REQUIRED_OPTION = 2;

    /**
     * @var AccountPlanService
     */
    private $accountPlanService;

    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param AccountPlanService $accountPlanService
     * @param UserAccountPlanService $userAccountPlanService
     * @param EntityManager $entityManager
     * @param null $name
     */
    public function __construct(
        AccountPlanService $accountPlanService,
        UserAccountPlanService $userAccountPlanService,
        EntityManager $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->accountPlanService = $accountPlanService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->entityManager = $entityManager;
    }

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:modify-initial-start-trial-period')
            ->setDescription('Modify the intial start trial period for all users on the basic plan')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_OPTIONAL,
                'Run through the process without writing any data'
            )
            ->addOption(
                'current',
                null,
                InputOption::VALUE_REQUIRED,
                'Current trial period to modify from'
            )
            ->addOption(
                'new',
                null,
                InputOption::VALUE_REQUIRED,
                'New trial period to modify to'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;

        $isDryRun = filter_var($input->getOption('dry-run'), FILTER_VALIDATE_BOOLEAN);

        if ($isDryRun) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }

        $current = $this->getNonZeroIntegerOption($input, 'current');

        if (is_null($current)) {
            $output->writeln('<info>Current trial period: NULL</info>');
            $output->writeln('<error>Current trial period not specified. Use --current=<int></error>');

            return self::RETURN_CODE_MISSING_REQUIRED_OPTION;
        } else {
            $output->writeln('<info>Current trial period: '.$current.'</info>');
        }

        $new = $this->getNonZeroIntegerOption($input, 'new');

        if (is_null($new)) {
            $output->writeln('<info>New trial period: NULL</info>');
            $output->writeln('<error>New trial period not specified. Use --new=<int></error>');

            return self::RETURN_CODE_MISSING_REQUIRED_OPTION;
        } else {
            $output->writeln('<info>New trial period: '.$new.'</info>');
        }

        $basicPlan = $this->accountPlanService->find('basic');

        $output->write('<info>Finding users on basic plan:</info>');

        $userAccountPlans = $this->userAccountPlanService->findAllByPlan($basicPlan);

        $output->writeln(' ' . count($userAccountPlans));

        foreach ($userAccountPlans as $userAccountPlan) {
            /* @var $userAccountPlan UserAccountPlan */
            $currentUserStartTrialPeriod = $userAccountPlan->getStartTrialPeriod();

            $hasNoStartTrialPeriod = empty($currentUserStartTrialPeriod);
            $hasMatchingStartTrialPeriod = $currentUserStartTrialPeriod === $current;
            $shouldUpdateStartTrialPeriod = $hasNoStartTrialPeriod || $hasMatchingStartTrialPeriod;

            if ($shouldUpdateStartTrialPeriod) {
                $output->write('Updating for ' . $userAccountPlan->getUser()->getUsername() . ' ... ');
                $output->writeln('going from ' . ($hasNoStartTrialPeriod ? 'NULL' : $current). ' to ' . $new);
            }

            if (!$isDryRun && $shouldUpdateStartTrialPeriod) {
                if (is_null($userAccountPlan->getIsActive())) {
                    $userAccountPlan->setIsActive(true);
                }

                $userAccountPlan->setStartTrialPeriod($new);
                $this->entityManager->persist($userAccountPlan);
                $this->entityManager->flush($userAccountPlan);
            }
        }

        return self::RETURN_CODE_OK;
    }

    /**
     * @param InputInterface $input
     * @param string $name
     *
     * @return int
     */
    private function getNonZeroIntegerOption(InputInterface $input, $name)
    {
        $value = $input->getOption($name);

        if (!(ctype_digit($value) || is_int($value))) {
            return null;
        }

        if ($value < 0) {
            return null;
        }

        return (int)$value;
    }
}
