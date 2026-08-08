<?php

declare(strict_types=1);

require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/PatientService.php';
require_once __DIR__ . '/VisitService.php';
require_once __DIR__ . '/ProblemListService.php';
require_once __DIR__ . '/ClinicalNoteService.php';

class MedicalRecordService
{
    private PDO $pdo;
    private PatientService $patientService;
    private VisitService $visitService;
    private AuditService $auditService;
    private ProblemListService $problemListService;
    private ClinicalNoteService $clinicalNoteService;

    public function __construct(
        PDO $pdo,
        ?PatientService $patientService = null,
        ?VisitService $visitService = null,
        ?AuditService $auditService = null,
        ?ProblemListService $problemListService = null,
        ?ClinicalNoteService $clinicalNoteService = null
    ) {
        $this->pdo = $pdo;
        $this->auditService = $auditService ?? new AuditService($pdo);
        $this->patientService = $patientService
            ?? new PatientService($pdo, $this->auditService);
        $this->visitService = $visitService ?? new VisitService($pdo);
        $this->problemListService = $problemListService
            ?? new ProblemListService($pdo);
        $this->clinicalNoteService = $clinicalNoteService
            ?? new ClinicalNoteService($pdo);
    }

    public function getProblemListSummary(int $patientId, array $user): array
    {
        return $this->problemListService->getProblemSummary($patientId, $user);
    }

    public function getStructuredMedicalHistory(int $patientId, array $user): array
    {
        return $this->problemListService->getMedicalHistorySummary(
            $patientId,
            $user
        );
    }

    public function getClinicalNoteSummary(int $patientId, array $user, int $limit = 8): array
    {
        return $this->clinicalNoteService->getNoteSummary($patientId, $user, $limit);
    }

    /*
    |--------------------------------------------------------------------------
    | Longitudinal Patient Chart
    |--------------------------------------------------------------------------
    */

    public function getPatientChart(
        int $patientId,
        array $user,
        bool $logAccess = true
    ): array {
        if ($patientId <= 0 || (int)($user['id'] ?? 0) <= 0) {
            return [
                'success' => false,
                'data' => null,
                'errors' => ['Patient and authenticated user are required.']
            ];
        }

        $ownsTransaction = !$this->pdo->inTransaction();

        try {
            if ($ownsTransaction) {
                $this->pdo->beginTransaction();
            }

            $patient = $this->patientService->getPatientById($patientId);

            if (!$patient) {
                if ($ownsTransaction) {
                    $this->pdo->rollBack();
                }

                return [
                    'success' => false,
                    'data' => null,
                    'errors' => ['Patient not found.']
                ];
            }

            $encounters = $this->getEncounterHistory($patientId);
            $demographicHistory = $this->getDemographicHistory($patientId);
            $summary = $this->getChartSummary($patientId);

            foreach ([$encounters, $demographicHistory, $summary] as $result) {
                if (!($result['success'] ?? false)) {
                    throw new RuntimeException('Unable to assemble patient chart.');
                }
            }

            if ($logAccess && !$this->auditService->logPatientAccess(
                (int)$user['id'],
                $patientId,
                null,
                $this->departmentId($user),
                'VIEW',
                'PatientChart',
                $patientId,
                'Longitudinal patient chart access.'
            )) {
                throw new RuntimeException('Unable to record chart access.');
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return [
                'success' => true,
                'data' => [
                    'patient' => $patient,
                    'summary' => $summary['data'],
                    'encounters' => $encounters['data'],
                    'demographic_history' => $demographicHistory['data'],
                    'metadata' => [
                        'patient_id' => $patientId,
                        'demographic_version' => (int)$patient['demographic_version'],
                        'loaded_at' => date('Y-m-d H:i:s')
                    ]
                ],
                'errors' => []
            ];
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if (!$ownsTransaction) {
                throw $exception;
            }

            return [
                'success' => false,
                'data' => null,
                'errors' => ['Unable to load the patient chart.']
            ];
        }
    }

    public function getChartSummary(int $patientId): array
    {
        if ($patientId <= 0) {
            return [
                'success' => false,
                'data' => [],
                'errors' => ['Patient is required.']
            ];
        }

        $stmt = $this->pdo->prepare('
            SELECT
                (SELECT COUNT(*) FROM visits WHERE patient_id = :patient_id_visits)
                    AS encounter_count,
                (SELECT COUNT(*) FROM visits
                    WHERE patient_id = :patient_id_active
                      AND visit_status NOT IN (\'Completed\', \'Cancelled\'))
                    AS active_encounter_count,
                (SELECT COUNT(*) FROM patient_demographic_history
                    WHERE patient_id = :patient_id_history)
                    AS demographic_change_count,
                (SELECT MAX(created_at) FROM record_access_logs
                    WHERE patient_id = :patient_id_access)
                    AS last_chart_access
        ');
        $stmt->execute([
            ':patient_id_visits' => $patientId,
            ':patient_id_active' => $patientId,
            ':patient_id_history' => $patientId,
            ':patient_id_access' => $patientId
        ]);

        return [
            'success' => true,
            'data' => $stmt->fetch(PDO::FETCH_ASSOC) ?: [],
            'errors' => []
        ];
    }

    public function getEncounterHistory(int $patientId): array
    {
        if ($patientId <= 0) {
            return [
                'success' => false,
                'data' => [],
                'errors' => ['Patient is required.']
            ];
        }

        return [
            'success' => true,
            'data' => $this->visitService->getPatientVisits($patientId),
            'errors' => []
        ];
    }

    public function getPatientAuditHistory(
        int $patientId,
        int $page = 1,
        int $perPage = 25
    ): array {
        return $this->auditService->getPatientHistory(
            $patientId,
            $page,
            $perPage
        );
    }

    public function getDemographicHistory(int $patientId): array
    {
        if ($patientId <= 0) {
            return [
                'success' => false,
                'data' => [],
                'errors' => ['Patient is required.']
            ];
        }

        $stmt = $this->pdo->prepare('
            SELECT h.id,
                   h.patient_id,
                   h.amendment_id,
                   h.version_no,
                   h.previous_values,
                   h.new_values,
                   h.changed_fields,
                   h.reason,
                   h.changed_by,
                   h.created_at,
                   CONCAT(u.first_name, \' \', u.last_name) AS changed_by_name
            FROM patient_demographic_history h
            INNER JOIN users u ON u.id = h.changed_by
            WHERE h.patient_id = :patient_id
            ORDER BY h.version_no DESC, h.id DESC
        ');
        $stmt->execute([':patient_id' => $patientId]);

        $history = array_map(
            static function (array $row): array {
                foreach (['previous_values', 'new_values', 'changed_fields'] as $field) {
                    $decoded = json_decode((string)$row[$field], true);
                    $row[$field] = is_array($decoded) ? $decoded : [];
                }

                return $row;
            },
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return [
            'success' => true,
            'data' => $history,
            'errors' => []
        ];
    }

    private function departmentId(array $user): ?int
    {
        $departmentId = (int)(
            $user['active_department_id']
            ?? $_SESSION['active_department_id']
            ?? $user['department_id']
            ?? 0
        );

        return $departmentId > 0 ? $departmentId : null;
    }
}
