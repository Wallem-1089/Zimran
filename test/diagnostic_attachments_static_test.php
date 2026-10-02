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
            && str_contains($contents, 'hmsRenderDiagnosticAttachmentPicker('),
        $label . ' should render the shared multiple diagnostic attachment picker.'
    );
}

$ecgForm = (string)file_get_contents(__DIR__ . '/../modules/ecg/report.php');
$radiologyForm = (string)file_get_contents(__DIR__ . '/../modules/radiology/report.php');
assertDiagnosticAttachments(
    !str_contains($ecgForm, 'name="ecg_chart"')
        && !str_contains($radiologyForm, 'name="radiology_chart"'),
    'ECG and Radiology should rely on the shared attachment picker, not legacy single-file chart fields.'
);

foreach ([
    'ECG save' => __DIR__ . '/../modules/ecg/report_save.php',
    'ECG update' => __DIR__ . '/../modules/ecg/report_update.php',
    'Radiology save' => __DIR__ . '/../modules/radiology/report_save.php',
    'Radiology update' => __DIR__ . '/../modules/radiology/report_update.php',
] as $label => $file) {
    $contents = (string)file_get_contents($file);
    assertDiagnosticAttachments(
        !str_contains($contents, '$_FILES[\'ecg_chart\']')
            && !str_contains($contents, '$_FILES[\'radiology_chart\']'),
        $label . ' should not read legacy single-file chart uploads.'
    );
}

$helpers = file_get_contents(__DIR__ . '/../config/helpers.php');
assertDiagnosticAttachments(
    is_string($helpers)
        && str_contains($helpers, 'function hmsRenderDiagnosticAttachmentPicker(')
        && str_contains($helpers, 'name="diagnostic_attachments[]"')
        && str_contains($helpers, 'multiple')
        && str_contains($helpers, 'data-add-diagnostic-attachment')
        && str_contains($helpers, 'border-radius:999px;width:2.75rem'),
    'Shared diagnostic attachment picker should support multiple files with a floating plus button.'
);

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

require_once __DIR__ . '/../services/DiagnosticAttachmentService.php';
$reflection = new ReflectionClass(DiagnosticAttachmentService::class);
$service = $reflection->newInstanceWithoutConstructor();
$normalize = $reflection->getMethod('normalizeFiles');
$normalize->setAccessible(true);
$normalized = $normalize->invoke($service, [
    'name' => [
        ['ecg-1.png', 'ecg-2.png'],
        ['ecg-3.png'],
    ],
    'type' => [
        ['image/png', 'image/png'],
        ['image/png'],
    ],
    'tmp_name' => [
        ['tmp1', 'tmp2'],
        ['tmp3'],
    ],
    'error' => [
        [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
        [UPLOAD_ERR_OK],
    ],
    'size' => [
        [10, 20],
        [30],
    ],
]);
assertDiagnosticAttachments(
    is_array($normalized)
        && count($normalized) === 3
        && ($normalized[2]['name'] ?? '') === 'ecg-3.png'
        && ($normalized[2]['tmp_name'] ?? '') === 'tmp3',
    'Diagnostic attachment upload normalization should flatten nested multi-input uploads.'
);

fwrite(STDOUT, 'PASS: Diagnostic attachment static wiring passed.' . PHP_EOL);
