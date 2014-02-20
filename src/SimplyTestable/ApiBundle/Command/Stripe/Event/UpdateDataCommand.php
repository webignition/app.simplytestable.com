<?php
namespace SimplyTestable\ApiBundle\Command\Stripe\Event;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDataCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:stripe:event:updatedata')
            ->setDescription('Retrieve all stripe event data from stripe and refresh local cache')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }
        
        $events = $this->getStripeEventService()->getEntityRepository()->findAll();
        
        foreach ($events as $event) {
            $output->write('Retrieving ' . $event->getStripeId().' ... ');
            
            $response = json_decode(shell_exec('curl https://api.stripe.com/v1/events/'. $event->getStripeId() .' -u ' . $this->getContainer()->getParameter('stripe_key').': 2>/dev/null'));
            if (isset($response->error)) {
                $output->writeln('<error>'.$response->error->message.'</error>');
                continue;
            }
            
            if (is_null($response)) {
                $output->writeln('<error>NULL</error>');
                continue;                
            }
            
            $output->write('<info>ok</info>');
            
            $output->write(' ... updating local copy  ... ');
            
            $event->setStripeEventData(json_encode($response));
            $this->getStripeEventService()->persistAndFlush($event);
            
            $output->writeln('<info>done</info>');
        }
        
        return self::RETURN_CODE_OK;
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\StripeEventService
     */    
    private function getStripeEventService() {
        return $this->getContainer()->get('simplytestable.services.stripeeventservice');
    }
}