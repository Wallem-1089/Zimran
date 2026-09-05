<?php

declare(strict_types=1);

$bloodCardLaboratoryPreviewRows = array_slice($bloodCardLaboratoryHistory ?? [], 0, 10);
$bloodCardDocumentPreviewRows = array_slice($bloodCardDocuments ?? [], 0, 10);
?>
<section class="card">
    <div class="section-heading">
        <div>
            <h2>Blood Card</h2>
            <p>Current blood-group/genotype summary with blood-related laboratory and document history.</p>
        </div>
        <div class="form-actions">
            <button class="btn-secondary" type="button" onclick="window.print()">Print Blood Card</button>
            <?php if (!empty($canUploadMedicalDocuments)): ?>
                <a class="btn-secondary" href="documents/upload.php?patient=<?= (int)$patient['id'] ?><?= e($chartContextQuery ?? '') ?>">Upload Blood Document</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-item">
            <span class="summary-label">Blood Group</span>
            <span class="summary-value"><?= e((string)($patient['blood_group'] ?? 'Not recorded')) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Genotype</span>
            <span class="summary-value"><?= e((string)($patient['genotype'] ?? 'Not recorded')) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Blood-Related Lab Records</span>
            <span class="summary-value"><?= count($bloodCardLaboratoryHistory ?? []) ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Blood-Related Documents</span>
            <span class="summary-value"><?= count($bloodCardDocuments ?? []) ?></span>
        </div>
    </div>
</section>

<section class="card">
    <div class="section-heading">
        <div>
            <h3>Blood-Related Laboratory History</h3>
            <p>Filtered from existing Laboratory requests/results. This is not yet a structured blood-bank workflow.</p>
        </div>
        <?php if (!empty($canViewLaboratory)): ?>
            <a class="btn-secondary" href="chart.php?patient=<?= (int)$patient['id'] ?>&tab=laboratory<?= e($chartContextQuery ?? '') ?>">Open Laboratory History</a>
        <?php endif; ?>
    </div>

    <?php if (empty($bloodCardLaboratoryHistory)): ?>
        <p class="text-muted">No blood-related laboratory records found.</p>
    <?php else: ?>
        <?php if (count($bloodCardLaboratoryHistory) > count($bloodCardLaboratoryPreviewRows)): ?>
            <p class="text-muted">Showing latest <?= count($bloodCardLaboratoryPreviewRows) ?> of <?= count($bloodCardLaboratoryHistory) ?> blood-related lab records. Open laboratory history to see all records.</p>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Encounter</th>
                        <th>Tests</th>
                        <th>Status</th>
                        <th>Result Summary</th>
                        <th class="no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bloodCardLaboratoryPreviewRows as $request): ?>
                        <?php
                        $summaryParts = array_filter([
                            trim((string)($request['sample_taken'] ?? '')),
                            trim((string)($request['findings'] ?? '')),
                            trim((string)($request['result'] ?? '')),
                            trim((string)($request['interpretation'] ?? '')),
                        ]);
                        $summary = implode(' | ', $summaryParts);
                        ?>
                        <tr>
                            <td><?= e((string)($request['created_at'] ?? '-')) ?></td>
                            <td><?= e((string)($request['visit_number'] ?? ('#' . (int)$request['visit_id']))) ?></td>
                            <td><?= e((string)($request['tests_requested'] ?? '-')) ?></td>
                            <td><?= e((string)($request['status'] ?? '-')) ?></td>
                            <td><?= e($summary !== '' ? $summary : 'No result text recorded') ?></td>
                            <td class="no-print">
                                <a class="btn-secondary btn-sm" href="../laboratory/view.php?id=<?= (int)$request['id'] ?>">View</a>
                                <a class="btn-secondary btn-sm" href="../visits/workspace.php?id=<?= (int)$request['visit_id'] ?>&tab=laboratory">Open Encounter</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <div class="section-heading">
        <div>
            <h3>Blood-Related Medical Documents</h3>
            <p>Uploaded blood cards, blood result files, crossmatch slips, transfusion forms, and similar documents.</p>
        </div>
        <?php if (!empty($canViewMedicalDocuments)): ?>
            <a class="btn-secondary" href="chart.php?patient=<?= (int)$patient['id'] ?>&tab=documents<?= e($chartContextQuery ?? '') ?>">Open Medical Documents</a>
        <?php endif; ?>
    </div>

    <?php if (empty($bloodCardDocuments)): ?>
        <p class="text-muted">No blood-related Medical Documents found.</p>
    <?php else: ?>
        <?php if (count($bloodCardDocuments) > count($bloodCardDocumentPreviewRows)): ?>
            <p class="text-muted">Showing latest <?= count($bloodCardDocumentPreviewRows) ?> of <?= count($bloodCardDocuments) ?> blood-related documents. Open Medical Documents to see all files.</p>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Uploaded</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Scope</th>
                        <th>Confidentiality</th>
                        <th>Status</th>
                        <th class="no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bloodCardDocumentPreviewRows as $document): ?>
                        <tr>
                            <td><?= e((string)($document['uploaded_at'] ?? '-')) ?></td>
                            <td><?= e((string)($document['title'] ?? '-')) ?><?= !empty($document['confidential_hidden']) ? ' (details hidden)' : '' ?></td>
                            <td><?= e(ucwords(str_replace('_', ' ', (string)($document['document_type'] ?? '')))) ?></td>
                            <td><?= !empty($document['visit_id']) ? 'Encounter #' . (int)$document['visit_id'] : 'Patient Chart' ?></td>
                            <td><?= e((string)($document['confidentiality_level'] ?? '-')) ?></td>
                            <td><?= e((string)($document['document_status'] ?? '-')) ?></td>
                            <td class="no-print">
                                <a class="btn-secondary btn-sm" href="documents/view.php?id=<?= (int)$document['id'] ?><?= e($chartContextQuery ?? '') ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card chart-foundation-notice">
    <h3>Future Laboratory Blood Card</h3>
    <p>
        Structured blood requests, crossmatch records, and transfusion records
        are still planned for a later Laboratory/Blood Bank workflow. This view
        is a current patient-chart summary only.
    </p>
</section>
