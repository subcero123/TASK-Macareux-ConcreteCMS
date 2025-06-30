<?php

namespace Concrete\Package\PopulationImporter\Controller\SinglePage\Dashboard\PopulationImporter;

use Concrete\Core\Page\Controller\DashboardPageController;
use Concrete\Core\Http\Request;
use Concrete\Core\Http\ResponseFactory;
use Concrete\Core\File\Importer;
use Concrete\Core\Error\UserMessageException;
use Concrete\Package\PopulationImporter\Entity\Population;
use Doctrine\ORM\EntityManagerInterface;

defined('C5_EXECUTE') or die('Access Denied.');

class Import extends DashboardPageController
{
    public function view()
    {
        $this->set('pageTitle', t('Import Population Data'));
        $this->set('importStats', $this->getImportStats());
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->app->make(EntityManagerInterface::class);
    }

    public function upload_csv()
    {
        $request = Request::getInstance();

        if (!$this->token->validate('upload_csv')) {
            $this->error->add(t('Invalid token. Please try again.'));
            $this->view();
            return;
        }

        $file = $request->files->get('csv_file');

        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            $this->error->add(t('Please select a valid CSV file.'));
            $this->view();
            return;
        }

        // Validate file t
        $allowedTypes = ['text/csv', 'text/plain', 'application/csv'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $this->error->add(t('Please upload a CSV file.'));
            $this->view();
            return;
        }

