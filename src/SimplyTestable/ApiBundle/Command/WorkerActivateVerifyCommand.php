<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;

class WorkerActivateVerifyCommand extends BaseCommand
{
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:worker:activate:verify')
            ->setDescription('Verify the activation request of a worker')
            ->addArgument('id', InputArgument::REQUIRED, 'id of worker to verify')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {         
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }
        
        $id = (int)$input->getArgument('id');
        $worker = $this->getWorkerService()->getById($id);
        $activationRequest = $this->getWorkerActivationRequestService()->fetch($worker);
        
        return $this->getWorkerActivationRequestService()->verify($activationRequest);
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\WorkerService
     */
    private function getWorkerService() {
        return $this->getContainer()->get('simplytestable.services.workerservice');
    }  
    

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\WorkerActivationRequestService
     */    
    private function getWorkerActivationRequestService() {
        return $this->getContainer()->get('simplytestable.services.workeractivationrequestservice');
    }
}