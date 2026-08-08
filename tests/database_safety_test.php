<?php

declare(strict_types=1);

require_once __DIR__ . '/../database/tools/DatabaseSafety.php';

$failures = [];
$expectFailure = static function (callable $operation, string $label) use (&$failures): void {
    try {
        $operation();
        $failures[] = $label . ' was not rejected.';
    } catch (RuntimeException $exception) {
    }
};

$originalEnvironment = getenv('HMS_APP_ENV');
$originalTestDatabase = getenv('HMS_TEST_DB_NAME');
$config = ['database' => ['name' => 'hospital_management_system']];

putenv('HMS_APP_ENV=testing');
putenv('HMS_TEST_DB_NAME=hospital_management_system');
$expectFailure(fn () => DatabaseSafety::resolveTestDatabase($config), 'Live/test equality');

putenv('HMS_TEST_DB_NAME=');
$expectFailure(fn () => DatabaseSafety::resolveTestDatabase($config), 'Empty test database');

putenv('HMS_TEST_DB_NAME=hms_test_safety');
putenv('HMS_APP_ENV=production');
$expectFailure(fn () => DatabaseSafety::resolveTestDatabase($config), 'Production environment');

$expectFailure(
    fn () => DatabaseSafety::assertSafeSchema(
        'USE hospital_management_system; SELECT 1;',
        'hospital_management_system'
    ),
    'Hardcoded live USE statement'
);
$expectFailure(
    fn () => DatabaseSafety::assertSafeSchema(
        'DROP DATABASE hms_test_safety;',
        'hospital_management_system'
    ),
    'Schema DROP DATABASE statement'
);
$expectFailure(
    fn () => DatabaseSafety::assertSafeSchema(
        'CREATE DATABASE hms_test_safety;',
        'hospital_management_system'
    ),
    'Schema CREATE DATABASE statement'
);
$expectFailure(
    fn () => DatabaseSafety::requireDestructiveApproval(['script.php']),
    'Missing destructive confirmation'
);

$originalEnvironment === false
    ? putenv('HMS_APP_ENV')
    : putenv('HMS_APP_ENV=' . $originalEnvironment);
$originalTestDatabase === false
    ? putenv('HMS_TEST_DB_NAME')
    : putenv('HMS_TEST_DB_NAME=' . $originalTestDatabase);

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}
echo 'Database safety refusal tests passed.' . PHP_EOL;
