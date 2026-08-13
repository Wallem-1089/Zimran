<?php

declare(strict_types=1);

class EncounterStateService
{
    private const ACTIVE_STATES = [
        'Waiting',
        'Reception',
        'Records',
        'Nursing',
        'Doctor',
        'Laboratory',
        'X-Ray',
        'Pharmacy',
        'Physiotherapy',
        'Theatre',
        'Accounts',
        'Store'
    ];

    private const TERMINAL_STATES = [
        'Completed',
        'Cancelled'
    ];

    private const TRANSFER_TYPES = [
        'Forward',
        'Return',
        'Referral',
        'Discharge'
    ];

    /*
    |--------------------------------------------------------------------------
    | Validate Encounter Status Transition
    |--------------------------------------------------------------------------
    */

    public function validateStatusTransition(
        ?string $currentStatus,
        string $targetStatus
    ): array {
        $targetStatus = trim($targetStatus);

        if (!$this->isKnownState($targetStatus)) {
            return [
                'success' => false,
                'errors' => ['Invalid encounter status.']
            ];
        }

        if ($currentStatus === null || trim($currentStatus) === '') {
            return [
                'success' => false,
                'errors' => ['Encounter not found.']
            ];
        }

        if (!$this->isKnownState($currentStatus)) {
            return [
                'success' => false,
                'errors' => ['Encounter has an invalid current status.']
            ];
        }

        if ($this->isTerminalState($currentStatus)
            && $currentStatus !== $targetStatus
        ) {
            return [
                'success' => false,
                'errors' => [
                    'Completed or cancelled encounters cannot be modified.'
                ]
            ];
        }

        return [
            'success' => true,
            'errors' => []
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Editable Encounter
    |--------------------------------------------------------------------------
    */

    public function validateEditableEncounter(?array $encounter): array
    {
        if (!$encounter) {
            return [
                'success' => false,
                'errors' => ['Encounter not found.']
            ];
        }

        $status = (string)($encounter['visit_status'] ?? '');

        if ($this->isTerminalState($status)) {
            return [
                'success' => false,
                'errors' => [
                    'Completed or cancelled encounters cannot be modified.'
                ]
            ];
        }

        if (!$this->isKnownState($status)) {
            return [
                'success' => false,
                'errors' => ['Encounter has an invalid current status.']
            ];
        }

        return [
            'success' => true,
            'errors' => []
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Transfer
    |--------------------------------------------------------------------------
    */

    public function validateTransfer(
        ?array $encounter,
        int $departmentId,
        string $transferType
    ): array {
        $editable = $this->validateEditableEncounter($encounter);

        if (!$editable['success']) {
            return $editable;
        }

        if ($departmentId <= 0) {
            return [
                'success' => false,
                'errors' => ['Destination department is required.']
            ];
        }

        if ((int)$encounter['current_department_id'] === $departmentId) {
            return [
                'success' => false,
                'errors' => [
                    'Patient is already in this department.'
                ]
            ];
        }

        if (!in_array($transferType, self::TRANSFER_TYPES, true)) {
            return [
                'success' => false,
                'errors' => ['Invalid transfer type.']
            ];
        }

        return [
            'success' => true,
            'errors' => []
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Receive
    |--------------------------------------------------------------------------
    */

    public function validateReceive(
        ?array $encounter,
        ?array $pendingTransfer
    ): array {
        $editable = $this->validateEditableEncounter($encounter);

        if (!$editable['success']) {
            return $editable;
        }

        if (($encounter['current_department_received_status'] ?? 'Pending')
            === 'Received'
        ) {
            return [
                'success' => false,
                'errors' => [
                    'This department has already received the patient.'
                ]
            ];
        }

        if (!$pendingTransfer) {
            return [
                'success' => false,
                'errors' => [
                    'There is no pending transfer awaiting receipt.'
                ]
            ];
        }

        if ((int)$pendingTransfer['to_department_id']
            !== (int)$encounter['current_department_id']
        ) {
            return [
                'success' => false,
                'errors' => [
                    'Pending transfer does not match the current department.'
                ]
            ];
        }

        return [
            'success' => true,
            'errors' => []
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Doctor Assignment
    |--------------------------------------------------------------------------
    */

    public function validateDoctorAssignment(?array $encounter): array
    {
        $editable = $this->validateEditableEncounter($encounter);

        if (!$editable['success']) {
            return $editable;
        }

        if (($encounter['current_department_received_status'] ?? 'Pending')
            !== 'Received'
        ) {
            return [
                'success' => false,
                'errors' => [
                    'The department must receive the patient first.'
                ]
            ];
        }

        return [
            'success' => true,
            'errors' => []
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | State Helpers
    |--------------------------------------------------------------------------
    */

    private function isKnownState(?string $state): bool
    {
        return is_string($state)
            && (in_array($state, self::ACTIVE_STATES, true)
                || in_array($state, self::TERMINAL_STATES, true));
    }

    private function isTerminalState(?string $state): bool
    {
        return is_string($state)
            && in_array($state, self::TERMINAL_STATES, true);
    }
}
