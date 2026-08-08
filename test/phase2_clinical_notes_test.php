<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/test_database.php';
require_once __DIR__ . '/../services/ClinicalNoteService.php';
require_once __DIR__ . '/../database/tools/DatabaseSafety.php';

function assertClinicalNote(bool $condition, string $message): void
{
    if (!$condition) { throw new RuntimeException($message); }
}

function requireClinicalNoteSuccess(array $result, string $operation): array
{
    assertClinicalNote(($result['success'] ?? false) === true, $operation . ': ' . implode(' ', $result['errors'] ?? []));
    return $result;
}

$config = require __DIR__ . '/../config/app.php';
$resolved = DatabaseSafety::resolveTestDatabase($config);
$databaseName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
assertClinicalNote($databaseName === $resolved['test'] && $databaseName !== $resolved['live'], 'Clinical Note tests are not isolated from live.');
fwrite(STDOUT, 'Resolved live database: ' . $resolved['live'] . PHP_EOL . 'Resolved test database: ' . $resolved['test'] . PHP_EOL);

$users = [];
$rows = $pdo->query("SELECT u.*,r.role_name,d.department_name FROM users u INNER JOIN roles r ON r.id=u.role_id INNER JOIN departments d ON d.id=u.department_id WHERE u.username IN ('admin','dev_doctor','dev_records','dev_nurse')")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) { $users[$row['username']] = $row; }
foreach (['admin','dev_doctor','dev_records','dev_nurse'] as $username) { assertClinicalNote(isset($users[$username]), 'Missing fixture ' . $username . '.'); }
$admin=$users['admin'];$doctor=$users['dev_doctor'];$records=$users['dev_records'];$nurse=$users['dev_nurse'];
$patientIds=array_map('intval',$pdo->query("SELECT id FROM patients WHERE hospital_number IN ('DEV-PATIENT-0001','DEV-PATIENT-0002') ORDER BY hospital_number")->fetchAll(PDO::FETCH_COLUMN));
assertClinicalNote(count($patientIds)===2,'Dedicated patients are missing.');[$patientId,$otherPatientId]=$patientIds;
$suffix=date('YmdHis').random_int(1000,9999);
$pdo->prepare("INSERT INTO visits (visit_number,patient_id,visit_date,visit_type,current_department_id,attending_doctor_id,current_department_received_status,visit_status,created_by) VALUES (:number,:patient,NOW(),'Outpatient',:department,:doctor,'Received','Doctor',:creator)")->execute([':number'=>'TEST-NOTE-'.$suffix,':patient'=>$patientId,':department'=>$doctor['department_id'],':doctor'=>$doctor['id'],':creator'=>$admin['id']]);
$visitId=(int)$pdo->lastInsertId();$noteIds=[];$service=new ClinicalNoteService($pdo);$settings=new SettingsService($pdo);
$pdo->prepare("INSERT INTO visits (visit_number,patient_id,visit_date,visit_type,current_department_id,current_department_received_status,visit_status,created_by) VALUES (:number,:patient,NOW(),'Outpatient',:department,'Received','Nursing',:creator)")->execute([':number'=>'TEST-NURSE-'.$suffix,':patient'=>$patientId,':department'=>$nurse['department_id'],':creator'=>$admin['id']]);
$nurseVisitId=(int)$pdo->lastInsertId();

