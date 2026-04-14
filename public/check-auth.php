<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\User;

echo "--- Auth Diagnostic ---<br>";
echo "Environment: " . app()->environment() . "<br>";
echo "SUPER_ADMIN_PASSWORD set: " . (env('SUPER_ADMIN_PASSWORD') ? 'YES' : 'NO') . "<br>";
echo "User count: " . User::count() . "<br>";

$admin = User::where('email', 'admin@perfloplast.com')->first();
if ($admin) {
    echo "Admin User Found: YES<br>";
    echo "Admin Active: " . ($admin->is_active ? 'YES' : 'NO') . "<br>";
} else {
    echo "Admin User Found: NO<br>";
}

// Check roles if possible
if ($admin && method_exists($admin, 'getRoleNames')) {
    echo "Roles: " . $admin->getRoleNames()->implode(', ') . "<br>";
}
