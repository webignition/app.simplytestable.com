<?php

namespace App\Services;

use App\Services\FixtureLoader\FixtureLoaderInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixtureLoader
{
    /**
     * @var FixtureLoaderInterface[]
     */
    private $fixtureLoaders = [];

    public function addFixtureLoader(FixtureLoaderInterface $fixtureLoader)
    {
        $this->fixtureLoaders[] = $fixtureLoader;
    }

    public function load(?OutputInterface $output = null)
    {
        foreach ($this->fixtureLoaders as $fixtureLoader) {
            $fixtureLoader->load($output);
        }
    }
}
