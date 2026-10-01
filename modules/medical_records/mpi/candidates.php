<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$permissionService->canViewDuplicateCandidates($currentUser)) {
    http_response_code(403);
    exit('Access denied.');
}

$status = (string)($_GET['status'] ?? 'Pending');
$allowedStatuses = ['Pending', 'Confirmed Duplicate', 'Not Duplicate', 'Deferred', 'Merge Requested'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = 'Pending';
}

$page = max(1, (int)($_GET['page'] ?? 1));
$result = $patientService->getDuplicateCandidates($status, $page, 25);

$pageTitle = 'Possible Duplicate Patients';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';

require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>

<div class="main-container">
<?php require __DIR__ . '/../../../layouts/navbar.php'; ?>

<main class="content">
    <div class="page-header">
        <div>
            <h1>Possible Duplicate Patients</h1>
            <p>Review patient records that matched MPI duplicate-detection rules.</p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="index.php">MPI Search</a>
            <button class="btn-secondary" type="button" onclick="window.print()">Print Duplicate List</button>
        </div>
    </div>

    <form method="get" class="card compact-filter no-print">
        <div class="form-grid">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach ($allowedStatuses as $statusOption): ?>
                        <option value="<?= e($statusOption) ?>" <?= $statusOption === $status ? 'selected' : '' ?>>
                            <?= e($statusOption) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn-primary" type="submit">Filter</button>
        </div>
    </form>

    <section class="card">
        <div class="card-header">
            <div>
                <h2><?= e($status) ?> Duplicate Cases</h2>
                <p>Open each case to compare demographics, identifiers, and review decision.</p>
            </div>
        </div>

        <?php if (($result['data'] ?? []) === []): ?>
            <div class="empty-state">No duplicate cases match this status.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="summary-table data-table">
                    <thead>
                        <tr>
                            <th>Classification</th>
                            <th>Score</th>
                            <th>Patient A</th>
                            <th>Patient B</th>
                            <th>Detected</th>
                            <th class="no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['data'] as $case): ?>
                            <tr>
                                <td><?= e((string)($case['classification'] ?? 'Possible Match')) ?></td>
                                <td><?= e((string)($case['match_score'] ?? '')) ?></td>
                                <td>
                                    <strong><?= e((string)($case['low_hospital_number'] ?? '')) ?></strong><br>
                                    <?= e((string)($case['low_patient_name'] ?? '')) ?>
                                </td>
                                <td>
                                    <strong><?= e((string)($case['high_hospital_number'] ?? '')) ?></strong><br>
                                    <?= e((string)($case['high_patient_name'] ?? '')) ?>
                                </td>
                                <td><?= !empty($case['detected_at']) ? e(date('d M Y h:i A', strtotime((string)$case['detected_at']))) : '—' ?></td>
                                <td class="table-actions no-print">
                                    <a class="btn-primary btn-sm" href="candidate.php?id=<?= (int)$case['id'] ?>">Compare</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/../../../layouts/footer.php'; ?>
</div>
