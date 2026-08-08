<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

if (!$permissionService->hasPermission('view_medical_record', $currentUser)) {
    http_response_code(403);
    exit('Access denied.');
}

$filters = [
    'query' => (string)($_GET['query'] ?? ''),
    'hospital_number' => (string)($_GET['hospital_number'] ?? ''),
    'alternate_identifier' => (string)($_GET['alternate_identifier'] ?? ''),
    'first_name' => (string)($_GET['first_name'] ?? ''),
    'middle_name' => (string)($_GET['middle_name'] ?? ''),
    'last_name' => (string)($_GET['last_name'] ?? ''),
    'phone' => (string)($_GET['phone'] ?? ''),
    'email' => (string)($_GET['email'] ?? ''),
    'date_of_birth' => (string)($_GET['date_of_birth'] ?? ''),
    'gender' => (string)($_GET['gender'] ?? '')
];
$searched = array_filter(
    $filters,
    static fn (string $value): bool => trim($value) !== ''
) !== [];
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = $settingsService->getInteger('mpi.search_page_size', 25);
$result = $searched
    ? $patientService->searchPatientsPaginated($filters, $page, $pageSize)
    : [
        'records' => [],
        'total_results' => 0,
        'total_pages' => 1,
        'current_page' => 1
    ];

$pageTitle = 'Master Patient Index';
$moduleStylesheet = '/modules/medical_records/assets/medical_records.css';
require __DIR__ . '/../../../layouts/header.php';
require __DIR__ . '/../../../layouts/sidebar.php';
?>
<div class="main-container">
    <?php require __DIR__ . '/../../../layouts/navbar.php'; ?>
    <main class="content">
        <div class="page-header">
            <div>
                <h1>Master Patient Index</h1>
                <p>Exact hospital numbers and identifiers are ranked before indexed prefix matches.</p>
            </div>
            <?php if ($permissionService->canViewDuplicateCandidates($currentUser)): ?>
                <a class="btn-secondary" href="candidates.php">Duplicate Cases</a>
            <?php endif; ?>
        </div>

        <form class="card" method="get">
            <div>
                <label for="query">Quick MPI Search</label>
                <input
                    id="query"
                    name="query"
                    value="<?= e($filters['query']) ?>"
                    placeholder="Hospital number, identifier, phone, or name prefix"
                >
            </div>
            <div class="chart-detail-grid">
                <?php foreach ([
                    'hospital_number' => 'Hospital Number',
                    'alternate_identifier' => 'Alternate Identifier',
                    'first_name' => 'First Name',
                    'middle_name' => 'Middle Name',
                    'last_name' => 'Last Name',
                    'phone' => 'Phone',
                    'email' => 'Email',
                    'date_of_birth' => 'Date of Birth'
                ] as $key => $label): ?>
                    <div>
                        <label for="<?= e($key) ?>"><?= e($label) ?></label>
                        <input
                            id="<?= e($key) ?>"
                            <?= $key === 'date_of_birth' ? 'type="date"' : '' ?>
                            name="<?= e($key) ?>"
                            value="<?= e($filters[$key]) ?>"
                        >
                    </div>
                <?php endforeach; ?>
                <div>
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Any</option>
                        <?php foreach (['Male', 'Female', 'Other', 'Unknown'] as $gender): ?>
                            <option
                                value="<?= e($gender) ?>"
                                <?= $filters['gender'] === $gender ? 'selected' : '' ?>
                            ><?= e($gender) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button class="btn-primary">Search</button>
        </form>

        <?php if ($searched): ?>
            <div class="card">
                <h2><?= (int)$result['total_results'] ?> result(s)</h2>
                <?php if ($result['records'] === []): ?>
                    <p>No matching patient record was found.</p>
                <?php endif; ?>
                <?php foreach ($result['records'] as $record): ?>
                    <div class="history-entry">
                        <strong>
                            <?= e($record['hospital_number']) ?> —
                            <?= e($record['first_name'] . ' ' . $record['last_name']) ?>
                        </strong>
                        <p>
                            <?= e($record['date_of_birth']) ?> ·
                            <?= e($record['phone'] ?? '-') ?>
                        </p>
                        <a href="../chart.php?patient=<?= (int)$record['id'] ?>">
                            Open Patient Chart
                        </a>
                    </div>
                <?php endforeach; ?>
                <?php if ((int)$result['total_pages'] > 1): ?>
                    <nav aria-label="MPI result pages">
                        <?php for ($pageNumber = 1; $pageNumber <= (int)$result['total_pages']; $pageNumber++): ?>
                            <?php $pageQuery = array_merge($_GET, ['page' => $pageNumber]); ?>
                            <a
                                href="?<?= e(http_build_query($pageQuery)) ?>"
                                <?= $pageNumber === (int)$result['current_page'] ? 'aria-current="page"' : '' ?>
                            ><?= $pageNumber ?></a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../../../layouts/footer.php'; ?>
</div>
