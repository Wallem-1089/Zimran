<?php

declare(strict_types=1);

class EncounterEventService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /*
    |--------------------------------------------------------------------------
    | Record Encounter Event
    |--------------------------------------------------------------------------
    */

    public function record(
        int $visitId,
        string $eventType,
        string $eventTitle,
        ?string $eventDescription,
        ?int $departmentId,
        ?int $performedBy
    ): array {
        $errors = [];

        if ($visitId <= 0) {
            $errors[] = 'Encounter is required.';
        }

        if (trim($eventType) === '') {
            $errors[] = 'Event type is required.';
        }

        if (trim($eventTitle) === '') {
            $errors[] = 'Event title is required.';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO encounter_events (
                visit_id,
                event_type,
                event_title,
                event_description,
                department_id,
                performed_by,
                event_time
            ) VALUES (
                :visit_id,
                :event_type,
                :event_title,
                :event_description,
                :department_id,
                :performed_by,
                NOW()
            )
        ");

        $stmt->execute([
            ':visit_id' => $visitId,
            ':event_type' => trim($eventType),
            ':event_title' => trim($eventTitle),
            ':event_description' => $eventDescription,
            ':department_id' => $departmentId,
            ':performed_by' => $performedBy
        ]);

        return [
            'success' => true,
            'event_id' => (int)$this->pdo->lastInsertId(),
            'errors' => []
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Encounter Timeline Events
    |--------------------------------------------------------------------------
    */

    public function getTimelineEvents(int $visitId): array
    {
        if ($visitId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare("\n
            SELECT\n
                ee.event_type,\n
                ee.event_title,\n
                ee.event_description,\n
                d.department_name,\n
                CONCAT(\n
                    u.first_name,\n
                    ' ',\n
                    u.last_name\n
                ) AS performed_by_name,\n
                ee.event_time,\n
                ee.created_at\n
            FROM encounter_events ee\n
            LEFT JOIN departments d\n
                ON d.id = ee.department_id\n
            LEFT JOIN users u\n
                ON u.id = ee.performed_by\n
            WHERE ee.visit_id = :visit_id\n
            ORDER BY ee.event_time, ee.id\n
        ");

        $stmt->execute([
            ':visit_id' => $visitId
        ]);

        $events = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $event) {
            $events[] = [
                'type' => $this->timelineType(
                    (string)$event['event_type']
                ),
                'title' => $event['event_title'],
                'description' => $event['event_description'],
                'department' => $event['department_name'],
                'performed_by' => $event['performed_by_name'],
                'transfer_type' => null,
                'remarks' => null,
                'created_at' => $event['event_time']
                    ?? $event['created_at']
            ];
        }

        return $events;
    }

    private function timelineType(string $eventType): string
    {
        return match ($eventType) {
            'ENCOUNTER_CREATED' => 'creation',
            'TRANSFERRED' => 'transfer',
            'PATIENT_RECEIVED' => 'transfer',
            'DOCTOR_ASSIGNED' => 'consultation',
            'NURSING_ASSESSMENT_STARTED',
            'NURSING_ASSESSMENT_COMPLETED' => 'nursing',
            'LABORATORY_REQUESTED',
            'LABORATORY_REQUEST_STARTED',
            'LABORATORY_COMPLETED' => 'laboratory',
            'RADIOLOGY_REQUESTED',
            'RADIOLOGY_REQUEST_STARTED',
            'RADIOLOGY_COMPLETED' => 'radiology',
            default => 'default'
        };
    }
}
