<?php
defined('C5_EXECUTE') or die("Access Denied.");
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0"><?= t('Japanese Prefecture Population Search') ?></h2>
                    <p class="mb-0"><?= t('Search population data by Japanese prefecture and year') ?></p>
                </div>
                <div class="card-body">
                    
                    <form method="get" action="" id="searchForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="prefecture" class="form-label"><?= t('Prefecture') ?></label>
                                    <select name="prefecture" id="prefecture" class="form-select" required>
                                        <option value=""><?= t('-- Select Prefecture --') ?></option>
                                        <?php foreach ($prefectures as $prefecture): ?>
                                        <option value="<?= h($prefecture) ?>" 
                                                <?= ($selectedPrefecture === $prefecture) ? 'selected' : '' ?>>
                                            <?= h($prefecture) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="year" class="form-label"><?= t('Year') ?></label>
                                    <select name="year" id="year" class="form-select" required>
                                        <option value=""><?= t('-- Select Year --') ?></option>
                                        <?php foreach ($years as $year): ?>
                                        <option value="<?= $year ?>" 
                                                <?= ($selectedYear == $year) ? 'selected' : '' ?>>
                                            <?= $year ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> <?= t('Search Population') ?>
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearForm()">
                                <i class="fa fa-times"></i> <?= t('Clear') ?>
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div id="results">
                        <?php if ($result): ?>
                        <div class="alert alert-success">
                            <h4 class="alert-heading"><?= t('Population Found') ?></h4>
                            <div class="row">
                                <div class="col-sm-4"><strong><?= t('Prefecture:') ?></strong></div>
                                <div class="col-sm-8"><?= h($result->getPrefecture()) ?></div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4"><strong><?= t('Year:') ?></strong></div>
                                <div class="col-sm-8"><?= $result->getYear() ?></div>
                            </div>
                            <div class="row">
                                <div class="col-sm-4"><strong><?= t('Population:') ?></strong></div>
                                <div class="col-sm-8" id="populationNumber"><?= number_format($result->getPopulation()) ?></div>
                            </div>
                            <?php if ($result->getPrefectureCode()): ?>
                            <div class="row">
                                <div class="col-sm-4"><strong><?= t('Prefecture Code:') ?></strong></div>
                                <div class="col-sm-8"><?= h($result->getPrefectureCode()) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php elseif ($error): ?>
                        <div class="alert alert-warning">
                            <h4 class="alert-heading"><?= t('No Data Found') ?></h4>
                            <p class="mb-0"><?= h($error) ?></p>
                        </div>
                        <?php elseif ($selectedPrefecture || $selectedYear): ?>
                        <div class="alert alert-info">
                            <p class="mb-0"><?= t('Please select both prefecture and year to search.') ?></p>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-light">
                            <p class="mb-0"><?= t('Select a prefecture and year above to search for population data.') ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div id="statsSection" class="mt-4" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <h5><?= t('Prefecture Population Statistics') ?></h5>
                            </div>
                            <div class="card-body">
                                <div id="statsContent">
                                    <div class="text-center">
                                        <i class="fa fa-spinner fa-spin"></i> <?= t('Loading statistics...') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
function clearForm() {
    document.getElementById('prefecture').value = '';
    document.getElementById('year').value = '';
    document.getElementById('results').innerHTML = '<div class="alert alert-light"><p class="mb-0"><?= t("Select a country/region and year above to search for population data.") ?></p></div>';
    document.getElementById('statsSection').style.display = 'none';
    
    const url = new URL(window.location);
    url.searchParams.delete('prefecture');
    url.searchParams.delete('year');
    window.history.replaceState({}, '', url);
}


function displayStats(stats) {
    if (!stats || stats.length === 0) {
        document.getElementById('statsContent').innerHTML = '<div class="alert alert-info"><?= t("No statistics available for this prefecture.") ?></div>';
        return;
    }

    let html = '<div class="table-responsive"><table class="table table-striped table-sm">';
    html += '<thead><tr><th><?= t("Year") ?></th><th><?= t("Population") ?></th><th><?= t("Change") ?></th></tr></thead><tbody>';
    
    for (let i = 0; i < stats.length; i++) {
        const stat = stats[i];
        const prevPopulation = i > 0 ? stats[i-1].population : null;
        const change = prevPopulation ? stat.population - prevPopulation : null;
        const changeClass = change > 0 ? 'text-success' : change < 0 ? 'text-danger' : '';
        const changeText = change ? (change > 0 ? '+' : '') + change.toLocaleString() : '-';
        
        html += '<tr>';
        html += '<td>' + stat.year + '</td>';
        html += '<td>' + parseInt(stat.population).toLocaleString() + '</td>';
        html += '<td class="' + changeClass + '">' + changeText + '</td>';
        html += '</tr>';
    }
    html += '</tbody></table></div>';
    
    document.getElementById('statsContent').innerHTML = html;
}

// Auto-submit
document.getElementById('prefecture').addEventListener('change', function() {
    const year = document.getElementById('year').value;
    if (this.value && year) {
        document.getElementById('searchForm').submit();
    }
});

document.getElementById('year').addEventListener('change', function() {
    const prefecture = document.getElementById('prefecture').value;
    if (this.value && prefecture) {
        document.getElementById('searchForm').submit();
    }
});
</script>

<style>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

#populationNumber {
    font-size: 1.2em;
    font-weight: bold;
    color: #28a745;
}

.alert-heading {
    font-size: 1.1em;
}

code {
    background-color: #f8f9fa;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    display: block;
    margin: 0.5rem 0;
}
</style>
