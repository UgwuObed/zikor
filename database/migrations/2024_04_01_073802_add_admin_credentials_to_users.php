<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AddAdminCredentialsToUsers extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Create a default admin user
        $adminData = [
            'first_name' => 'Admin',
            'last_name' => 'Zikor', 
            'email' => 'admin@zikor.com',
            'password' => Hash::make('zikoradminpassword'),
            'is_admin' => true, 
        ];

        // Create admin user only if it doesn't exist
        if (!User::where('email', $adminData['email'])->exists()) {
            User::create($adminData);
        }

        
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the isAdmin column if rolling back migration
            $table->dropColumn('is_admin');
        });
    }
}
