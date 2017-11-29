<?php

namespace Tests\ApiBundle\Command;

use GuzzleHttp\Exception\ConnectException;
use Tests\ApiBundle\Factory\ConnectExceptionFactory;
use Tests\ApiBundle\Factory\HtmlDocumentFactory;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
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
     * @dataProvider resolveWithCurlExceptionDataProvider
     *
     * @param $curlCode
     */
    public function testResolveWithCurlException($curlCode)
    {
        $this->queueHttpFixtures([
            ConnectExceptionFactory::create('CURL/'. $curlCode . ' foo'),
        ]);

        $resolver = $this->container->get(Resolver::class);

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

    public function testResolveTimeout()
    {
        $resolver = $this->container->get(Resolver::class);

        $configuration = $resolver->getConfiguration();

        $reflection = new \ReflectionClass($configuration);
        $property = $reflection->getProperty('timeoutMs');
        $property->setAccessible(true);
        $property->setValue($configuration, 1);

        $this->expectException(ConnectException::class);
        $this->expectExceptionMessageRegExp('/cURL error 28: Resolving timed out after [0-9]+ milliseconds/');

        $resolver->resolve('http://example.com/');
    }

    /**
     * @dataProvider resolveDataProvider
     *
     * @param array $httpFixtures
     * @param string $expectedResolvedUrl
     */
    public function testResolve($httpFixtures, $expectedResolvedUrl)
    {
        $this->queueHttpFixtures($httpFixtures);

        $resolver = $this->container->get(Resolver::class);
        $resolvedUrl = $resolver->resolve('http://example.com/');

        $this->assertEquals($expectedResolvedUrl, $resolvedUrl);
    }

    /**
     * @return array
     */
    public function resolveDataProvider()
    {
        return [
            'no change to url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'change to url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://foo.example.com/bar',
            ],
            'resolves very long url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse(implode('', $this->longUrlParts)),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => implode('', $this->longUrlParts),
            ],
            'too many redirects' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                    HttpFixtureFactory::createMovedPermanentlyRedirectResponse('http://foo.example.com/bar'),
                ],
                'expectedResolvedUrl' => 'http://foo.example.com/bar',
            ],
            'meta redirect absolute url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::createMetaRedirectDocument('http://meta-redirect.example.com/')
                    ),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://meta-redirect.example.com/',
            ],
            'meta redirect relative url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::createMetaRedirectDocument('/foo')
                    ),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://example.com/foo',
            ],
            'meta redirect schemeless url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::createMetaRedirectDocument('//foo.example.com')
                    ),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://foo.example.com',
            ],
            'meta redirect same url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::createMetaRedirectDocument('//example.com')
                    ),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'meta redirect no url' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::createMetaRedirectDocument(null)
                    ),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://example.com/',
            ],
            'meta redirect invalid content type' => [
                'httpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse(
                        'text/plain',
                        HtmlDocumentFactory::createMetaRedirectDocument('http//foo.example.com/')
                    ),
                    HttpFixtureFactory::createSuccessResponse(),
                ],
                'expectedResolvedUrl' => 'http://example.com/',
            ],
        ];
    }
}
