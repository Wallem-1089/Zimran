<?php

declare(strict_types=1);

$noteRows = $clinicalNotes['data']['records'] ?? [];

?>
<section class="card">
    <div class="card-header">
        <div>
            <h2>Clinical Notes</h2>
            <p>Longitudinal and encounter-linked notes. Drafts remain unsigned; signed records are locked.</p>
        </div>

        <?php if ($canCreatePatientNotes): ?>
            <a class="btn-primary" href="notes/create.php?patient=<?= (int)$patientId ?><?= e($chartContextQuery) ?>">
                Create Note
            </a>
        <?php endif; ?>
    </div>

    <form method="get" class="filter-form">
        <input type="hidden" name="patient" value="<?= (int)$patientId ?>">
        <input type="hidden" name="tab" value="notes">
        <?php if ($visitId): ?>
            <input type="hidden" name="visit" value="<?= (int)$visitId ?>">
        <?php endif; ?>

        <input name="note_q" value="<?= e($_GET['note_q'] ?? '') ?>" placeholder="Title prefix">

        <select name="note_type">
            <option value="">All types</option>
            <?php foreach ($clinicalNoteService->getAllowedNoteTypes() as $type): ?>
                <option value="<?= e($type) ?>" <?= ($_GET['note_type'] ?? '') === $type ? 'selected' : '' ?>>
                    <?= e(ucwords(str_replace('_', ' ', $type))) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="note_status">
            <option value="">All statuses</option>
            <?php foreach (['Draft', 'Signed', 'Amended', 'Entered-in-error'] as $status): ?>
                <option value="<?= e($status) ?>" <?= ($_GET['note_status'] ?? '') === $status ? 'selected' : '' ?>>
                    <?= e($status) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="note_author_id">
            <option value="">All authors</option>
            <?php foreach ($clinicalNoteFilterOptions['authors'] as $author): ?>
                <option value="<?= (int)$author['id'] ?>" <?= ((int)($_GET['note_author_id'] ?? 0) === (int)$author['id']) ? 'selected' : '' ?>>
                    <?= e($author['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="note_department_id">
            <option value="">All departments</option>
            <?php foreach ($clinicalNoteFilterOptions['departments'] as $department): ?>
                <option value="<?= (int)$department['id'] ?>" <?= ((int)($_GET['note_department_id'] ?? 0) === (int)$department['id']) ? 'selected' : '' ?>>
                    <?= e($department['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>
            From
            <input type="date" name="note_date_from" value="<?= e($_GET['note_date_from'] ?? '') ?>">
        </label>
        <label>
            To
            <input type="date" name="note_date_to" value="<?= e($_GET['note_date_to'] ?? '') ?>">
        </label>

        <button class="btn-secondary">Filter</button>
    </form>

    <?php if (!$noteRows): ?>
        <div class="empty-state">No Clinical Notes are available for this patient.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="summary-table data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Scope</th>
                        <th>Status</th>
                        <th>Author</th>
                        <th>Updated</th>
                        <th class="table-actions-column">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($noteRows as $row): ?>
                        <tr>
                            <td><?= e($row['title']) ?><?= !empty($row['masked']) ? ' (protected)' : '' ?></td>
                            <td><?= e(ucwords(str_replace('_', ' ', $row['note_type']))) ?></td>
                            <td><?= $row['visit_id'] ? 'Encounter #' . (int)$row['visit_id'] : 'Patient' ?></td>
                            <td><?= e($row['note_status']) ?></td>
                            <td><?= e($row['author_name'] ?? 'Protected') ?></td>
                            <td><?= e($row['updated_at'] ?? $row['created_at']) ?></td>
                            <td class="table-actions">
                                <a
                                    class="btn-secondary btn-sm"
                                    href="notes/view.php?id=<?= (int)$row['id'] ?><?= e($chartContextQuery) ?>"
                                >View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
