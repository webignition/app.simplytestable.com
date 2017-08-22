<?php
namespace SimplyTestable\ApiBundle\Command\Stripe\Event;

use SimplyTestable\ApiBundle\Command\BaseCommand;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;
    const RETURN_CODE_EVENT_HAS_NO_USER = 3;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:stripe:event:process')
            ->setDescription('Process and respond to received stripe event')
            ->addArgument('stripeId', InputArgument::REQUIRED, 'stripe id of event to process')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $applicationStateService = $this->getContainer()->get('simplytestable.services.applicationstateservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $stripeEventService = $this->getContainer()->get('simplytestable.services.stripeeventservice');

        $eventEntity = $stripeEventService->getByStripeId($input->getArgument('stripeId'));

        if (!$eventEntity->hasUser()) {
            $this->getLogger()->error('Stripe\Event\ProcessCommand: event has no user');
            return self::RETURN_CODE_EVENT_HAS_NO_USER;
        }

        $this->getContainer()->get('event_dispatcher')->dispatch(
            'stripe_process.' . $eventEntity->getType(),
            new DispatchableEvent($eventEntity)
        );

        return self::RETURN_CODE_OK;
    }
}
