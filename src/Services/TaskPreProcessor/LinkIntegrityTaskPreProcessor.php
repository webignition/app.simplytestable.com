<?php

namespace App\Services\TaskPreProcessor;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use App\Entity\Task\Task;
use App\Entity\Task\TaskType;
use App\Repository\TaskRepository;
use App\Services\HttpClientService;
use App\Services\StateService;
use App\Services\TaskService;
use App\Services\TaskTypeService;
use webignition\HtmlDocumentLinkUrlFinder\HtmlDocumentLinkUrlFinder;
use App\Entity\Task\Output;
use webignition\HtmlDocumentLinkUrlFinder\LinkCollection;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResourceInterfaces\WebPageInterface;

class LinkIntegrityTaskPreProcessor implements TaskPreprocessorInterface
{
    const HTTP_USER_AGENT = 'ST Link integrity task pre-processor';

    private $taskService;
    private $webResourceRetriever;
    private $httpClientService;
    private $stateService;
    private $logger;
    private $taskRepository;
    private $entityManager;
    private $linkFinder;

    public function __construct(
        TaskService $taskService,
        WebResourceRetriever $webResourceService,
        HttpClientService $httpClientService,
        LoggerInterface $logger,
        StateService $stateService,
        EntityManagerInterface $entityManager,
        TaskRepository $taskRepository,
        HtmlDocumentLinkUrlFinder $linkFinder
    ) {
        $this->taskService = $taskService;
        $this->webResourceRetriever = $webResourceService;
        $this->httpClientService = $httpClientService;
        $this->logger = $logger;
        $this->stateService = $stateService;
        $this->entityManager = $entityManager;
        $this->taskRepository = $taskRepository;
        $this->linkFinder = $linkFinder;
    }

