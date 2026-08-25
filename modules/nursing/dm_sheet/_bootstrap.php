<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if (!$diabetesMonitoringTablesReady || $diabetesMonitoringService === null) {
    http_response_code(503);
    exit('DM Sheet tables are not available yet. Apply Migration 048 to enable this section.');
}

function dmSheetFlash(array $result, string $successMessage): void
{
    if (($result['success'] ?? false) === true) {
        $_SESSION['success_message'] = $successMessage;
        return;
    }

    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to complete the DM Sheet action.'];
}

function dmSheetBackToWorkspace(int $visitId): string
{
    return '../../visits/workspace.php?id=' . $visitId . '&tab=nursing';
}

function dmSheetBackToChart(int $patientId): string
{
    return '../../medical_records/chart.php?patient=' . $patientId . '&tab=dm_sheet';
}
