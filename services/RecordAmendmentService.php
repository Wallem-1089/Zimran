<?php

declare(strict_types=1);

/**
 * Generic approval-ledger operations. Domain services remain responsible for
 * applying approved content and for audit/event creation.
 */
class RecordAmendmentService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createRequest(array $data): array
    {
        $required = ['patient_id', 'record_type', 'record_id', 'proposed_changes', 'reason', 'requested_by'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                return ['success' => false, 'data' => null, 'errors' => ['Complete amendment request data is required.']];
            }
        }
        $owns = !$this->pdo->inTransaction();
        try {
            if ($owns) {
                $this->pdo->beginTransaction();
            }
            $stmt = $this->pdo->prepare('INSERT INTO record_amendments
                (patient_id,visit_id,record_type,record_id,proposed_changes,reason,status,requested_by)
                VALUES (:patient_id,:visit_id,:record_type,:record_id,:proposed_changes,:reason,\'Requested\',:requested_by)');
            $stmt->execute([
                ':patient_id' => (int)$data['patient_id'],
                ':visit_id' => empty($data['visit_id']) ? null : (int)$data['visit_id'],
                ':record_type' => trim((string)$data['record_type']),
                ':record_id' => (int)$data['record_id'],
                ':proposed_changes' => is_string($data['proposed_changes'])
                    ? $data['proposed_changes']
                    : json_encode($data['proposed_changes'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                ':reason' => trim((string)$data['reason']),
                ':requested_by' => (int)$data['requested_by']
            ]);
            $id = (int)$this->pdo->lastInsertId();
            if ($owns) {
                $this->pdo->commit();
            }
            return ['success' => true, 'data' => ['amendment_id' => $id], 'amendment_id' => $id, 'errors' => []];
        } catch (Throwable $exception) {
            if ($owns && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if (!$owns) {
                throw $exception;
            }
            return ['success' => false, 'data' => null, 'errors' => ['Unable to create amendment request.']];
        }
    }

    public function getRequest(int $amendmentId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM record_amendments WHERE id=:id LIMIT 1');
        $stmt->execute([':id' => $amendmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function lockRequest(int $amendmentId): ?array
    {
        if (!$this->pdo->inTransaction()) {
            throw new LogicException('Amendment locking requires a caller-owned transaction.');
        }
        $stmt = $this->pdo->prepare('SELECT * FROM record_amendments WHERE id=:id FOR UPDATE');
        $stmt->execute([':id' => $amendmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function listForRecord(string $recordType, int $recordId): array
    {
        $stmt = $this->pdo->prepare('SELECT a.*, CONCAT(rq.first_name,\' \',rq.last_name) requested_by_name,
                CONCAT(rv.first_name,\' \',rv.last_name) reviewed_by_name
            FROM record_amendments a
            INNER JOIN users rq ON rq.id=a.requested_by
            LEFT JOIN users rv ON rv.id=a.reviewed_by
            WHERE a.record_type=:record_type AND a.record_id=:record_id
            ORDER BY a.requested_at DESC,a.id DESC');
        $stmt->execute([':record_type' => $recordType, ':record_id' => $recordId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function approveLocked(int $amendmentId, int $reviewerId): void
    {
        $this->updateLocked($amendmentId, 'Approved', $reviewerId, false);
    }

    public function rejectLocked(int $amendmentId, int $reviewerId, string $reviewReason): void
    {
        $request = $this->lockRequest($amendmentId);
        if (!$request) {
            throw new RuntimeException('Amendment request not found.');
        }
        $metadata = json_decode((string)$request['proposed_changes'], true);
        $metadata = is_array($metadata) ? $metadata : [];
        $metadata['review_reason'] = trim($reviewReason);
        $stmt = $this->pdo->prepare('UPDATE record_amendments SET proposed_changes=:changes WHERE id=:id');
        $stmt->execute([
            ':changes' => json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ':id' => $amendmentId
        ]);
        $this->updateLocked($amendmentId, 'Rejected', $reviewerId, false);
    }

    public function markAppliedLocked(int $amendmentId, int $reviewerId): void
    {
        $this->updateLocked($amendmentId, 'Applied', $reviewerId, true);
    }

    private function updateLocked(int $id, string $status, int $reviewerId, bool $applied): void
    {
        if (!$this->pdo->inTransaction()) {
            throw new LogicException('Amendment review requires a caller-owned transaction.');
        }
        $stmt = $this->pdo->prepare('UPDATE record_amendments SET status=:status,reviewed_by=:reviewed_by,
            reviewed_at=COALESCE(reviewed_at,NOW()),applied_at=CASE WHEN :applied=1 THEN NOW() ELSE applied_at END
            WHERE id=:id');
        $stmt->execute([':status' => $status, ':reviewed_by' => $reviewerId, ':applied' => $applied ? 1 : 0, ':id' => $id]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('Amendment request could not be updated.');
        }
    }
}
