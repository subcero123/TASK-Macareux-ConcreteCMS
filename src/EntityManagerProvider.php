<?php

namespace Concrete\Package\PopulationImporter\Src;

use Concrete\Core\Foundation\Service\Provider as ServiceProvider;
use Concrete\Core\Database\EntityManager\Provider\ProviderInterface;

// Used to provide Doctrine entity manager configuration for the Population importer

class EntityManagerProvider implements ProviderInterface
{
    /**
     * @var \Concrete\Core\Package\Package
     */
    protected $package;


    public function __construct($package)
    {
        $this->package = $package;
    }

    public function getDrivers()
    {
        return [
            'population_importer' => [
                'paths' => [$this->package->getPackagePath() . '/src/Entity'],
                'namespace' => 'Concrete\Package\PopulationImporter\Entity'
            ]
        ];
    }
}
