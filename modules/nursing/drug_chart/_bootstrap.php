<?php

declare(strict_types=1);

require __DIR__ . '/../_bootstrap.php';

if (!$medicationAdministrationTablesReady || $medicationAdministrationService === null) {
    http_response_code(503);
    exit('Drug Chart tables are not available yet. Apply Migration 046 to enable this section.');
}

function drugChartFlash(array $result, string $successMessage): void
{
    if (($result['success'] ?? false) === true) {
        $_SESSION['success_message'] = $successMessage;
        return;
    }

    $_SESSION['validation_errors'] = $result['errors'] ?? ['Unable to complete the drug chart action.'];
}

function drugChartBackToWorkspace(int $visitId): string
{
    return '../../visits/workspace.php?id=' . $visitId . '&tab=nursing';
}

function drugChartBackToChart(int $patientId): string
{
    return '../../medical_records/chart.php?patient=' . $patientId . '&tab=drug_chart';
}
