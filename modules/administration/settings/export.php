<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$group = trim((string)($_GET['group'] ?? ''));
if (!$settingsService->recordExport($settingsActorId, $group === '' ? null : $group)) {
    http_response_code(500);
    exit('Unable to audit settings export.');
}
$export = [
    'exported_at' => date(DATE_ATOM),
    'category' => $group === '' ? null : $group,
    'settings' => $settingsService->exportSettings($group === '' ? null : $group)
];

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="hospital-settings-' . date('Ymd-His') . '.json"');
echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
exit;
