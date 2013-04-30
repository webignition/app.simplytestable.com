<?php
namespace SimplyTestable\ApiBundle\Command\User;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\ApiBundle\Command\BaseCommand;

class AddNonPlannedUsersToBasicPlanCommand extends BaseCommand
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;    
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:user:add-non-planned-users-to-basic-plan')
            ->setDescription('Assign all users without a plan the basic plan')
            ->addOption('dry-run')                
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            $output->writeln('In maintenance-read-only mode, I can\'t do that right now');
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }       
        
        if ($this->isDryRun($input)) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }        
        
        $output->writeln('Finding users that have no plan ...');
        
        $users = $this->getUserAccountPlanService()->findUsersWithNoPlan();
        
        if (count($users) === 0) {
            $output->writeln('No users found that have no plan. Done.');
            return true;
        }
        
        $output->writeln('['.count($users).'] users found with no plan');        

        $plan = $this->getAccountPlanService()->find('basic');
        
        foreach ($users as $user) {            
            $output->writeln('Setting basic plan for ' . $user->getUsername());                       
            
            if (!$this->isDryRun($input)) {     
                $this->getUserAccountPlanService()->create($user, $plan);                
            }
        }
        
        $output->writeln('');
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserAccountPlanService
     */
    private function getUserAccountPlanService() {
        return $this->getContainer()->get('simplytestable.services.useraccountplanservice');
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
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return int
     */
    private function isDryRun(InputInterface $input) {
        return $input->getOption('dry-run');
    }       
}