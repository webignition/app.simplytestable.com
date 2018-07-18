<?php

namespace SimplyTestable\ApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SimplyTestableApiExtension extends Extension
{
    /**
     * @var string[]
     */
    private $parameterFiles = [
        'content_type_web_resource_map.yml',
        'curl_options.yml',
        'css_validation_domains_to_ignore.yml',
        'js_static_analysis_domains_to_ignore.yml',
    ];

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $fileLocator = new FileLocator([
            __DIR__.'/../Resources/config',
        ]);

        foreach ($this->parameterFiles as $parameterFile) {
            $parameterName = str_replace('.yml', '', $parameterFile);
            $container->setParameter(
                $parameterName,
                Yaml::parse(file_get_contents($fileLocator->locate($parameterFile)))
            );
        }
    }
}
