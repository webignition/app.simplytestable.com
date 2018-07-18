<?php

namespace Tests\ApiBundle\Command;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use Tests\ApiBundle\Factory\ConnectExceptionFactory;
use Tests\ApiBundle\Factory\HtmlDocumentFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Tests\ApiBundle\Services\TestHttpClientService;
use webignition\Url\Resolver\Resolver;
use webignition\GuzzleHttp\Exception\CurlException\Factory as GuzzleCurlExceptionFactory;

class UrlResolverTest extends AbstractBaseTestCase
{
    private $longUrlParts = [
        'http://example.com/',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
        '1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890',
    ];

    /**
     * @var TestHttpClientService
     */
    private $httpClientService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->httpClientService = self::$container->get(HttpClientService::class);
    }

    /**
     * @dataProvider resolveWithCurlExceptionDataProvider
     *
     * @param $curlCode
     */
    public function testResolveWithCurlException($curlCode)
    {
        $curlException = ConnectExceptionFactory::create('CURL/'. $curlCode . ' foo');

        $this->httpClientService->appendFixtures([
            $curlException,
            $curlException,
            $curlException,
            $curlException,
            $curlException,
            $curlException,
        ]);

        $resolver = self::$container->get(Resolver::class);

        try {
            $resolver->resolve('http://example.com/');
        } catch (ConnectException $connectException) {
            $curlException = GuzzleCurlExceptionFactory::fromConnectException($connectException);

            $this->assertEquals($curlCode, $curlException->getCurlCode());
        }
    }

    /**
     * @return array
     */
    public function resolveWithCurlExceptionDataProvider()
    {
        return [
            'curl 6' => [
                'curlCode' => 6,
            ],
            'curl 28' => [
                'curlCode' => 28,
            ],
        ];
    }

    /**
     * @dataProvider resolveDataProvider
     *
     * @param array $httpFixtures
     * @param string $expectedResolvedUrl
     */
    public function testResolve($httpFixtures, $expectedResolvedUrl)
    {
        $this->httpClientService->appendFixtures($httpFixtures);

        $resolver = self::$container->get(Resolver::class);
        $resolvedUrl = $resolver->resolve('http://example.com/');

        $this->assertEquals($expectedResolvedUrl, $resolvedUrl);
    }

    /**
     * @return array
     */
    public function resolveDataProvider()
    {
        $successResponse = new Response();
        $movedPermanentlyRedirectResponse = new Response(301, ['location' => 'http://foo.example.com/bar']);
        $movedPermanentlyRedirectResponseLongUrlParts = new Response(
            301,
            ['location' => implode('', $this->longUrlParts)]
        );

        return [
            'no change to url' => [
                'httpFixtures' => [
                    $successResponse,
                ],
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'change to url' => [
                'httpFixtures' => [
                    $movedPermanentlyRedirectResponse,
                    $successResponse,
                ],
                'expectedResolvedUrl' => 'http://foo.example.com/bar',
            ],
            'resolves very long url' => [
                'httpFixtures' => [
                    $movedPermanentlyRedirectResponseLongUrlParts,
                    $successResponse,
                ],
                'expectedResolvedUrl' => implode('', $this->longUrlParts),
            ],
            'too many redirects' => [
                'httpFixtures' => [
                    $movedPermanentlyRedirectResponse,
                    $movedPermanentlyRedirectResponse,
                    $movedPermanentlyRedirectResponse,
                    $movedPermanentlyRedirectResponse,
                    $movedPermanentlyRedirectResponse,
                    $movedPermanentlyRedirectResponse,
                ],
                'expectedResolvedUrl' => 'http://foo.example.com/bar',
            ],
            'meta redirect absolute url' => [
                'httpFixtures' => [
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::createMetaRedirectDocument('http://meta-redirect.example.com/')
                    ),
                    $successResponse,
                ],
                'expectedResolvedUrl' => 'http://meta-redirect.example.com/',
            ],
            'meta redirect relative url' => [
                'httpFixtures' => [
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::createMetaRedirectDocument('/foo')
                    ),
                    $successResponse,
                ],
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'meta redirect schemeless url' => [
                'httpFixtures' => [
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::createMetaRedirectDocument('//foo.example.com')
                    ),
                    $successResponse,
                ],
                'expectedResolvedUrl' => 'http://foo.example.com',
            ],
            'meta redirect same url' => [
                'httpFixtures' => [
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::createMetaRedirectDocument('//example.com')
                    ),
                    $successResponse,
                ],
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'meta redirect no url' => [
                'httpFixtures' => [
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::createMetaRedirectDocument(null)
                    ),
                    $successResponse,
                ],
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'meta redirect invalid content type' => [
                'httpFixtures' => [
                    new Response(
                        200,
                        ['content-type' => 'text/plain'],
                        HtmlDocumentFactory::createMetaRedirectDocument('http//foo.example.com/')
                    ),
                    $successResponse,
                ],
                'expectedResolvedUrl' => 'http://example.com/',
            ],
        ];
    }
}
