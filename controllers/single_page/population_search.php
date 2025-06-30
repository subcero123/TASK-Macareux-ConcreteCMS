<?php

namespace Concrete\Package\PopulationImporter\Controller\SinglePage;

use Concrete\Core\Page\Controller\PageController;
use Concrete\Core\Http\Request;
use Concrete\Package\PopulationImporter\Entity\Population;
use Doctrine\ORM\EntityManagerInterface;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Population Search Controller
 * 
 */
class PopulationSearch extends PageController
{

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->app->make(EntityManagerInterface::class);
    }

    public function view()
    {
        $request = Request::getInstance();
        
        $this->set('pageTitle', t('Population Search'));
        $this->set('prefectures', $this->getAvailablePrefectures());
        $this->set('years', $this->getAvailableYears());
        
        $selectedPrefecture = $request->query->get('prefecture');
        $selectedYear = $request->query->get('year');
        
        $this->set('selectedPrefecture', $selectedPrefecture);
        $this->set('selectedYear', $selectedYear);
        
        $result = null;
        $error = null;

        // Process search if both parameters are provided
        if ($selectedPrefecture && $selectedYear) {
            try {
                $result = $this->searchPopulation($selectedPrefecture, (int)$selectedYear);
                if ($result === null) {
                    $error = t('No population data found for %s in year %s.', $selectedPrefecture, $selectedYear);
                }
            } catch (\Exception $e) {
                $error = t('Error searching population data: %s', $e->getMessage());
            }
        }

        $this->set('result', $result);
        $this->set('error', $error);
    }

    private function searchPopulation(string $prefecture, int $year): ?Population
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository(Population::class);
        
        return $repository->findOneBy([
            'prefecture' => $prefecture,
            'year' => $year
        ]);
    }

    private function getAvailablePrefectures(): array
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository(Population::class);
        
        $qb = $repository->createQueryBuilder('p');
        $results = $qb
            ->select('DISTINCT p.prefecture')
            ->orderBy('p.prefecture', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($results, 'prefecture');
    }
    private function getAvailableYears(): array
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository(Population::class);
        
        $qb = $repository->createQueryBuilder('p');
        $results = $qb
            ->select('DISTINCT p.year')
            ->orderBy('p.year', 'DESC')
            ->getQuery()
            ->getResult();

        return array_column($results, 'year');
    }

    public function prefecture_stats()
    {
        $request = Request::getInstance();
        $prefecture = $request->query->get('prefecture');

        if (!$prefecture) {
            return $this->app->make('helper/ajax')->sendError(t('Prefecture parameter is required.'));
        }

        try {
            $em = $this->getEntityManager();
            $repository = $em->getRepository(Population::class);
            
            $qb = $repository->createQueryBuilder('p');
            $stats = $qb
                ->select('p.year, p.population')
                ->where('p.prefecture = :prefecture')
                ->setParameter('prefecture', $prefecture)
                ->orderBy('p.year', 'ASC')
                ->getQuery()
                ->getResult();

            return $this->app->make('helper/ajax')->sendResult($stats);

        } catch (\Exception $e) {
            return $this->app->make('helper/ajax')->sendError(t('Error retrieving statistics: %s', $e->getMessage()));
        }
    }

}