<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(18); // Roel
echo "Simulating for User: {$user->id} ({$user->first_name})\n";

// 1. Get Swiped IDs
$swipedUserIds = App\Models\Swipe::where('user_id', $user->id)->pluck('target_user_id')->toArray();
echo "Swiped IDs: " . implode(',', $swipedUserIds) . "\n";

// 2. Get Matched IDs
$matchedIds1 = App\Models\UserMatch::where('user_id_1', $user->id)->pluck('user_id_2')->toArray();
$matchedIds2 = App\Models\UserMatch::where('user_id_2', $user->id)->pluck('user_id_1')->toArray();
$matchedIds = array_merge($matchedIds1, $matchedIds2);
echo "Matched IDs: " . implode(',', $matchedIds) . "\n";

// 3. Exclude List
$allExcludedIds = array_unique(array_merge($swipedUserIds, $matchedIds, [$user->id]));
echo "All Excluded: " . implode(',', $allExcludedIds) . "\n";

// 4. Query
$query = App\Models\User::whereNotIn('id', $allExcludedIds)
    ->where('role', 'user');

$results = $query->get();
echo "Found Candidates:\n";
foreach ($results as $c) {
    echo "- ID: {$c->id} | Name: {$c->first_name}\n";
}
