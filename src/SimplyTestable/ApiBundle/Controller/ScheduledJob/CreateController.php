<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Cron\Validator\CrontabValidator;
use Cron\Exception\InvalidPatternException;
use SimplyTestable\ApiBundle\Exception\Services\ScheduledJob\Exception as ScheduledJobException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateController extends ScheduledJobController
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $userService = $this->container->get('simplytestable.services.userservice');
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');
        $cronModifierValidationService = $this->container->get(
            'simplytestable.services.scheduledjob.cronmodifier.validationservice'
        );

        $this->request = $request;

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($applicationStateService->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $requestData = $request->request;

        $requestJobConfigurationLabel = rawurldecode(trim($requestData->get('job-configuration')));

        if (empty($requestJobConfigurationLabel)) {
            throw new BadRequestHttpException('"job-configuration" missing');
        }

        $requestSchedule = rawurldecode(trim($requestData->get('schedule')));

        if (empty($requestSchedule)) {
            throw new BadRequestHttpException('"schedule" missing');
        }

        $user = $this->getUser();

        if ($userService->isSpecialUser($user)) {
            return $this->sendFailureResponse([
                'X-ScheduledJobCreate-Error' => json_encode([
                    'code' => 99,
                    'message' => 'Special users cannot create scheduled jobs'
                ])
            ]);
        }

        $scheduledJobService->setUser($this->getUser());
        $jobConfigurationService->setUser($this->getUser());

        $jobConfiguration = $jobConfigurationService->get($requestJobConfigurationLabel);

        if (empty($jobConfiguration)) {
            return $this->sendFailureResponse([
                'X-ScheduledJobCreate-Error' => json_encode([
                    'code' => 98,
                    'message' => 'Unknown job configuration "' . $requestJobConfigurationLabel . '"'
                ])
            ]);
        }

        try {
            $scheduleValidator = new CrontabValidator();
            $scheduleValidator->validate($requestSchedule);
        } catch (InvalidPatternException $invalidPatternException) {
            return $this->sendFailureResponse([
                'X-ScheduledJobCreate-Error' => json_encode([
                    'code' => 97,
                    'message' => 'Malformed schedule "' . $requestSchedule . '"'
                ])
            ]);
        }

        $requestScheduleModifier = trim($requestData->get('schedule-modifier'));
        if (empty($requestScheduleModifier)) {
            $requestScheduleModifier = null;
        }

        if (!$cronModifierValidationService->isValid($requestScheduleModifier)) {
            return $this->sendFailureResponse([
                'X-ScheduledJobCreate-Error' => json_encode([
                    'code' => 96,
                    'message' => 'Malformed schedule modifier "' . $requestScheduleModifier . '"'
                ])
            ]);
        }

        try {
            $scheduledJob = $scheduledJobService->create(
                $jobConfiguration,
                $this->request->request->get('schedule'),
                $this->request->request->get('schedule-modifier'),
                $requestData->getBoolean('is-recurring')
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
}
