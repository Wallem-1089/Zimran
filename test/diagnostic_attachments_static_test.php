<?php

declare(strict_types=1);

function assertDiagnosticAttachments(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$migration = file_get_contents(__DIR__ . '/../database/migrations/080_diagnostic_attachments_up.sql');
assertDiagnosticAttachments(
    is_string($migration) && str_contains($migration, 'CREATE TABLE IF NOT EXISTS diagnostic_attachments'),
    'Diagnostic attachment migration is missing.'
);

foreach ([
    'Laboratory result form' => __DIR__ . '/../modules/laboratory/result.php',
    'Radiology report form' => __DIR__ . '/../modules/radiology/report.php',
    'ECG report form' => __DIR__ . '/../modules/ecg/report.php',
] as $label => $file) {
    $contents = file_get_contents($file);
    assertDiagnosticAttachments(
        is_string($contents)
            && str_contains($contents, 'enctype="multipart/form-data"')
            && str_contains($contents, 'name="diagnostic_attachments[]"')
            && str_contains($contents, 'multiple'),
        $label . ' should support multiple diagnostic attachments.'
    );
}

foreach ([
    'Laboratory view' => __DIR__ . '/../modules/laboratory/view.php',
    'Radiology view' => __DIR__ . '/../modules/radiology/view.php',
    'ECG view' => __DIR__ . '/../modules/ecg/view.php',
] as $label => $file) {
    $contents = file_get_contents($file);
    assertDiagnosticAttachments(
        is_string($contents)
            && str_contains($contents, 'attachment_ids[]')
            && str_contains($contents, '../diagnostic_attachments/download.php?id='),
        $label . ' should allow selecting uploaded attachments for WhatsApp.'
    );
}

$handoff = file_get_contents(__DIR__ . '/../modules/patient_communications/whatsapp_handoff.php');
assertDiagnosticAttachments(
    is_string($handoff)
        && str_contains($handoff, "'laboratory_result'")
        && str_contains($handoff, "'radiology_report'")
        && str_contains($handoff, "'ecg_report'")
        && str_contains($handoff, 'selectedForMessage')
        && str_contains($handoff, 'Files selected for attachment'),
    'WhatsApp handoff should support selected diagnostic attachments.'
);

fwrite(STDOUT, 'PASS: Diagnostic attachment static wiring passed.' . PHP_EOL);
