<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; if(!$permissionService->canViewDuplicateCandidates($currentUser)){http_response_code(403);exit('Access denied.');}
$case=$patientService->getDuplicateCandidate((int)($_GET['id']??0));if(!$case){http_response_code(404);exit('Case not found.');}
$pageTitle='Duplicate Candidate Comparison';$moduleStylesheet='/modules/medical_records/assets/medical_records.css';require __DIR__.'/../../../layouts/header.php';require __DIR__.'/../../../layouts/sidebar.php';
?>
<div class="main-container"><?php require __DIR__.'/../../../layouts/navbar.php';?><main class="content"><div class="card"><h1><?=e($case['classification'])?> — <?=e($case['match_score'])?></h1><div class="chart-detail-grid"><div><strong><?=e($case['hospital_number'])?></strong><p><?=e($case['first_name'].' '.$case['last_name'])?><br><?=e($case['date_of_birth'])?><br><?=e($case['phone']??'-')?></p></div><div><strong><?=e($case['comparison_hospital_number'])?></strong><p><?=e($case['comparison_first_name'].' '.$case['comparison_last_name'])?><br><?=e($case['comparison_date_of_birth'])?><br><?=e($case['comparison_phone']??'-')?></p></div></div>
<?php if($permissionService->canReviewDuplicateCandidates($currentUser)):?><form method="post" action="review.php"><?=csrfField()?><input type="hidden" name="id" value="<?= (int)$case['id'] ?>"><input type="hidden" name="version" value="<?= (int)$case['version'] ?>"><label>Decision</label><select name="decision"><option>Confirmed Duplicate</option><option>Not Duplicate</option><option>Deferred</option><option>Merge Requested</option></select><label>Reason</label><textarea name="reason" required></textarea><button class="btn-primary">Record Review</button></form><?php endif;?></div></main><?php require __DIR__.'/../../../layouts/footer.php';?></div>
