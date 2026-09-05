<?php

declare(strict_types=1);

$patientCommunicationHistory = $patientCommunicationHistory ?? [];
$communicationPreviewRows = array_slice($patientCommunicationHistory, 0, 50);
?>

<div class="card">
    <div class="card-header">
        <div>
            <h2>Patient Communications</h2>
            <p>Tracked patient communication handoffs, including WhatsApp handoffs for reports and documents.</p>
        </div>
    </div>

    <?php if (!$patientCommunicationTablesReady): ?>
        <div class="empty-state">Patient communication tables are not available yet. Apply Migration 069 to enable this section.</div>
    <?php elseif ($patientCommunicationHistory === []): ?>
        <div class="empty-state">No patient communications have been tracked yet.</div>
    <?php else: ?>
        <?php if (count($patientCommunicationHistory) > count($communicationPreviewRows)): ?>
            <p class="text-muted">Showing latest <?= count($communicationPreviewRows) ?> of <?= count($patientCommunicationHistory) ?> communications.</p>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Channel</th>
                        <th>Source</th>
                        <th>Visit</th>
                        <th>Recipient</th>
                        <th>Consent</th>
                        <th>Status</th>
                        <th>Sent By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($communicationPreviewRows as $communication): ?>
                        <tr>
                            <td><?= e((string)($communication['created_at'] ?? '-')) ?></td>
                            <td><?= e((string)($communication['channel'] ?? '-')) ?></td>
                            <td>
                                <?= e((string)($communication['source_module'] ?? '-')) ?>
                                <br><small class="text-muted"><?= e((string)($communication['source_type'] ?? '-')) ?> #<?= e((string)($communication['source_record_id'] ?? '-')) ?></small>
                            </td>
                            <td><?= e((string)($communication['visit_number'] ?? '-')) ?></td>
                            <td><?= e((string)($communication['recipient_phone'] ?? '-')) ?></td>
                            <td><?= !empty($communication['consent_confirmed']) ? 'Confirmed' : 'Not confirmed' ?></td>
                            <td><?= e((string)($communication['status'] ?? '-')) ?></td>
                            <td><?= e((string)($communication['sent_by_name'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
