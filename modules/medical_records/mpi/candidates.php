<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
if(!$permissionService->canViewDuplicateCandidates($currentUser)){http_response_code(403);exit('Access denied.');}
$status=(string)($_GET['status']??'Pending'); $result=$patientService->getDuplicateCandidates($status,max(1,(int)($_GET['page']??1)),25);
$pageTitle='Possible Duplicate Patients';$moduleStylesheet='/modules/medical_records/assets/medical_records.css';require __DIR__.'/../../../layouts/header.php';require __DIR__.'/../../../layouts/sidebar.php';
?>
<div class="main-container"><?php require __DIR__.'/../../../layouts/navbar.php';?><main class="content"><div class="page-header"><h1>Possible Duplicate Patients</h1><a href="index.php">MPI Search</a></div><div class="card"><?php if($result['data']===[]):?><p>No duplicate cases match this status.</p><?php endif;?><?php foreach($result['data'] as $case):?><div class="history-entry"><strong><?=e($case['classification'])?> — Score <?=e($case['match_score'])?></strong><p><?=e($case['low_hospital_number'].' '.$case['low_patient_name'])?> compared with <?=e($case['high_hospital_number'].' '.$case['high_patient_name'])?></p><a href="candidate.php?id=<?= (int)$case['id'] ?>">Compare</a></div><?php endforeach;?></div></main><?php require __DIR__.'/../../../layouts/footer.php';?></div>
