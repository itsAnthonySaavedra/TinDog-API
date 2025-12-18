<?php
$roel = \App\Models\User::where('email', 'roel@test.com')->first();
$maria = \App\Models\User::where('email', 'maria@test.com')->first();
if ($roel && $maria) {
    \App\Models\Swipe::firstOrCreate([
        'user_id' => $maria->id,
        'target_user_id' => $roel->id,
        'type' => 'like'
    ]);
    echo "Seeded match potential: Maria likes Roel.\n";
} else {
    echo "Users not found.\n";
}
exit();
