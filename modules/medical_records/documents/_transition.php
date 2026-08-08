<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
requireCsrfToken();
$documentId = (int)($_POST['id'] ?? 0);
$method = match ($documentOperation ?? '') {
    'archive' => 'archiveDocument',
    'restore' => 'restoreDocument',
    default => 'markDocumentEnteredInError'
};
$result = $medicalDocumentService->{$method}($documentId, (string)($_POST['reason'] ?? ''), $currentUser, (int)($_POST['version'] ?? 0));
documentFlash($result, $documentSuccessMessage ?? 'Document status updated.');
header('Location: view.php?id=' . $documentId);
exit;
