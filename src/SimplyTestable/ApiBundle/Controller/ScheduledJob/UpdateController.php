<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Cron\Validator\CrontabValidator;
use Cron\Exception\InvalidPatternException;
use SimplyTestable\ApiBundle\Exception\Controller\ScheduledJob\Update\Exception
    as ScheduledJobControllerUpdateException;
use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobException;
use Symfony\Component\HttpFoundation\Response;

class UpdateController extends ScheduledJobController
{
    /**
     * @param Request $request
     * @param int $id
     * @return RedirectResponse|Response
     */
    public function updateAction(Request $request, $id)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');
        $cronModifierValidationService = $this->get(
            'simplytestable.services.scheduledjob.cronmodifier.validationservice'
        );

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($applicationStateService->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $scheduledJobService->setUser($this->getUser());
        $scheduledJob = $scheduledJobService->get($id);

        if (empty($scheduledJob)) {
            return $this->sendNotFoundResponse();
        }

        $requestData = $request->request;

        $jobConfiguration = null;
        $requestJobConfigurationLabel = trim($requestData->get('job-configuration'));

        if (!empty($requestJobConfigurationLabel)) {
            $jobConfigurationService->setUser($this->getUser());

            $jobConfiguration = $jobConfigurationService->get($requestJobConfigurationLabel);
            if (empty($jobConfiguration)) {
                return $this->sendFailureResponse([
                    'X-ScheduledJobUpdate-Error' => json_encode([
                        'code' => 100 - ScheduledJobControllerUpdateException::CODE_UNKNOWN_JOB_CONFIGURATION,
                        'message' => 'Unknown job configuration'
                    ])
                ]);
            }
        }

        $schedule = null;
        $requestSchedule = trim($requestData->get('schedule'));
        if (!empty($requestSchedule)) {
            try {
                $scheduleValidator = new CrontabValidator();
                $scheduleValidator->validate($requestSchedule);
                $schedule = $requestSchedule;
            } catch (InvalidPatternException $invalidPatternException) {
                return $this->sendFailureResponse([
                    'X-ScheduledJobUpdate-Error' => json_encode([
                        'code' => 100 - ScheduledJobControllerUpdateException::CODE_INVALID_SCHEDULE,
                        'message' => 'Invalid schedule'
                    ])
                ]);
            }
        }

        $isRecurring = null;
        if ($requestData->has('is-recurring')) {
            $isRecurring = $requestData->getBoolean('is-recurring');
        }

        $cronModifier = null;

        if ($requestData->has('schedule-modifier')) {
            $cronModifier = $requestData->get('schedule-modifier');
            if (!$cronModifierValidationService->isValid($cronModifier)) {
                return $this->sendFailureResponse([
                    'X-ScheduledJobUpdate-Error' => json_encode([
                        'code' => 100 - ScheduledJobControllerUpdateException::CODE_INVALID_SCHEDULE_MODIFIER,
                        'message' => 'Invalid schedule modifier'
                    ])
                ]);
            }
        }

        try {
            $this->getScheduledJobService()->update(
                $scheduledJob,
                $jobConfiguration,
                $schedule,
                $cronModifier,
                $isRecurring
            );
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
}
