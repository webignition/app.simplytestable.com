<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Guzzle\Http\Message\Request as GuzzleRequest;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService as JobConfigurationService;
use Cron\Validator\CrontabValidator;
use Cron\Exception\InvalidPatternException;
use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobException;
use SimplyTestable\ApiBundle\Services\ScheduledJob\CronModifier\ValidationService as CronModifierValidationService;

class CreateController extends ScheduledJobController {

    /**
     * @var Request
     */
    private $request;

    public function __construct() {
        $this->setInputDefinitions(array(
            'createAction' => new InputDefinition(array(
                    new InputArgument('job-configuration', InputArgument::REQUIRED, 'label of existing job configuration to use'),
                    new InputArgument('schedule', InputArgument::REQUIRED, 'cron-formatted schedule'),
                    new InputArgument('is-recurring', InputArgument::OPTIONAL, 'Is this to recur?')
                ))
        ));

        $this->setRequestTypes(array(
            'createAction' => GuzzleRequest::POST
        ));
    }

    public function createAction(Request $request) {
        $this->request = $request;

        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($this->getApplicationStateService()->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($this->getUserService()->isSpecialUser($this->getUser())) {
            return $this->sendFailureResponse([
                'X-ScheduledJobCreate-Error' => json_encode([
                    'code' => 99,
                    'message' => 'Special users cannot create scheduled jobs'
                ])
            ]);
        }

        $this->getScheduledJobService()->setUser($this->getUser());
        $this->getJobConfigurationService()->setUser($this->getUser());

        $jobConfiguration = $this->getJobConfigurationService()->get($this->request->request->get('job-configuration'));

        if (is_null($jobConfiguration)) {
            return $this->sendFailureResponse([
                'X-ScheduledJobCreate-Error' => json_encode([
                    'code' => 98,
                    'message' => 'Unknown job configuration'
                ])
            ]);
        }

        try {
            $scheduleValidator = new CrontabValidator();
            $scheduleValidator->validate($this->request->request->get('schedule'));
        } catch (InvalidPatternException $invalidPatternException) {
            return $this->sendFailureResponse([
                'X-ScheduledJobCreate-Error' => json_encode([
                    'code' => 97,
                    'message' => 'Malformed schedule'
                ])
            ]);
        }


        if (!$this->getCronModifierValidationService()->isValid($this->request->request->get('schedule-modifier'))) {
            return $this->sendFailureResponse([
                'X-ScheduledJobCreate-Error' => json_encode([
                    'code' => 96,
                    'message' => 'Malformed schedule modifier'
                ])
            ]);
        }

        try {
            $scheduledJob = $this->getScheduledJobService()->create(
                $jobConfiguration,
                $this->request->request->get('schedule'),
                $this->request->request->get('schedule-modifier'),
                $this->getRequestIsRecurring()
            );

            return $this->redirect($this->generateUrl(
                'scheduledjob_get_get',
                ['id' => $scheduledJob->getId()]
            ));
        } catch (ScheduledJobException $scheduledJobException) {
            return $this->sendFailureResponse([
                'X-ScheduledJobCreate-Error' => json_encode([
                    'code' => $scheduledJobException->getCode(),
                    'message' => $scheduledJobException->getMessage()
                ])
            ]);
        }
    }


    /**
     * @return boolean
     */
    private function getRequestIsRecurring() {
        return filter_var($this->request->request->get('is-recurring'), FILTER_VALIDATE_BOOLEAN);
    }


    /**
     * @return JobConfigurationService
     */
    protected function getJobConfigurationService() {
        return $this->get('simplytestable.services.job.configurationservice');
    }


    /**
     * @return CronModifierValidationService
     */
    protected function getCronModifierValidationService() {
        return $this->get('simplytestable.services.scheduledjob.cronmodifier.validationservice');
    }

}