        try {
            $importedCount = $this->processCsvFile($file->getPathname());
            $this->flash('success', t('Successfully imported %s population records.', $importedCount));
            $this->redirect('/dashboard/population_importer/import');
        } catch (\Exception $e) {
            $this->error->add(t('Error importing CSV: %s', $e->getMessage()));
            $this->view();
        }
    }


    public function clear_data()
    {
        if (!$this->token->validate('clear_data')) {
            $this->error->add(t('Invalid token. Please try again.'));
            $this->view();
            return;
        }

        try {
            $em = $this->getEntityManager();
            $qb = $em->createQueryBuilder();
            $query = $qb->delete(Population::class, 'p')->getQuery();
            $deletedCount = $query->execute();

            $this->flash('success', t('Successfully deleted %s population records.', $deletedCount));
            $this->redirect('/dashboard/population_importer/import');
        } catch (\Exception $e) {
            $this->error->add(t('Error clearing data: %s', $e->getMessage()));
            $this->view();
        }
    }

    // Shift-JIS to UTF-8 conversion and CSV processing
    private function processCsvFile(string $filePath): int
    {
        if (!file_exists($filePath)) {
            throw new \Exception(t('File not found.'));
        }

        // Detect and convert from Shift-JIS to UTF-8
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \Exception(t('Unable to read file.'));
        }

        // Try to detect encoding and convert to UTF-8
        $encodings = ['SJIS', 'SJIS-win', 'Shift_JIS', 'CP932', 'UTF-8', 'EUC-JP'];
        $detectedEncoding = mb_detect_encoding($content, $encodings, true);

        if ($detectedEncoding && $detectedEncoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $detectedEncoding);
        } elseif (!$detectedEncoding) {
            // Force conversion from Shift-JIS 
            $content = mb_convert_encoding($content, 'UTF-8', 'SJIS');
        }

        // UTF-8 content
        $tempFile = tempnam(sys_get_temp_dir(), 'population_csv_');
        file_put_contents($tempFile, $content);

        try {
            $importedCount = $this->parseCsvData($tempFile);
            return $importedCount;
        } finally {
            unlink($tempFile);
        }
    }


    private function parseCsvData(string $filePath): int
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception(t('Unable to open CSV file.'));
        }

        $importedCount = 0;
        $lineNumber = 0;
        $yearHeaders = null;
        $dataStarted = false;

        try {
            while (($data = fgetcsv($handle)) !== false) {
                $lineNumber++;

                // Skip empty lines
                if (empty(array_filter($data))) {
                    continue;
                }

                // Look for the year header row 
                if (!$dataStarted && isset($data[1]) && is_numeric($data[1])) {
                    $yearHeaders = array_slice($data, 1); // Skip first column (prefecture names)
                    $dataStarted = true;
                    continue;
                }

                if (!$dataStarted) {
                    continue;
                }

                if ($yearHeaders && $this->processPrefectureMatrixRow($data, $yearHeaders, $lineNumber)) {
                    $importedCount++;
                }

                // Batch processing to avoid memory issues
                if ($importedCount % 50 === 0) {
                    $em = $this->getEntityManager();
                    $em->flush();
                    $em->clear();
                }
            }
            $em = $this->getEntityManager();
            $em->flush();

        } finally {
            fclose($handle);
        }

        return $importedCount;
    }

    private function processPrefectureMatrixRow(array $data, array $yearHeaders, int $lineNumber): bool
    {
        try {
            $prefectureRaw = trim($data[0] ?? '');
            if (empty($prefectureRaw)) {
                return false;
            }


            $prefecture = $this->cleanPrefectureName($prefectureRaw);
            if (empty($prefecture)) {
                return false;
            }

            $recordsProcessed = 0;
            $em = $this->getEntityManager();

            // Process each year column
            for ($i = 1; $i < count($data) && ($i - 1) < count($yearHeaders); $i++) {
                $year = (int) trim($yearHeaders[$i - 1]);
                $populationRaw = trim($data[$i] ?? '');

                // Skip empty population values
                if (empty($populationRaw) || $populationRaw === 'ｃ') {
                    continue;
                }

                // Clean population number
                $population = (int) str_replace([',', ' '], '', $populationRaw);

                if ($year < 1800 || $year > 2100 || $population <= 0) {
                    continue;
                }

                $existing = $em->getRepository(Population::class)
                    ->findOneBy(['prefecture' => $prefecture, 'year' => $year]);

                if ($existing) {
                    $existing->setPopulation($population);
                    $existing->setUpdatedAt(new \DateTime());
                } else {
                    $population_entity = new Population();
                    $population_entity->setPrefecture($prefecture);
                    $population_entity->setYear($year);
                    $population_entity->setPopulation($population);

                    $prefectureCode = $this->extractPrefectureCode($prefectureRaw);
                    if ($prefectureCode) {
                        $population_entity->setPrefectureCode($prefectureCode);
                    }

                    $em->persist($population_entity);
                }

                $recordsProcessed++;
            }

            return $recordsProcessed > 0;

        } catch (\Exception $e) {
            error_log("Error processing prefecture matrix line {$lineNumber}: " . $e->getMessage());
            return false;
        }
    }

    // data cleaning
    private function cleanPrefectureName(string $raw): string
    {
        // Remove leading numbers and spaces
        $cleaned = preg_replace('/^\d+\s*/', '', $raw);

        // Handle special cases
        $prefectureMap = [
            '全　国' => 'Japan',
            '全国' => 'Japan',
            '東京都' => 'Tokyo',
            '大阪府' => 'Osaka',
            '北海道' => 'Hokkaido',
            '青森' => 'Aomori',
            '岩手県' => 'Iwate',
            '宮城県' => 'Miyagi',
            '秋田県' => 'Akita',
            '山形県' => 'Yamagata',
            '福島県' => 'Fukushima',
            '茨城県' => 'Ibaraki',
            '栃木県' => 'Tochigi',
            '群馬県' => 'Gunma',
            '埼玉県' => 'Saitama',
            '千葉県' => 'Chiba',
            '神奈川県' => 'Kanagawa',
            '新潟県' => 'Niigata',
            '富山県' => 'Toyama',
            '石川県' => 'Ishikawa',
            '福井県' => 'Fukui',
            '山梨県' => 'Yamanashi',
            '長野県' => 'Nagano',
            '岐阜県' => 'Gifu',
            '静岡県' => 'Shizuoka',
            '愛知県' => 'Aichi',
            '三重県' => 'Mie',
            '滋賀県' => 'Shiga',
            '京都府' => 'Kyoto',
            '兵庫県' => 'Hyogo',
            '奈良県' => 'Nara',
            '和歌山県' => 'Wakayama',
            '鳥取県' => 'Tottori',
            '島根県' => 'Shimane',
            '岡山県' => 'Okayama',
            '広島県' => 'Hiroshima',
            '山口県' => 'Yamaguchi',
            '徳島県' => 'Tokushima',
            '香川県' => 'Kagawa',
            '愛媛県' => 'Ehime',
            '高知県' => 'Kochi',
            '福岡県' => 'Fukuoka',
            '佐賀県' => 'Saga',
            '長崎県' => 'Nagasaki',
            '熊本県' => 'Kumamoto',
            '大分県' => 'Oita',
            '宮崎県' => 'Miyazaki',
            '鹿児島県' => 'Kagoshima',
            '沖縄県' => 'Okinawa'
        ];

        // Try exact match first
        if (isset($prefectureMap[$cleaned])) {
            return $prefectureMap[$cleaned];
        }

        // Try partial matches for cases where suffix might vary
        foreach ($prefectureMap as $japanese => $english) {
            if (strpos($cleaned, str_replace(['県', '府', '都'], '', $japanese)) !== false) {
                return $english;
            }
        }

        return $cleaned ?: $raw;
    }

    // Decrypt prefecture code from raw name
    private function extractPrefectureCode(string $raw): ?string
    {
        if (preg_match('/^(\d{2})\s/', $raw, $matches)) {
            return $matches[1];
        }
        return null;
    }



    private function processJapaneseDataRow(array $data, int $lineNumber): bool
    {
        try {
            // Format: [year, total_population, male_population, female_population]
            // Some years have asterisk (*) which we need to remove
            $year = (int) trim(str_replace('*', '', $data[0] ?? ''));
            $totalPopulation = (int) str_replace([',', ' '], '', $data[1] ?? '0');

            // Validate data
            if ($year < 1800 || $year > 2100 || $totalPopulation <= 0) {
                return false;
            }

            // For Japanese national data, we'll use "Japan" as the prefecture
            $prefecture = 'Japan';

            // Check if record already exists
            $em = $this->getEntityManager();
            $existing = $em->getRepository(Population::class)
                ->findOneBy(['prefecture' => $prefecture, 'year' => $year]);

            if ($existing) {
                $existing->setPopulation($totalPopulation);
                $existing->setUpdatedAt(new \DateTime());
            } else {
                $population_entity = new Population();
                $population_entity->setPrefecture($prefecture);
                $population_entity->setYear($year);
                $population_entity->setPopulation($totalPopulation);
                $population_entity->setPrefectureCode('00'); // National level code

                $em->persist($population_entity);
            }

            return true;

        } catch (\Exception $e) {
            error_log("Error processing Japanese data line {$lineNumber}: " . $e->getMessage());
            return false;
        }
    }

    // Processing of a data row
    private function processDataRow(array $data, array $headers, int $lineNumber): bool
    {
        try {
            // Map headers to find the relevant columns
            $prefectureIndex = $this->findColumnIndex($headers, ['prefecture', '都道府県', 'prefectural']);
            $yearIndex = $this->findColumnIndex($headers, ['year', '年', 'year']);
            $populationIndex = $this->findColumnIndex($headers, ['population', '人口', 'total']);

            if ($prefectureIndex === false || $yearIndex === false || $populationIndex === false) {
                // Try to guess from position if headers are unclear
                if (count($data) >= 3) {
                    $prefectureIndex = 0;
                    $yearIndex = 1;
                    $populationIndex = 2;
                } else {
                    return false;
                }
            }

            $prefecture = trim($data[$prefectureIndex] ?? '');
            $year = (int) ($data[$yearIndex] ?? 0);
            $population = (int) str_replace([',', ' '], '', $data[$populationIndex] ?? '0');

            // Validate data
            if (empty($prefecture) || $year < 1900 || $year > 2100 || $population < 0) {
                return false;
            }

            // Check if record already exists
            $em = $this->getEntityManager();
            $existing = $em->getRepository(Population::class)
                ->findOneBy(['prefecture' => $prefecture, 'year' => $year]);

            if ($existing) {
                $existing->setPopulation($population);
            } else {
                $population_entity = new Population();
                $population_entity->setPrefecture($prefecture);
                $population_entity->setYear($year);
                $population_entity->setPopulation($population);

                $em->persist($population_entity);
            }

            return true;

        } catch (\Exception $e) {
            error_log("Error processing line {$lineNumber}: " . $e->getMessage());
            return false;
        }
    }

    private function findColumnIndex(array $headers, array $possibleNames): int|false
    {
        foreach ($headers as $index => $header) {
            $normalizedHeader = strtolower(trim($header));
            foreach ($possibleNames as $name) {
                if (strpos($normalizedHeader, strtolower($name)) !== false) {
                    return $index;
                }
            }
        }
        return false;
    }
    private function getImportStats(): array
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

        return [
            'total_records' => $totalRecords,
            'prefecture_count' => $prefectureCount,
            'year_range' => $yearRange,
        ];
    }
}