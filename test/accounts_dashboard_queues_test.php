<?php

declare(strict_types=1);

function assertAccountsDashboardQueues(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$accountsDashboard = file_get_contents(__DIR__ . '/../modules/accounts/index.php');
assertAccountsDashboardQueues(is_string($accountsDashboard), 'Unable to read Accounts dashboard.');
$mainDashboard = file_get_contents(__DIR__ . '/../dashboard/index.php');
assertAccountsDashboardQueues(is_string($mainDashboard), 'Unable to read main dashboard.');

foreach ([
    'Accounts Billing Queues',
    'Laboratory',
    'Radiology / X-Ray',
    'ECG',
    'Plaster',
    'Physiotherapy',
    'Pharmacy',
    'Theatre',
    'Patient Stock Usage',
    'Admission',
    'Nursing',
    'Dressing',
    'Consultation Fee',
    'Patient Registration',
    'Patient Renewal',
    'Emergency Registration',
    '../billing/billing_requests.php?',
    '../billing/registration_requests.php?',
] as $needle) {
    assertAccountsDashboardQueues(
        str_contains($accountsDashboard, $needle),
        'Accounts dashboard is missing queue entry or link fragment: ' . $needle
    );
}

assertAccountsDashboardQueues(
    str_contains($accountsDashboard, 'accountsPendingClinicalBillingCount')
        && str_contains($accountsDashboard, 'accountsPendingRegistrationBillingCount')
        && str_contains($accountsDashboard, 'accounts-queue-button'),
    'Accounts dashboard should render pending queue counts as clickable summary-style buttons.'
);

$patientRegistrationPosition = strpos($accountsDashboard, 'Patient Registration');
$consultationFeePosition = strpos($accountsDashboard, 'Consultation Fee');
$patientRenewalPosition = strpos($accountsDashboard, 'Patient Renewal');
$emergencyRegistrationPosition = strpos($accountsDashboard, 'Emergency Registration');
assertAccountsDashboardQueues(
    $patientRegistrationPosition !== false
        && $consultationFeePosition !== false
        && $patientRenewalPosition !== false
        && $emergencyRegistrationPosition !== false
        && $patientRegistrationPosition < $consultationFeePosition
        && $consultationFeePosition < $patientRenewalPosition
        && $patientRenewalPosition < $emergencyRegistrationPosition,
    'Accounts queues should start Patient Registration, Consultation Fee, Patient Renewal, Emergency Registration.'
);

foreach ([
    'Accounts Billing Queues',
    'Patient Registration',
    'Consultation Fee',
    'Laboratory',
    'Radiology / X-Ray',
    'ECG',
    'Plaster',
    'Physiotherapy',
    'Pharmacy',
    'Theatre',
    'Patient Stock Usage',
    'Admission',
    'Nursing',
    'Dressing',
    'Patient Renewal',
    'Emergency Registration',
    '../modules/billing/billing_requests.php?',
    '../modules/billing/registration_requests.php?',
] as $needle) {
    assertAccountsDashboardQueues(
        str_contains($mainDashboard, $needle),
        'Main dashboard is missing queue entry or link fragment: ' . $needle
    );
}

$dashboardPatientRegistrationPosition = strpos($mainDashboard, 'Patient Registration');
$dashboardConsultationFeePosition = strpos($mainDashboard, 'Consultation Fee');
$dashboardPatientRenewalPosition = strpos($mainDashboard, 'Patient Renewal');
$dashboardEmergencyRegistrationPosition = strpos($mainDashboard, 'Emergency Registration');
assertAccountsDashboardQueues(
    $dashboardPatientRegistrationPosition !== false
        && $dashboardConsultationFeePosition !== false
        && $dashboardPatientRenewalPosition !== false
        && $dashboardEmergencyRegistrationPosition !== false
        && $dashboardPatientRegistrationPosition < $dashboardConsultationFeePosition,
    'Main dashboard should show Patient Registration before Consultation Fee.'
);
assertAccountsDashboardQueues(
    $dashboardConsultationFeePosition < $dashboardPatientRenewalPosition
        && $dashboardPatientRenewalPosition < $dashboardEmergencyRegistrationPosition,
    'Main dashboard should show Patient Renewal third and Emergency Registration fourth.'
);
assertAccountsDashboardQueues(
    str_contains($mainDashboard, "strcasecmp(\$activeDepartmentName, 'Accounts') === 0")
        && str_contains($mainDashboard, "'Accountant'")
        && str_contains($mainDashboard, '$isAdministrator'),
    'Main dashboard billing queue buttons should be restricted to Accounts and Super Administrator users.'
);

$registrationRequestsPage = file_get_contents(__DIR__ . '/../modules/billing/registration_requests.php');
assertAccountsDashboardQueues(is_string($registrationRequestsPage), 'Unable to read registration requests page.');
foreach (['billing_type', 'registration_type', 'InitialRegistration', 'MonthlyRenewal', 'Emergency'] as $needle) {
    assertAccountsDashboardQueues(
        str_contains($registrationRequestsPage, $needle),
        'Registration requests page is missing filter support for: ' . $needle
    );
}

$registrationBillingService = file_get_contents(__DIR__ . '/../services/PatientRegistrationBillingService.php');
assertAccountsDashboardQueues(is_string($registrationBillingService), 'Unable to read registration billing service.');
assertAccountsDashboardQueues(
    str_contains($registrationBillingService, 'r.billing_type = :billing_type')
        && str_contains($registrationBillingService, 'r.registration_type = :registration_type'),
    'Registration billing service should filter registration queues by billing and registration type.'
);

$billingService = file_get_contents(__DIR__ . '/../services/BillingService.php');
assertAccountsDashboardQueues(is_string($billingService), 'Unable to read billing service.');
assertAccountsDashboardQueues(
    str_contains($billingService, "br.source_module IN ('Plaster', 'POP')"),
    'Billing service should treat legacy POP requests as Plaster billing requests.'
);

fwrite(STDOUT, 'PASS: Accounts dashboard queue buttons are wired to billing request filters.' . PHP_EOL);
