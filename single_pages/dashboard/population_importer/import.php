<?php
defined('C5_EXECUTE') or die("Access Denied.");
?>

<div class="ccm-dashboard-header-buttons">
    <a href="<?= $view->url('/dashboard/population_importer') ?>" class="btn btn-secondary">
        <?= t('Back to Population Importer') ?>
    </a>
</div>

<div class="ccm-dashboard-content-inner">
    
    <?php if (isset($importStats) && $importStats['total_records'] > 0): ?>
    <div class="alert alert-info">
        <h5><?= t('Current Data Summary') ?></h5>
        <ul class="mb-0">
            <li><?= t('Total Records: %s', number_format($importStats['total_records'])) ?></li>
            <li><?= t('Prefectures: %s', $importStats['prefecture_count']) ?></li>
            <?php if ($importStats['year_range']['min_year']): ?>
            <li><?= t('Year Range: %s - %s', $importStats['year_range']['min_year'], $importStats['year_range']['max_year']) ?></li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3><?= t('Import CSV File') ?></h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <strong><?= t('Japanese Prefecture Data Requirements:') ?></strong>
                        <ul class="mb-0">
                            <li><?= t('Data source: ') ?><a href="https://www.e-stat.go.jp/stat-search/files?tclass=000001041653&cycle=7&year=20220" target="_blank"><?= t('e-Stat Japan') ?></a></li>
                        </ul>
                    </div>

                    <form method="post" action="<?= $view->action('upload_csv') ?>" enctype="multipart/form-data">
                        <?= $token->output('upload_csv') ?>
                        
                        <div class="form-group">
                            <label for="csv_file" class="form-label"><?= t('Select CSV File') ?></label>
                            <input type="file" 
                                   name="csv_file" 
                                   id="csv_file" 
                                   class="form-control" 
                                   accept=".csv,.txt" 
                                   required>
                            <div class="form-text"><?= t('Maximum file size: %s', ini_get('upload_max_filesize')) ?></div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-upload"></i> <?= t('Upload and Import') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4><?= t('Data Management') ?></h4>
                </div>
                <div class="card-body">
                    <p><?= t('Use these tools to manage your population data.') ?></p>
                    
                    <div class="d-grid gap-2">
                        <a href="<?= $view->url('/population_search') ?>" class="btn btn-info" target="_blank">
                            <i class="fa fa-search"></i> <?= t('View Search Page') ?>
                        </a>
                        
                        <?php if (isset($importStats) && $importStats['total_records'] > 0): ?>
                        <button type="button" 
                                class="btn btn-danger" 
                                data-bs-toggle="modal" 
                                data-bs-target="#clearDataModal">
                            <i class="fa fa-trash"></i> <?= t('Clear All Data') ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($importStats) && $importStats['total_records'] > 0): ?>
<div class="modal fade" id="clearDataModal" tabindex="-1" aria-labelledby="clearDataModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearDataModalLabel"><?= t('Clear All Population Data') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= t('Close') ?>"></button>
            </div>
            <div class="modal-body">
                <p><?= t('Are you sure you want to delete all population data?') ?></p>
                <p class="text-danger"><strong><?= t('This action cannot be undone!') ?></strong></p>
                <p><?= t('This will delete %s records from %s prefectures.', 
                    number_format($importStats['total_records']), 
                    $importStats['prefecture_count']) ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= t('Cancel') ?></button>
                <form method="post" action="<?= $view->action('clear_data') ?>" style="display: inline;">
                    <?= $token->output('clear_data') ?>
                    <button type="submit" class="btn btn-danger"><?= t('Delete All Data') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
