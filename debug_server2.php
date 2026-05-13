<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$server2 = \App\Models\ReplyServer::find(2);
if (!$server2) {
    echo "Server #2 not found\n";
    exit;
}

echo "=== SERVER #2 DETAILS ===\n";
echo "Name: " . $server2->name . "\n";
echo "Protocol: " . $server2->protocol . "\n";
echo "Hostname: " . $server2->hostname . "\n";
echo "Port: " . $server2->port . "\n";
echo "Encryption: " . $server2->encryption . "\n";
echo "Username: " . $server2->username . "\n";
echo "Active: " . ($server2->active ? 'YES' : 'NO') . "\n";
echo "Validate SSL: " . ($server2->validate_ssl ? 'YES' : 'NO') . "\n";

echo "\n=== BUILD CONNECTION STRING ===\n";
$hostname = $server2->hostname;
$port = $server2->port;
$encryption = $server2->encryption;
$mailbox = $server2->mailbox;
$validateSsl = $server2->validate_ssl;
$protocol = strtolower((string) $server2->protocol);
if (!in_array($protocol, ['imap', 'pop3'], true)) {
    $protocol = 'imap';
}

$connectionString = "{{$hostname}:{$port}";

if ($protocol === 'pop3') {
    $connectionString .= '/pop3';
} else {
    $connectionString .= '/imap';
}

$useSsl = false;
if ($encryption === 'ssl') {
    $useSsl = true;
} elseif ($encryption === 'tls') {
    $useSsl = false;
} elseif ($encryption === 'none' || empty($encryption)) {
    $useSsl = in_array($port, [993, 465, 995]);
}

if ($useSsl) {
    $connectionString .= '/ssl';
} elseif ($encryption === 'tls') {
    $connectionString .= '/tls';
}

if (!$validateSsl && ($useSsl || $encryption === 'tls')) {
    $connectionString .= '/novalidate-cert';
}

$connectionString .= '}' . $mailbox;

echo "Connection string: " . $connectionString . "\n";

echo "\n=== TEST CONNECTION ===\n";
$connection = @imap_open($connectionString, $server2->username, $server2->password, NULL, 1);
if ($connection) {
    echo "✅ Manual connection SUCCESS\n";
    imap_close($connection);
} else {
    $lastError = (string) (imap_last_error() ?: '');
    $errors = imap_errors();
    $alerts = imap_alerts();
    $errorsText = is_array($errors) && !empty($errors) ? implode(' | ', array_map('strval', $errors)) : '';
    $alertsText = is_array($alerts) && !empty($alerts) ? implode(' | ', array_map('strval', $alerts)) : '';

    echo "❌ Manual connection FAILED\n";
    echo "Last error: " . ($lastError !== '' ? $lastError : '(empty)') . "\n";
    if ($errorsText !== '') {
        echo "Errors: " . $errorsText . "\n";
    }
    if ($alertsText !== '') {
        echo "Alerts: " . $alertsText . "\n";
    }
}

echo "\n=== TEST PROCESSOR METHOD ===\n";
try {
    $processor = new \App\Services\ReplyProcessorService();
    $result = $processor->processReplies($server2);
    echo "✅ Server #2 SUCCESS: Processed {$result} replies\n";
} catch (Exception $e) {
    echo "❌ Server #2 FAILED: " . $e->getMessage() . "\n";
}

echo "\n=== QUICK FIX OPTIONS ===\n";
echo "1. Deactivate server #2:\n";
echo "   php artisan tinker --execute=\"\\App\\Models\\ReplyServer::find(2)->update(['active' => false]);\"\n\n";

echo "2. Update server #2 with working credentials:\n";
echo "   php artisan tinker --execute=\"\\App\\Models\\ReplyServer::find(2)->update(['username' => '<USERNAME>', 'password' => '<PASSWORD>', 'hostname' => '<HOSTNAME>', 'port' => 993, 'encryption' => 'ssl', 'validate_ssl' => false]);\"\n\n";
