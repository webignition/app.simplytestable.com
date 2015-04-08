<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService as JobConfigurationService;
use Cron\Validator\CrontabValidator;
use Cron\Exception\InvalidPatternException;
use SimplyTestable\ApiBundle\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Exception\Controller\ScheduledJob\Update\Exception as ScheduledJobControllerUpdateException;
use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobException;

class UpdateController extends ScheduledJobController {

    /**
     * @var Request
     */
    private $request;

    public function updateAction(Request $request, $id) {
        $this->request = $request;

        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($this->getApplicationStateService()->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $this->getScheduledJobService()->setUser($this->getUser());

        $scheduledJob = $this->getScheduledJobService()->get($id);

        if (is_null($scheduledJob)) {
            return $this->sendNotFoundResponse();
        }

        try {
            $jobConfiguration = $this->getRequestJobConfiguration();
            $schedule = $this->getRequestSchedule();
            $isRecurring = $this->request->request->has('is-recurring') ? filter_var($this->request->request->get('is-recurring'), FILTER_VALIDATE_BOOLEAN)  : null;
            $cronModifier = $this->request->request->get('schedule-modifier');

            $this->getScheduledJobService()->update($scheduledJob, $jobConfiguration, $schedule, $cronModifier, $isRecurring);
        } catch (ScheduledJobControllerUpdateException $exception) {
            return $this->sendFailureResponse([
                'X-ScheduledJobUpdate-Error' => json_encode([
                    'code' => 100 - $exception->getCode(),
                    'message' => $exception->getMessage()
                ])
            ]);
        } catch (ScheduledJobException $scheduledJobException) {
            return $this->sendFailureResponse([
                'X-ScheduledJobUpdate-Error' => json_encode([
                    'code' => $scheduledJobException->getCode(),
                    'message' => $scheduledJobException->getMessage()
                ])
            ]);
        }

        return $this->redirect($this->generateUrl(
            'scheduledjob_get_get',
            ['id' => $scheduledJob->getId()]
        ));
    }


    /**
     * @return null|JobConfiguration
     * @throws JobConfigurationServiceException
     * @throws ScheduledJobControllerUpdateException
     */
    private function getRequestJobConfiguration() {
        if ($this->request->request->has('job-configuration')) {
            $jobConfiguration = $this->getJobConfigurationService()->get($this->request->request->get('job-configuration'));

            if (is_null($jobConfiguration)) {
                throw new ScheduledJobControllerUpdateException(
                    'Unknown job configuration',
                    ScheduledJobControllerUpdateException::CODE_UNKNOWN_JOB_CONFIGURATION
                );
            }

            return $jobConfiguration;
        }

        return null;
    }


    /**
     * @return string|null
     * @throws ScheduledJobControllerUpdateException
     */
    private function getRequestSchedule() {
        if ($this->request->request->has('schedule')) {
            try {
                $scheduleValidator = new CrontabValidator();
                $scheduleValidator->validate($this->request->request->get('schedule'));
                return $this->request->request->get('schedule');
            } catch (InvalidPatternException $invalidPatternException) {
                throw new ScheduledJobControllerUpdateException(
                    'Invalid schedule',
                    ScheduledJobControllerUpdateException::CODE_INVALID_SCHEDULE
                );
            }
        }

        return null;
    }


    /**
     * @return JobConfigurationService
     */
    protected function getJobConfigurationService() {
        return $this->get('simplytestable.services.job.configurationservice');
    }

}
