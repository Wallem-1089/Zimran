<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if (!$dressingTablesReady || $dressingRecordService === null) {
    http_response_code(503);
    exit('Dressing Book tables are not available yet. Apply Migration 045 to enable this section.');
}

function dressingFlash(array $result, string $successMessage): void
{
    if (($result['success'] ?? false) === true) {
        $_SESSION['success_message'] = $successMessage;
        return;
    }

    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to complete the dressing action.'];
}

function dressingBackToWorkspace(int $visitId): string
{
    return '../../visits/workspace.php?id=' . $visitId . '&tab=nursing';
}

function dressingBackToChart(int $patientId): string
{
    return '../../medical_records/chart.php?patient=' . $patientId . '&tab=dressings';
}
