<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n|------------------------------------------|\n";
echo "|      DATABASE CONNECTION DIAGNOSTICS     |\n";
echo "|------------------------------------------|\n";

$host1 = 'aws-1-ap-south-1.pooler.supabase.com'; // From previous output
$ports = [6543, 5432];

echo "\n[NETWORK CONNECTIVITY CHECK]\n";
foreach ($ports as $port) {
    echo "Testing connection to $host1:$port... ";
    $start = microtime(true);
    $fp = @fsockopen($host1, $port, $errno, $errstr, 5); // 5 sec timeout
    $time = (microtime(true) - $start) * 1000;
    
    if ($fp) {
        echo "SUCCESS (" . number_format($time, 2) . " ms)\n";
        fclose($fp);
    } else {
        echo "FAILED ($errstr - $errno)\n";
    }
}

$startTime = microtime(true);

// User provided credentials
$creds = [
    'driver' => 'pgsql',
    'host' => 'aws-1-ap-south-1.pooler.supabase.com',
    'database' => 'postgres',
    'username' => 'postgres.dyhfryvhmeccqrhzupdc',
    'password' => '!*$5syz2C6',
];

$configs = [
    [
        'name' => 'Proposed Fix: Port 5432 + SSL Prefer + Emulate Prepares',
        'config' => array_merge($creds, [
            'port' => '5432', // Session pooler is often more stable for direct queries
            'sslmode' => 'prefer',
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => true, 
                PDO::ATTR_TIMEOUT => 5
            ]
        ])
    ],
    [
        'name' => 'Original Port 6543 + SSL Prefer + Emulate Prepares',
        'config' => array_merge($creds, [
            'port' => '6543',
            'sslmode' => 'prefer',
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => true, 
                PDO::ATTR_TIMEOUT => 5
            ]
        ])
    ]
];

foreach ($configs as $test) {
    echo "\n\n[TESTING CONFIG: {$test['name']}]\n";
    
    try {
        // Reset connection
        DB::purge('pgsql');
        
        // Overwrite config completely for test
        config(['database.connections.pgsql' => $test['config']]);
        
        // Connect
        $connection = DB::connection('pgsql');
        $pdo = $connection->getPdo(); 
        echo "Connected! Driver: " . $connection->getConfig('driver') . "\n";
        
        // Query
        echo "Executing Query... ";
        $qStart = microtime(true);
        $user = DB::table('users')->first();
        $qTime = microtime(true) - $qStart;
        
        echo "SUCCESS (" . number_format($qTime * 1000, 2) . " ms)\n";
        echo "Solution Found! Applying this config will fix the issue.\n";
        
        if ($user) {
             echo "Sample User: " . $user->first_name . "\n";
        }
        
        break; // Stop on first success
        
    } catch (\Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
}
