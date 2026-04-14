<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "--- Auth Emergency Fix ---<br>";

$email = 'admin@perfloplast.com';
$password = env('SUPER_ADMIN_PASSWORD', 'perfloplast123');

echo "Checking User...<br>";
$admin = User::where('email', $email)->first();

if (!$admin) {
    echo "User NOT found. Creating...<br>";
    try {
        $admin = User::create([
            'name' => 'Super Administrador',
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        echo "User Created Successfully!<br>";
    } catch (\Exception $e) {
        echo "Error Creating User: " . $e->getMessage() . "<br>";
    }
} else {
    echo "User already exists. Ensuring it is active...<br>";
    $admin->is_active = true;
    $admin->save();
}

if ($admin) {
    echo "Ensuring Roles/Permissions...<br>";
    try {
        // Force create roles if they don't exist
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin->assignRole($role);
        echo "Role 'super_admin' assigned!<br>";
    } catch (\Exception $e) {
        echo "Error assigning role: " . $e->getMessage() . "<br>";
    }
}

echo "<br>Final User Count: " . User::count() . "<br>";
echo "Now try logging in at /admin/login<br>";
