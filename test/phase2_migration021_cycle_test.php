<?php

declare(strict_types=1);

require_once __DIR__ . '/../database/tools/DatabaseSafety.php';
DatabaseSafety::requireCli();
$config=require __DIR__.'/../config/app.php';$resolved=DatabaseSafety::resolveTestDatabase($config);
fwrite(STDOUT,'Resolved live database: '.$resolved['live'].PHP_EOL.'Resolved test database: '.$resolved['test'].PHP_EOL);
DatabaseSafety::requireDestructiveApproval($argv);$database=$config['database'];
$pdo=new PDO('mysql:host='.$database['host'].';dbname='.$resolved['test'].';charset=utf8mb4',$database['user'],$database['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true]);
if((string)$pdo->query('SELECT DATABASE()')->fetchColumn()!==$resolved['test']){throw new RuntimeException('Migration cycle target is not the dedicated test database.');}
foreach(['clinical_note_versions','clinical_notes'] as $table){if((int)$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn()!==0){throw new RuntimeException('Migration 021 cycle requires empty Clinical Note tables.');}}
$upPath=__DIR__.'/../database/migrations/021_phase2_clinical_notes_up.sql';$downPath=__DIR__.'/../database/migrations/021_phase2_clinical_notes_down.sql';$up=(string)file_get_contents($upPath);$down=(string)file_get_contents($downPath);
DatabaseSafety::assertSafeSchema($up,$resolved['live']);DatabaseSafety::assertSafeSchema($down,$resolved['live']);
DatabaseSafety::logOperation($resolved['test'],'Explicit Migration 021 down/up verification on empty dedicated test tables',getenv('HMS_VERIFIED_BACKUP_PATH')!==false?'verified-backup':'explicit-test-acknowledgement');
$pdo->exec($down);
foreach(['clinical_note_versions','clinical_notes'] as $table){$s=$pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=:s AND table_name=:t');$s->execute([':s'=>$resolved['test'],':t'=>$table]);if((int)$s->fetchColumn()!==0){throw new RuntimeException('Down migration did not remove '.$table);}}
$pdo->exec($up);
foreach(['clinical_note_versions','clinical_notes'] as $table){$s=$pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=:s AND table_name=:t');$s->execute([':s'=>$resolved['test'],':t'=>$table]);if((int)$s->fetchColumn()!==1){throw new RuntimeException('Up migration did not restore '.$table);}}
$ledger=$pdo->prepare("SELECT checksum FROM schema_migrations WHERE migration_name='021_phase2_clinical_notes_up.sql'");$ledger->execute();if(!hash_equals((string)$ledger->fetchColumn(),(string)hash_file('sha256',$upPath))){throw new RuntimeException('Migration 021 checksum does not match ledger.');}
fwrite(STDOUT,"Migration 021 isolated down/up verification passed.\n");
