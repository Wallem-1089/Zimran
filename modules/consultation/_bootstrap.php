<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../services/ConsultationService.php';
require_once __DIR__ . '/../../services/LaboratoryService.php';
require_once __DIR__ . '/../../services/RadiologyService.php';
require_once __DIR__ . '/../../services/ECGService.php';
require_once __DIR__ . '/../../services/PhysiotherapyService.php';
require_once __DIR__ . '/../../services/TheatreService.php';
require_once __DIR__ . '/../../services/ClinicalSafetyService.php';
require_once __DIR__ . '/../../services/PharmacyService.php';
require_once __DIR__ . '/../../services/StoreService.php';
require_once __DIR__ . '/../../services/PatientService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/VitalSignsService.php';
require_once __DIR__ . '/../../services/VisitService.php';

function consultationTableExists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table'
        );
        $stmt->execute([':table' => $table]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable) {
        return false;
    }
}

$consultationService = new ConsultationService($pdo);
$patientService = new PatientService($pdo);
$permissionService = new PermissionService($pdo);
$visitService = new VisitService($pdo);
$laboratoryTablesReady = consultationTableExists($pdo, 'laboratory_requests')
    && consultationTableExists($pdo, 'laboratory_results');
$laboratoryService = $laboratoryTablesReady ? new LaboratoryService($pdo, null, null, $permissionService) : null;
$radiologyTablesReady = consultationTableExists($pdo, 'radiology_requests')
    && consultationTableExists($pdo, 'radiology_reports');
$radiologyService = $radiologyTablesReady ? new RadiologyService($pdo, null, null, $permissionService) : null;
$ecgTablesReady = consultationTableExists($pdo, 'ecg_requests')
    && consultationTableExists($pdo, 'ecg_reports');
$ecgService = $ecgTablesReady ? new ECGService($pdo, null, null, $permissionService) : null;
$physiotherapyTablesReady = consultationTableExists($pdo, 'physiotherapy_records')
    && consultationTableExists($pdo, 'physiotherapy_sessions');
$physiotherapyService = $physiotherapyTablesReady ? new PhysiotherapyService($pdo, null, null, $permissionService) : null;
$theatreTablesReady = consultationTableExists($pdo, 'theatre_records');
$theatreService = $theatreTablesReady ? new TheatreService($pdo, null, null, $permissionService) : null;
$vitalSignsTablesReady = consultationTableExists($pdo, 'vital_signs');
$vitalSignsService = $vitalSignsTablesReady ? new VitalSignsService($pdo, null, $permissionService) : null;
$pharmacyTablesReady = consultationTableExists($pdo, 'prescriptions')
    && consultationTableExists($pdo, 'pharmacy_dispensing');
$pharmacyService = $pharmacyTablesReady
    ? new PharmacyService($pdo, new StoreService($pdo, null, $permissionService), new ClinicalSafetyService($pdo), null, null, $permissionService, $visitService)
    : null;

function consultationFlash(array $result, string $success): void
{
    if ($result['success'] ?? false) {
        $_SESSION['success_message'] = $success;
        return;
    }
    $_SESSION['validation_errors'] = $result['errors'] ?? ['The consultation operation failed.'];
}

function consultationBackToWorkspace(int $visitId): string
{
    return '../visits/workspace.php?id=' . $visitId . '&tab=consultation';
}

function consultationRequireVisit(VisitService $service, int $visitId): array
{
    $visit = $service->getVisitById($visitId);
    if (!$visit) {
        http_response_code(404);
        exit('Encounter not found.');
    }
    return $visit;
}

function consultationRequireAccess(PermissionService $permissions, array $visit, array $user): void
{
    if (!$permissions->canViewConsultation($visit, $user)) {
        http_response_code(403);
        exit('Consultation access denied.');
    }
}

function consultationHandwritingPrefix(): string
{
    return '__HMS_HANDWRITING_V1__';
}

function consultationExtractHandwriting(string $value): ?array
{
    $prefix = consultationHandwritingPrefix();
    if (!str_starts_with($value, $prefix)) {
        return null;
    }

    $payload = json_decode(substr($value, strlen($prefix)), true);
    if (!is_array($payload) || !isset($payload['strokes']) || !is_array($payload['strokes'])) {
        return null;
    }

    return $payload;
}

function consultationRenderNarrative(string $value): void
{
    $handwriting = consultationExtractHandwriting($value);
    if ($handwriting === null) {
        echo '<p>' . nl2br(e($value)) . '</p>';
        return;
    }

    $width = max(320, min(1400, (int)($handwriting['width'] ?? 900)));
    $height = max(180, min(900, (int)($handwriting['height'] ?? 280)));
    $paths = [];

    foreach ($handwriting['strokes'] as $stroke) {
        if (!is_array($stroke) || count($stroke) < 1) {
            continue;
        }

        $points = [];
        foreach ($stroke as $point) {
            if (!is_array($point) || count($point) < 2) {
                continue;
            }

            $x = max(0, min($width, (float)$point[0]));
            $y = max(0, min($height, (float)$point[1]));
            $points[] = [round($x, 1), round($y, 1)];
        }

        if ($points === []) {
            continue;
        }

        if (count($points) === 1) {
            $x = $points[0][0];
            $y = $points[0][1];
            $paths[] = 'M ' . $x . ' ' . $y . ' m -1.8 0 a 1.8 1.8 0 1 0 3.6 0 a 1.8 1.8 0 1 0 -3.6 0';
            continue;
        }

        $path = 'M ' . $points[0][0] . ' ' . $points[0][1];
        for ($index = 1, $count = count($points); $index < $count; $index++) {
            $previous = $points[$index - 1];
            $current = $points[$index];
            $middleX = round(($previous[0] + $current[0]) / 2, 1);
            $middleY = round(($previous[1] + $current[1]) / 2, 1);
            $path .= ' Q ' . $previous[0] . ' ' . $previous[1] . ' ' . $middleX . ' ' . $middleY;
        }
        $last = $points[count($points) - 1];
        $path .= ' L ' . $last[0] . ' ' . $last[1];
        $paths[] = $path;
    }

    if ($paths === []) {
        echo '<p class="text-muted">No handwritten content captured.</p>';
        return;
    }

    echo '<div class="consultation-handwriting-view" role="img" aria-label="Handwritten consultation note">';
    echo '<svg viewBox="0 0 ' . $width . ' ' . $height . '" preserveAspectRatio="xMidYMin meet">';
    foreach ($paths as $path) {
        echo '<path d="' . e($path) . '" />';
    }
    echo '</svg>';
    echo '</div>';
}
