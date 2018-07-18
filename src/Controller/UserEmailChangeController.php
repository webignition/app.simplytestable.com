<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use FOS\UserBundle\Util\CanonicalizerInterface;
use App\Entity\User;
use App\Entity\UserEmailChangeRequest;
use App\Services\UserEmailChangeRequestService;
use App\Services\UserService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserEmailChangeController
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var CanonicalizerInterface
     */
    private $emailCanonicalizer;

    /**
     * @var UserEmailChangeRequestService
     */
    private $userEmailChangeRequestService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param UserService $userService
     * @param CanonicalizerInterface $canonicalizer
     * @param UserEmailChangeRequestService $userEmailChangeRequestService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        UserService $userService,
        CanonicalizerInterface $canonicalizer,
        UserEmailChangeRequestService $userEmailChangeRequestService,
        EntityManagerInterface $entityManager
    ) {
        $this->userService = $userService;
        $this->emailCanonicalizer = $canonicalizer;
        $this->userEmailChangeRequestService = $userEmailChangeRequestService;
        $this->entityManager = $entityManager;
    }

    /**
     * @param UserInterface|User $user
     * @param string $email_canonical
     * @param string $new_email
     * @return Response
     */
    public function createAction(UserInterface $user, $email_canonical, $new_email)
    {
        $userEmailChangeRequestRepository = $this->entityManager->getRepository(UserEmailChangeRequest::class);

        $new_email = $this->emailCanonicalizer->canonicalize($new_email);

        /* @var UserEmailChangeRequest|null $existingRequest */
        $existingUserRequest = $userEmailChangeRequestRepository->findOneBy([
            'user' => $user,
        ]);

        if (!empty($existingUserRequest)) {
            if ($existingUserRequest->getNewEmail() === $new_email) {
                return new Response();
            }

            throw new ConflictHttpException();
        }

        $validator = new EmailValidator();
        $isEmailValid = $validator->isValid($new_email, new RFCValidation());

        if (!$isEmailValid) {
            throw new BadRequestHttpException();
        }

        if ($this->userService->exists($new_email)) {
            throw new ConflictHttpException();
        }

        $existingNewEmailRequest = $userEmailChangeRequestRepository->findOneBy([
            'newEmail' => $new_email,
        ]);

        if (!empty($existingNewEmailRequest)) {
            throw new ConflictHttpException();
        }

        $this->userEmailChangeRequestService->create($user, $new_email);

        return new Response();
    }

    /**
     * @param string $email_canonical
     *
     * @return JsonResponse|Response
     */
    public function getAction($email_canonical)
    {
        $user = $this->userService->findUserByEmail($email_canonical);
        if (empty($user)) {
            throw new NotFoundHttpException();
        }

        $userEmailChangeRequestRepository = $this->entityManager->getRepository(UserEmailChangeRequest::class);
        $emailChangeRequest = $userEmailChangeRequestRepository->findOneBy([
            'user' => $user,
        ]);

        if (empty($emailChangeRequest)) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse($emailChangeRequest);
    }

    /**
     * @param UserInterface|User $user
     * @param string $email_canonical
     *
     * @return Response
     */
    public function cancelAction(UserInterface $user, $email_canonical)
    {
        $this->userEmailChangeRequestService->removeForUser($user);

        return new Response();
    }

    /**
     * @param UserInterface|User $user
     * @param string $email_canonical
     * @param string $token
     * @return Response
     */
    public function confirmAction(UserInterface $user, $email_canonical, $token)
    {
        $emailChangeRequest = $this->userEmailChangeRequestService->getForUser($user);
        if (empty($emailChangeRequest)) {
            throw new NotFoundHttpException();
        }

        if ($token !== $emailChangeRequest->getToken()) {
            throw new BadRequestHttpException();
        }

        if ($this->userService->exists($emailChangeRequest->getNewEmail())) {
            $this->userEmailChangeRequestService->removeForUser($user);
            throw new ConflictHttpException();
        }

        $newEmail = $emailChangeRequest->getNewEmail();

        $user->setEmail($newEmail);
        $user->setEmailCanonical($newEmail);
        $user->setUsername($newEmail);
        $user->setUsernameCanonical($newEmail);

        $this->userService->updateUser($user);

        $this->userEmailChangeRequestService->removeForUser($user);

        return new Response();
    }
}