    /**
     * @param TaskType $taskType
     *
     * @return bool
     */
    public function handles(TaskType $taskType)
    {
        return $taskType->getName() === TaskTypeService::LINK_INTEGRITY_TYPE;
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function process(Task $task)
    {
        $rawTaskOutputs = $this->taskRepository->findOutputByJobAndType($task);

        if (empty($rawTaskOutputs)) {
            return false;
        }

        $webResource = $this->retrieveWebPage($task);
        if (!$webResource instanceof WebPage) {
            return false;
        }

        $linkCollection = $this->linkFinder->getLinkCollection($webResource, $task->getUrl());

        if (0 === count($linkCollection)) {
            $output = $this->createOutput(null);
            $this->completeTask($task, $output);

            return true;
        }

        $existingLinkIntegrityResults = $this->getLinkIntegrityResultsFromRawTaskOutputs($rawTaskOutputs);

        $linkIntegrityResults = $this->getLinkIntegrityResultsFromExistingResults(
            $linkCollection,
            $existingLinkIntegrityResults
        );

        $linkCount = count($linkCollection);
        $linkIntegrityResultCount = count($linkIntegrityResults);

        if (!empty($linkIntegrityResults)) {
            $output = $this->createOutput(json_encode($linkIntegrityResults));

            if ($linkIntegrityResultCount === $linkCount) {
                $this->completeTask($task, $output);

                return true;
            }

            $task->getParameters()->set(
                'excluded-urls',
                $this->getUniqueUrlListFromLinkIntegrityResults($linkIntegrityResults)
            );

            $task->setOutput($output);

            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }

        return false;
    }

    /**
     * @param string $outputContent
     *
     * @return Output
     */
    private function createOutput($outputContent)
    {
        $mediaType = new InternetMediaType();
        $mediaType->setType('application');
        $mediaType->setSubtype('json');

        $output = new Output();
        $output->setOutput($outputContent);
        $output->setContentType($mediaType);
        $output->setErrorCount(0);
        $output->setWarningCount(0);

        return $output;
    }

    /**
     * @param Task $task
     * @param Output $output
     */
    private function completeTask(Task $task, Output $output)
    {
        $taskCompletedState = $this->stateService->get(Task::STATE_COMPLETED);

        $this->taskService->complete(
            $task,
            new \DateTime(),
            $output,
            $taskCompletedState
        );
    }

    /**
     * @param array $linkIntegrityResults
     *
     * @return string[]
     */
    private function getUniqueUrlListFromLinkIntegrityResults($linkIntegrityResults)
    {
        $urls = [];

        foreach ($linkIntegrityResults as $linkIntegrityResult) {
            if (!$this->isLinkIntegrityError($linkIntegrityResult) && !in_array($linkIntegrityResult->url, $urls)) {
                $urls[] = $linkIntegrityResult->url;
            }
        }

        return $urls;
    }

    private function getLinkIntegrityResultsFromExistingResults(
        LinkCollection $linkCollection,
        array $existingLinkIntegrityResults
    ): array {
        $linkIntegrityResults = [];

        foreach ($linkCollection as $link) {
            $linkIntegrityResult = $this->getExistingLinkIntegrityResult(
                $link->getUri(),
                $existingLinkIntegrityResults
            );

            if (!is_null($linkIntegrityResult)) {
                $linkIntegrityResult->context = $link->getElementAsString();
                $linkIntegrityResults[] = $linkIntegrityResult;
            }
        }

        return $linkIntegrityResults;
    }

    /**
     * @param UriInterface $uri
     * @param \stdClass[] $existingLinkIntegrityResults
     *
     * @return \stdClass
     */
    private function getExistingLinkIntegrityResult(UriInterface $uri, $existingLinkIntegrityResults)
    {
        foreach ($existingLinkIntegrityResults as $linkIntegrityResult) {
            if ($linkIntegrityResult->url === (string) $uri) {
                return $linkIntegrityResult;
            }
        }

        return null;
    }

    /**
     * @param array $rawTaskOutputs
     *
     * @return array
     */
    private function getLinkIntegrityResultsFromRawTaskOutputs($rawTaskOutputs)
    {
        $linkIntegrityResults = [];

        foreach ($rawTaskOutputs as $rawTaskOutput) {
            $decodedTaskOutput = json_decode($rawTaskOutput);

            if (is_array($decodedTaskOutput)) {
                foreach ($decodedTaskOutput as $linkIntegrityResult) {
                    if (!$this->isLinkIntegrityError($linkIntegrityResult) && is_object($linkIntegrityResult)) {
                        $linkIntegrityResults[] = $linkIntegrityResult;
                    }
                }
            }
        }

        return $linkIntegrityResults;
    }

    /**
     * @param \stdClass $linkIntegrityResult
     *
     * @return boolean
     */
    private function isLinkIntegrityError($linkIntegrityResult)
    {
        if (!is_object($linkIntegrityResult)) {
            $this->logger->error('LinkIntegrityTaskPreProcessor::isLinkIntegrityError: non-object found');

            return false;
        }

        if ($linkIntegrityResult->type == 'curl') {
            return true;
        }

        return $linkIntegrityResult->state >= 300 && $linkIntegrityResult->state < 600;
    }

    /**
     * @param Task $task
     *
     * @return WebPageInterface|null
     */
    private function retrieveWebPage(Task $task)
    {
        $taskParametersObject = $task->getParameters();

        $cookies = $taskParametersObject->getCookies();
        if (!empty($cookies)) {
            $this->httpClientService->setCookies($cookies);
        }

        $httpAuthenticationCredentials = $taskParametersObject->getHttpAuthenticationCredentials($task->getUrl());
        if (!$httpAuthenticationCredentials->isEmpty()) {
            $this->httpClientService->setBasicHttpAuthorization($httpAuthenticationCredentials);
        }

        $this->httpClientService->setRequestHeader('User-Agent', self::HTTP_USER_AGENT);

        $webPage = null;
        $request = new Request('GET', $task->getUrl());

        try {
            $webPage = $this->webResourceRetriever->retrieve($request);

            if (!$webPage instanceof WebPageInterface) {
                $webPage = null;
            }
        } catch (HttpException $httpException) {
            $this->logger->error(sprintf(
                'LinkIntegrityTaskPreProcessor::retrieveWebPage [%s][http exception][%s]',
                $task->getUrl(),
                $httpException->getResponse()->getStatusCode()
            ));
        } catch (TransportException $transportException) {
            $this->logger->error(sprintf(
                'LinkIntegrityTaskPreProcessor::retrieveWebPage [%s][transport exception][%s]',
                $task->getUrl(),
                $transportException->getCode()
            ));
        } catch (\Exception $exception) {
            $this->logger->error(sprintf(
                'LinkIntegrityTaskPreProcessor::retrieveWebPage [%s][generic exception][%s][%s]',
                $task->getUrl(),
                $exception->getMessage(),
                $exception->getCode()
            ));
        }

        return $webPage;
    }
}
