<?php
namespace SimplyTestable\ApiBundle\Command\Worker;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;
use SimplyTestable\ApiBundle\Command\BaseCommand;

class SetTokenFromActivationRequestCommand extends BaseCommand {

    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:worker:settokenfromactivationrequest')
            ->setDescription('Set all unset worker tokens from related activation requests')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {         
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $workers = $this->getWorkerService()->getEntityRepository()->findAll();

        foreach ($workers as $worker) {
            /* @var $worker Worker */
            if (is_null($worker->getToken()) && $this->getWorkerActivationRequestService()->has($worker)) {
                $worker->setToken($this->getWorkerActivationRequestService()->fetch($worker)->getToken());
                $this->getWorkerService()->persistAndFlush($worker);
            }
        }

        return self::RETURN_CODE_OK;
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