try {
    assertClinicalNote((int)$pdo->query("SELECT COUNT(*) FROM schema_migrations WHERE migration_name='021_phase2_clinical_notes_up.sql'")->fetchColumn()===1,'Migration 021 is not ledger-recorded.');
    foreach (['clinical_notes','clinical_note_versions'] as $table) { assertClinicalNote(in_array($table,$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN),true),'Missing table '.$table); }
    $invalidSetting=$settings->update('clinical_notes.enabled_types',['general_clinical_note','consultation_note'],(int)$admin['id']);
    assertClinicalNote(!($invalidSetting['success']??true),'Unsupported future note type was accepted by settings validation.');
    $lockSetting=$settings->update('clinical_notes.auto_lock_on_signing',false,(int)$admin['id']);
    assertClinicalNote(!($lockSetting['success']??true),'System-controlled signing lock setting was edited.');

    $bad=$service->createDraft(['patient_id'=>$patientId,'note_type'=>'general_clinical_note','title'=>'Unsafe','content'=>'<script>alert(1)</script>'],$doctor);
    assertClinicalNote(!($bad['success']??true),'Executable HTML was accepted.');
    $created=requireClinicalNoteSuccess($service->createDraft(['patient_id'=>$patientId,'visit_id'=>$visitId,'note_type'=>'progress_note','title'=>'Clinical note '.$suffix,'content'=>"Initial plain-text entry.\nSecond line.",'confidentiality_level'=>'Standard'],$doctor),'Create encounter draft');
    $noteId=(int)$created['note_id'];$noteIds[]=$noteId;
    assertClinicalNote((int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE patient_id=$patientId AND visit_id=$visitId AND action='CLINICAL_NOTE_CREATED'")->fetchColumn()===1,'Draft creation did not create exactly one audit record.');
    assertClinicalNote($service->getNoteById($noteId)!==null&&!array_key_exists('content',$service->getNoteById($noteId)),'Context-free lookup exposed content.');
    $nurseDraft=$service->getNoteByIdForUser($noteId,$nurse,false);assertClinicalNote(!($nurseDraft['success']??true),'Another author draft was exposed to nurse.');
    $updated=requireClinicalNoteSuccess($service->updateDraft($noteId,['title'=>'Clinical note revised '.$suffix,'content'=>'Revised immutable draft.','note_type'=>'progress_note','confidentiality_level'=>'Standard'],1,$doctor),'Update draft');
    assertClinicalNote((int)$updated['version']===2,'Draft optimistic version did not advance.');
    $stale=$service->updateDraft($noteId,['content'=>'Stale content'],1,$doctor);assertClinicalNote(!($stale['success']??true)&&!empty($stale['conflict']),'Stale draft update was accepted.');
    $adminSign=$service->signNote($noteId,2,$admin,$visitId);assertClinicalNote(!($adminSign['success']??true),'Administrative override signed a clinical note.');
    requireClinicalNoteSuccess($service->signNote($noteId,2,$doctor,$visitId),'Sign draft');
    $signed=$service->getNoteByIdForUser($noteId,$doctor,false);assertClinicalNote($signed['data']['note']['note_status']==='Signed'&&!empty($signed['data']['note']['locked_at']),'Signed note was not locked.');
    assertClinicalNote(!($service->updateDraft($noteId,['content'=>'Overwrite'],3,$doctor)['success']??true),'Signed note was edited as a draft.');
    assertClinicalNote((int)$pdo->query("SELECT COUNT(*) FROM clinical_note_versions WHERE note_id=$noteId")->fetchColumn()===3,'Immutable draft/sign version chain is incomplete.');
    $identity=$service->getNoteById($noteId);assertClinicalNote((int)$identity['patient_id']===$patientId&&(int)$identity['author_id']===(int)$doctor['id']&&(int)$identity['visit_id']===$visitId,'Note identity or authorship changed.');
    $page=requireClinicalNoteSuccess($service->listPatientNotes($patientId,$doctor,['q'=>'Clinical note revised','status'=>'Signed'],1,1),'Paginated title-prefix search');
    assertClinicalNote((int)$page['data']['page_size']===1&&(int)$page['data']['total_results']>=1,'Clinical Note pagination/filter metadata is incorrect.');
    $options=requireClinicalNoteSuccess($service->getNoteFilterOptions($patientId,$doctor),'Load note filter options');
    assertClinicalNote(count($options['data']['authors'])>=1&&count($options['data']['departments'])>=1,'Clinical Note filter options are incomplete.');
    requireClinicalNoteSuccess($service->listEncounterNotes($visitId,$doctor,[],1,25),'List authorized encounter notes');
    assertClinicalNote(!($service->listEncounterNotes($visitId,$nurse,[],1,25)['success']??true),'Encounter Notes list bypassed current encounter department access.');

    $requested=requireClinicalNoteSuccess($service->amendNote($noteId,'Corrected signed content.','Clinical correction.',3,$doctor,$visitId),'Request amendment');
    $amendmentId=(int)$requested['amendment_id'];
    assertClinicalNote(!($service->approveAmendment($amendmentId,$doctor)['success']??true),'Requester self-approved amendment.');
    requireClinicalNoteSuccess($service->approveAmendment($amendmentId,$records),'Approve amendment');
    $amended=$service->getNoteByIdForUser($noteId,$doctor,false);assertClinicalNote($amended['data']['note']['note_status']==='Amended'&&$amended['data']['note']['content']==='Corrected signed content.','Approved amendment was not applied.');
    $history=requireClinicalNoteSuccess($service->getNoteVersions($noteId,$records),'Read note history');
    assertClinicalNote(count($history['data']['versions'])===5,'Expected draft, signed, proposal, and amended versions are missing.');

    $conf=requireClinicalNoteSuccess($service->createDraft(['patient_id'=>$patientId,'note_type'=>'general_clinical_note','title'=>'Restricted note '.$suffix,'content'=>'Protected content.','confidentiality_level'=>'Confidential'],$doctor),'Create confidential draft');
    $confId=(int)$conf['note_id'];$noteIds[]=$confId;
    requireClinicalNoteSuccess($service->signNote($confId,1,$doctor,null),'Sign confidential note');
    $nurseList=requireClinicalNoteSuccess($service->listPatientNotes($patientId,$nurse,['q'=>'Restricted note'],1,25),'List confidential notes');
    $masked=array_values(array_filter($nurseList['data']['records'],static fn(array $r):bool=>(int)$r['id']===$confId))[0]??[];
    assertClinicalNote(!empty($masked['masked'])&&$masked['title']==='Confidential Clinical Note','Confidential list metadata was not masked.');
    assertClinicalNote(!($service->getNoteByIdForUser($confId,$nurse,true)['success']??true),'Confidential note detail was exposed.');
    $confHistory=requireClinicalNoteSuccess($service->getNoteVersions($confId,$nurse),'Read masked confidential history');
    $signedHistory=array_values(array_filter($confHistory['data']['versions'],static fn(array $v):bool=>$v['version_status']==='Signed'))[0]??[];
    assertClinicalNote(!empty($signedHistory['masked'])&&$signedHistory['content']===null,'Confidential historical version was not masked by stored classification.');
    $failingAccess=new class($pdo) extends AuditService { public function logPatientAccess(int $userId,int $patientId,?int $visitId,?int $departmentId,string $accessType,string $resourceType='PatientChart',?int $resourceId=null,?string $accessReason=null):bool{return false;} };
    $accessFailure=(new ClinicalNoteService($pdo,null,null,$failingAccess))->getNoteByIdForUser($confId,$doctor,true);
    assertClinicalNote(!($accessFailure['success']??true)&&!empty($accessFailure['audit_failed']),'Protected note access did not fail closed when PHI logging failed.');

    $cross=$service->createDraft(['patient_id'=>$otherPatientId,'visit_id'=>$visitId,'note_type'=>'general_clinical_note','title'=>'Cross patient','content'=>'Must fail.'],$doctor);
    assertClinicalNote(!($cross['success']??true),'Cross-patient encounter context was accepted.');
    assertClinicalNote((int)$pdo->query("SELECT COUNT(*) FROM encounter_events WHERE visit_id=$visitId AND event_type IN ('CLINICAL_NOTE_SIGNED','CLINICAL_NOTE_AMENDED')")->fetchColumn()===2,'Signed/amended encounter events are incorrect.');
    assertClinicalNote((int)$pdo->query("SELECT COUNT(*) FROM encounter_events WHERE visit_id=$visitId AND event_type='CLINICAL_NOTE_CREATED'")->fetchColumn()===0,'Draft creation polluted the encounter timeline.');

    $failingAudit=new class($pdo) extends AuditService { public function logPatient(?int $userId,int $patientId,?int $visitId,string $module,string $action,string $description,?int $departmentId=null,string $severity='INFO',?string $eventType=null):bool{return false;} };
    $rollbackService=new ClinicalNoteService($pdo,null,null,$failingAudit);
    $rollback=$rollbackService->createDraft(['patient_id'=>$patientId,'note_type'=>'other','title'=>'Rollback '.$suffix,'content'=>'Must roll back.'],$doctor);
    assertClinicalNote(!($rollback['success']??true),'Audit failure did not fail note creation.');
    $check=$pdo->prepare('SELECT COUNT(*) FROM clinical_notes WHERE title=:title');$check->execute([':title'=>'Rollback '.$suffix]);assertClinicalNote((int)$check->fetchColumn()===0,'Audit failure did not roll back note/version rows.');

    $eventDraft=requireClinicalNoteSuccess($service->createDraft(['patient_id'=>$patientId,'visit_id'=>$visitId,'note_type'=>'other','title'=>'Event rollback '.$suffix,'content'=>'Must remain a draft.'],$doctor),'Create event rollback draft');
    $eventNoteId=(int)$eventDraft['note_id'];$noteIds[]=$eventNoteId;
    $failingEvent=new class($pdo) extends EncounterEventService { public function record(int $visitId,string $eventType,string $eventTitle,?string $eventDescription,?int $departmentId,?int $performedBy):array{return ['success'=>false,'errors'=>['Injected event failure.']];} };
    $eventFailure=(new ClinicalNoteService($pdo,null,null,null,$failingEvent))->signNote($eventNoteId,1,$doctor,$visitId);
    assertClinicalNote(!($eventFailure['success']??true),'Encounter-event failure did not fail signing.');
    $eventNote=$service->getNoteById($eventNoteId);assertClinicalNote($eventNote['note_status']==='Draft'&&(int)$eventNote['version']===1,'Encounter-event failure did not roll back signed state.');
    assertClinicalNote((int)$pdo->query("SELECT COUNT(*) FROM clinical_note_versions WHERE note_id=$eventNoteId")->fetchColumn()===1,'Encounter-event failure left an orphan signed version.');

    requireClinicalNoteSuccess($service->markNoteEnteredInError($noteId,'Documented against wrong context.',4,$records,$visitId),'Mark note entered in error');
    assertClinicalNote(!($service->amendNote($noteId,'Invalid','Invalid terminal transition.',5,$doctor,$visitId)['success']??true),'Entered-in-error note accepted amendment.');
    assertClinicalNote(!in_array('patient_merge_history',$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN),true),'Patient merge functionality was introduced.');
    echo "Phase 2.6 Clinical Notes tests passed.\n";
} finally {
    if($noteIds!==[]){$ids=implode(',',array_map('intval',$noteIds));$pdo->exec("DELETE FROM record_amendments WHERE record_type='ClinicalNote' AND record_id IN ($ids)");$pdo->exec("DELETE FROM record_access_logs WHERE resource_type IN ('ClinicalNote','ClinicalNoteHistory') AND resource_id IN ($ids)");$pdo->exec("UPDATE clinical_note_versions SET supersedes_version_id=NULL WHERE note_id IN ($ids)");$pdo->exec("DELETE FROM clinical_note_versions WHERE note_id IN ($ids)");$pdo->exec("DELETE FROM clinical_notes WHERE id IN ($ids)");}
    $pdo->exec("DELETE FROM encounter_events WHERE visit_id=$visitId");
    $pdo->exec("DELETE FROM audit_logs WHERE visit_id=$visitId OR (patient_id=$patientId AND action LIKE 'CLINICAL_NOTE_%') OR (patient_id=$patientId AND action IN ('CONFIDENTIAL_NOTE_VIEWED','CONFIDENTIAL_NOTE_ACCESS_DENIED'))");
    $pdo->exec("DELETE FROM visits WHERE id IN ($visitId,$nurseVisitId)");
}
