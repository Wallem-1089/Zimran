<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../services/PermissionService.php';

$permissionService = new PermissionService($pdo);

function clinicalPermissionUser(string $role, string $department): array
{
    return [
        'id' => 100,
        'role_id' => 0,
        'role_name' => $role,
        'department_id' => 100,
        'department_name' => $department,
        'active_department_id' => 100,
        'active_department_name' => $department,
    ];
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$encounter = [
    'id' => 10,
    'patient_id' => 8,
    'visit_status' => 'Laboratory',
    'current_department_id' => 6,
    'attending_doctor_id' => 5,
];

$clinicalUsers = [
    clinicalPermissionUser('Doctor', 'Doctor'),
    clinicalPermissionUser('Nurse', 'Nursing'),
    clinicalPermissionUser('Laboratory Scientist', 'Laboratory'),
    clinicalPermissionUser('Radiographer', 'X-Ray'),
    clinicalPermissionUser('Physiotherapist', 'Physiotherapy'),
    clinicalPermissionUser('Theatre Staff', 'Theatre'),
    clinicalPermissionUser('Pharmacist', 'Pharmacy'),
    clinicalPermissionUser('Records Officer', 'Records'),
];

foreach ($clinicalUsers as $user) {
    assertTrue($permissionService->canViewMedicalRecord(8, $user), $user['role_name'] . ' should view clinical medical-record context.');
    assertTrue($permissionService->canViewConsultation($encounter, $user), $user['role_name'] . ' should view Consultation.');
    assertTrue($permissionService->canViewVitalSigns(8, $user), $user['role_name'] . ' should view Vital Signs.');
    assertTrue($permissionService->canViewNursing(8, $user), $user['role_name'] . ' should view Nursing.');
    assertTrue($permissionService->canViewLaboratory(8, $user), $user['role_name'] . ' should view Laboratory.');
    assertTrue($permissionService->canViewRadiology(8, $user), $user['role_name'] . ' should view Radiology.');
    assertTrue($permissionService->canViewPhysiotherapy(8, $user), $user['role_name'] . ' should view Physiotherapy.');
    assertTrue($permissionService->canViewTheatre($encounter, $user), $user['role_name'] . ' should view Theatre.');
    assertTrue($permissionService->canViewPharmacy(8, $user), $user['role_name'] . ' should view Pharmacy.');
}

$doctor = $clinicalUsers[0];
$nurse = $clinicalUsers[1];
assertTrue($permissionService->canCreateVitalSigns($encounter, $doctor), 'Doctor should create Vital Signs on active encounters.');
assertTrue($permissionService->canEditVitalSigns($encounter, $doctor), 'Doctor should edit Vital Signs on active encounters.');
assertTrue($permissionService->canCreateVitalSigns($encounter, $nurse), 'Nurse should create Vital Signs on active encounters.');
assertTrue($permissionService->canEditVitalSigns($encounter, $nurse), 'Nurse should edit Vital Signs on active encounters.');
assertTrue($permissionService->canCreateNursing($encounter, $nurse), 'Nurse should create Nursing on active encounters.');

foreach (array_slice($clinicalUsers, 2) as $user) {
    assertTrue(!$permissionService->canEditVitalSigns($encounter, $user), $user['role_name'] . ' should not edit Vital Signs.');
}

assertTrue($permissionService->canViewLaboratoryWorklist(clinicalPermissionUser('Laboratory Scientist', 'Laboratory')), 'Laboratory should view Laboratory worklist.');
assertTrue($permissionService->canViewRadiologyWorklist(clinicalPermissionUser('Radiographer', 'X-Ray')), 'Radiographer should view Radiology worklist.');
assertTrue($permissionService->canViewPhysiotherapyWorklist(clinicalPermissionUser('Physiotherapist', 'Physiotherapy')), 'Physiotherapist should view Physiotherapy worklist.');
assertTrue($permissionService->canViewPharmacyWorklist(clinicalPermissionUser('Pharmacist', 'Pharmacy')), 'Pharmacist should view Pharmacy worklist.');

assertTrue(!$permissionService->canViewLaboratoryWorklist(clinicalPermissionUser('Pharmacist', 'Pharmacy')), 'Pharmacist should not browse Laboratory worklist.');
assertTrue(!$permissionService->canViewRadiologyWorklist(clinicalPermissionUser('Laboratory Scientist', 'Laboratory')), 'Laboratory should not browse Radiology worklist.');
assertTrue(!$permissionService->canViewPharmacyWorklist(clinicalPermissionUser('Radiographer', 'X-Ray')), 'Radiographer should not browse Pharmacy worklist.');
assertTrue(!$permissionService->canViewPhysiotherapyWorklist(clinicalPermissionUser('Nurse', 'Nursing')), 'Nurse should not browse Physiotherapy worklist.');

echo "PASS: Clinical cross-view permission regression passed.\n";
