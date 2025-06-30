<?php

namespace Concrete\Package\PopulationImporter;

use Concrete\Core\Package\Package;
use Concrete\Core\Database\EntityManager\Provider\DefaultPackageProvider;
use Concrete\Core\Page\Single as SinglePage;
use Concrete\Core\Page\Page;
use Concrete\Core\Database\EntityManager\Provider\ProviderAggregateInterface;
use Concrete\Core\Support\Facade\Application;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

defined('C5_EXECUTE') or die('Access Denied.');


class Controller extends Package implements ProviderAggregateInterface
{

    protected $pkgHandle = 'population_importer';
    
    protected $pkgVersion = '1.0.0';
    
    protected $pkgName = 'Population Importer';
    
    protected $pkgDescription = 'Import and query Japanese population data from CSV files';
    protected $appVersionRequired = '9.0.0';
    protected $pkgAutoloaderRegistries = [
        'src' => '\Concrete\Package\PopulationImporter',
    ];

    public function getEntityManagerProvider()
    {
        return new DefaultPackageProvider($this->app, $this);
    }

    public function install()
    {
        $pkg = parent::install();
        
        $this->installDashboardPages();
        
        $this->installPublicPages();
        
        return $pkg;
    }

    public function uninstall()
    {
        parent::uninstall();
        
        $this->uninstallPages();
    }

    public function on_start()
    {
        // Use doctrine 
        $this->registerEntityNamespace();
    }
    private function installDashboardPages()
    {
        $dashboard = Page::getByPath('/dashboard/population_importer');
        if (!is_object($dashboard) || $dashboard->isError()) {
            $dashboard = SinglePage::add('/dashboard/population_importer', $this);
            if (is_object($dashboard) && !$dashboard->isError()) {
                // description
                $dashboard->updateCollectionName(t('Population Importer'));
                $dashboard->setAttribute('meta_description', t('Manage population data imports'));
            }
        }

        // Import page
        $import = Page::getByPath('/dashboard/population_importer/import');
        if (!is_object($import) || $import->isError()) {
            $import = SinglePage::add('/dashboard/population_importer/import', $this);
            if (is_object($import) && !$import->isError()) {
                $import->updateCollectionName(t('Import CSV'));
                $import->setAttribute('meta_description', t('Import population data from CSV files'));
            }
        }
    }
    private function installPublicPages()
    {
        $search = Page::getByPath('/population_search');
        if (!is_object($search) || $search->isError()) {
            $search = SinglePage::add('/population_search', $this);
            if (is_object($search) && !$search->isError()) {
                $search->updateCollectionName(t('Population Search'));
                $search->setAttribute('meta_description', t('Search population data by prefecture and year'));
            }
        }
    }

    private function uninstallPages()
    {
        $pages = [
            '/dashboard/population_importer/import',
            '/dashboard/population_importer',
            '/population_search'
        ];

        foreach ($pages as $path) {
            $page = Page::getByPath($path);
            if (is_object($page) && !$page->isError()) {
                $page->delete();
            }
        }
    }

    // Use doctrine to register entities 
    private function registerEntityNamespace()
    {
        $em = $this->app->make('database/orm')->entityManager();
        $driver = $em->getConfiguration()->getMetadataDriverImpl();
        
        if ($driver instanceof AnnotationDriver) {
            $driver->addPaths([
                $this->getPackagePath() . '/src/Entity'
            ]);
        }
    }
}