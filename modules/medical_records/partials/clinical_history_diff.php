<?php

declare(strict_types=1);

$historyFields = $historyFields ?? [];
$historyMasked = !empty($historyEntry['confidential_hidden']);
$decodeHistorySnapshot = static function (mixed $value): array {
    if (!is_string($value) || trim($value) === '') {
        return [];
    }
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
};
$previousSnapshot = $historyMasked
    ? []
    : $decodeHistorySnapshot($historyEntry['previous_snapshot'] ?? null);
$newSnapshot = $historyMasked
    ? []
    : $decodeHistorySnapshot($historyEntry['new_snapshot'] ?? null);
$changes = [];

foreach ($historyFields as $field => $label) {
    $before = $previousSnapshot[$field] ?? null;
    $after = $newSnapshot[$field] ?? null;
    if ($before !== $after) {
        $changes[] = [
            'label' => $label,
            'before' => $before,
            'after' => $after
        ];
    }
}
?>
<div class="history-entry">
    <strong><?= e((string)$historyEntry['action']) ?></strong>
    · Version <?= (int)$historyEntry['version_no'] ?>
    · <?= e((string)($historyEntry['actor_name'] ?? 'Unknown user')) ?>
    · <?= e((string)($historyEntry['created_at'] ?? '')) ?>
    <p><?= e((string)($historyEntry['reason'] ?? 'No reason recorded.')) ?></p>
    <?php if ($historyMasked): ?>
        <p><em>Confidential historical details are hidden.</em></p>
    <?php elseif ($changes === []): ?>
        <p><em>No comparable field differences are available for this legacy snapshot.</em></p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Field</th><th>Previous</th><th>New</th></tr></thead>
                <tbody>
                <?php foreach ($changes as $change): ?>
                    <tr>
                        <td><?= e((string)$change['label']) ?></td>
                        <td><?= e($change['before'] === null ? '—' : (string)$change['before']) ?></td>
                        <td><?= e($change['after'] === null ? '—' : (string)$change['after']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
