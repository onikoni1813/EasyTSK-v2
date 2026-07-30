<?php
$user = \App\Models\User::where('role', 'admin')->first();
if ($user) {
    $user->password = \Illuminate\Support\Facades\Hash::make('12345678');
    $user->save();
    echo "Success: Admin password updated to 12345678.\n";
} else {
    echo "Admin user not found.\n";
}
