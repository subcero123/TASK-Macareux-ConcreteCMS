<?php
defined('C5_EXECUTE') or die("Access Denied.");
?>

<div class="ccm-dashboard-header-buttons">
    <a href="<?= $view->url('/dashboard/population_importer/import') ?>" class="btn btn-primary">
        <i class="fa fa-upload"></i> <?= t('Import CSV') ?>
    </a>
    <a href="<?= $view->url('/population_search') ?>" class="btn btn-info" target="_blank">
        <i class="fa fa-search"></i> <?= t('View Search Page') ?>
    </a>
</div>

<div class="ccm-dashboard-content-inner">
    
    <div class="row">
        <div class="col-md-12">
            <h2><?= t('Population Data Overview') ?></h2>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary"><?= number_format($stats['total_records']) ?></h3>
                    <p class="card-text"><?= t('Total Records') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success"><?= $stats['prefecture_count'] ?></h3>
                    <p class="card-text"><?= t('Prefectures') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <?php if ($stats['year_range']['min_year']): ?>
                    <h3 class="text-info"><?= $stats['year_range']['min_year'] ?> - <?= $stats['year_range']['max_year'] ?></h3>
                    <p class="card-text"><?= t('Year Range') ?></p>
                    <?php else: ?>
                    <h3 class="text-muted">-</h3>
                    <p class="card-text"><?= t('No Data') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <?php if ($stats['latest_update']): ?>
                    <h6 class="text-warning"><?= $stats['latest_update']->format('M j, Y') ?></h6>
                    <p class="card-text"><?= t('Last Update') ?></p>
                    <?php else: ?>
                    <h6 class="text-muted">-</h6>
                    <p class="card-text"><?= t('Never') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4><?= t('Quick Actions') ?></h4>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="<?= $view->url('/dashboard/population_importer/import') ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="fa fa-upload text-primary"></i> <?= t('Import New CSV File') ?></h6>
                                <small><?= t('Upload data') ?></small>
                            </div>
                            <p class="mb-1"><?= t('Upload and import population data from CSV files') ?></p>
                        </a>

                        <a href="<?= $view->url('/population_search') ?>" class="list-group-item list-group-item-action" target="_blank">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="fa fa-search text-info"></i> <?= t('Search Population Data') ?></h6>
                                <small><?= t('Public page') ?></small>
                            </div>
                            <p class="mb-1"><?= t('Query population by prefecture and year') ?></p>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4><?= t('Recent Data') ?></h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentImports)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th><?= t('Prefecture') ?></th>
                                    <th><?= t('Year') ?></th>
                                    <th><?= t('Population') ?></th>
                                    <th><?= t('Added') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentImports as $record): ?>
                                <tr>
                                    <td><?= h($record->getPrefecture()) ?></td>
                                    <td><?= $record->getYear() ?></td>
                                    <td><?= number_format($record->getPopulation()) ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?= $record->getCreatedAt()->format('M j, H:i') ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-light text-center">
                        <i class="fa fa-database fa-2x text-muted mb-3"></i>
                        <h5><?= t('No Data Available') ?></h5>
                        <p><?= t('Import your first CSV file to get started.') ?></p>
                        <a href="<?= $view->url('/dashboard/population_importer/import') ?>" class="btn btn-primary">
                            <i class="fa fa-upload"></i> <?= t('Import CSV File') ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 1rem;
}

.card h3 {
    margin-bottom: 0.5rem;
}

.list-group-item-action:hover {
    background-color: #f8f9fa;
}

.fa-2x {
    display: block;
    margin: 0 auto;
}

.text-primary { color: #007bff !important; }
.text-success { color: #28a745 !important; }
.text-info { color: #17a2b8 !important; }
.text-warning { color: #ffc107 !important; }
</style>
