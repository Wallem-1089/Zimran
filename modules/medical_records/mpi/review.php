<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php'; requireCsrfToken(); if(!$permissionService->canReviewDuplicateCandidates($currentUser)){http_response_code(403);exit('Access denied.');}
$result=$patientService->reviewDuplicateCandidate((int)($_POST['id']??0),(string)($_POST['decision']??''),(string)($_POST['reason']??''),(int)$currentUser['id'],(int)($_POST['version']??0)); $_SESSION[$result['success']?'success_message':'validation_errors']=$result['success']?'Duplicate case reviewed.':$result['errors']; header('Location: candidate.php?id='.(int)($_POST['id']??0));exit;
