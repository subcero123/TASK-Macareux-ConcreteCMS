<?php

namespace Concrete\Package\PopulationImporter\Controller\SinglePage\Dashboard;

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Package\PopulationImporter\Entity\Population;
use Doctrine\ORM\EntityManagerInterface;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Population Importer Controller
 */
class PopulationImporter extends DashboardPageController
{
    public function view()
    {
        $this->set('pageTitle', t('Population Importer'));
        $this->set('stats', $this->getOverallStats());
        $this->set('recentImports', $this->getRecentImports());
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->app->make(EntityManagerInterface::class);
    }

    private function getOverallStats(): array
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository(Population::class);
        
        $totalRecords = $repository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $prefectureCount = $repository->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.prefecture)')
            ->getQuery()
            ->getSingleScalarResult();

        $yearRange = $repository->createQueryBuilder('p')
            ->select('MIN(p.year) as min_year, MAX(p.year) as max_year')
            ->getQuery()
            ->getSingleResult();

        $latestUpdate = $repository->createQueryBuilder('p')
            ->select('MAX(p.updated_at)')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_records' => $totalRecords,
            'prefecture_count' => $prefectureCount,
            'year_range' => $yearRange,
            'latest_update' => $latestUpdate ? new \DateTime($latestUpdate) : null,
        ];
    }

    private function getRecentImports(): array
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository(Population::class);
        
        return $repository->createQueryBuilder('p')
            ->orderBy('p.created_at', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
