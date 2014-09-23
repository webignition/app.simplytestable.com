<?php
namespace SimplyTestable\ApiBundle\Command\Maintenance;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\ApiBundle\Command\BaseCommand;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;

class ModifyInitialStartTrialPeriodCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_MISSING_REQUIRED_OPTION = 2;   
    
    /**
     *
     * @var InputInterface
     */
    private $input;
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:modify-initial-start-trial-period')
            ->setDescription('Modify the intial start trial period for all users on the basic plan')
            ->addOption('dry-run', null, InputOption::VALUE_OPTIONAL, 'Run through the process without writing any data')
            ->addOption('current', null, InputOption::VALUE_REQUIRED, 'Current trial period to modify from')
            ->addOption('new', null, InputOption::VALUE_REQUIRED, 'New trial period to modify to')
            ->setHelp(<<<EOF
Modify the intial start trial period for all users on the basic plan.
EOF
        );     
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        $this->input = $input;
        
        if ($this->isDryRun()) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }
        
        $current = $this->getCurrent();
        
        if (is_null($current)) {
            $output->writeln('<info>Current trial period: NULL</info>');
            $output->writeln('<error>Current trial period not specified. Use --current=<int></error>');
            return self::RETURN_CODE_MISSING_REQUIRED_OPTION;
        } else {
            $output->writeln('<info>Current trial period: '.$current.'</info>');
        }
        
        $new = $this->getNew();        
        
        if (is_null($current)) {
            $output->writeln('<info>New trial period: NULL</info>');
            $output->writeln('<error>New trial period not specified. Use --new=<int></error>');
            return self::RETURN_CODE_MISSING_REQUIRED_OPTION;
        } else {
            $output->writeln('<info>New trial period: '.$new.'</info>');
        }
        
        $basicPlan = $this->getAccountPlanService()->find('basic');
        
        $output->write('<info>Finding users on basic plan:</info>'); 
        
        $userAccountPlans = $this->getUserAccountPlanService()->findAllByPlan($basicPlan);
        
        $output->writeln(' ' . count($userAccountPlans));
        
        foreach ($userAccountPlans as $userAccountPlan) {
            /* @var $userAccountPlan UserAccountPlan */
            if (is_null($userAccountPlan->getIsActive())) {
                $userAccountPlan->setIsActive(true);
            }
            
            if (is_null($userAccountPlan->getStartTrialPeriod()) || $userAccountPlan->getStartTrialPeriod() === $current) {
                $output->write('Updating for ' . $userAccountPlan->getUser()->getUsername() . ' ... ');
                $output->writeln('going from ' .(is_null($userAccountPlan->getStartTrialPeriod()) ? 'NULL' : $current). ' to ' . $new);
                
                $userAccountPlan->setStartTrialPeriod($new);
            }
            
            if (!$this->isDryRun()) {
                $this->getUserAccountPlanService()->getManager()->persist($userAccountPlan);
                $this->getUserAccountPlanService()->getManager()->flush($userAccountPlan);
            }
        }
        
        
        return self::RETURN_CODE_OK;
    }

    private function getCurrent() {
        return $this->getNonZeroIntegerOption('current');
    }    
    
    
    private function getNew() {
        return $this->getNonZeroIntegerOption('new');
    }
    
    
    
    /**
     * 
     * @param string $name
     * @return int
     */
    private function getNonZeroIntegerOption($name) {
        $value = $this->input->getOption($name);
        if (!ctype_digit($value)) {
            return null;
        }
        
        if ($value < 0) {
            return null;
        }
        
        return (int)$value;        
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function isDryRun() {        
        return $this->input->getOption('dry-run') == 'true';
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\AccountPlanService
     */    
    private function getAccountPlanService() {
        return $this->getContainer()->get('simplytestable.services.accountplanservice');
    }     
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserAccountPlanService
     */    
    private function getUserAccountPlanService() {
        return $this->getContainer()->get('simplytestable.services.useraccountplanservice');
    }
}