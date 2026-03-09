<?php
// database/seeders/RolePermissionSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Créer les permissions
        $permissions = [
            'view pointages',
            'create pointages',
            'edit pointages',
            'delete pointages',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage sanctions',
            'export data',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Créer les rôles et assigner les permissions
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        $coach = Role::create(['name' => 'coach']);
        $coach->givePermissionTo([
            'view pointages',
            'edit pointages',
            'manage sanctions',
        ]);

        $stagiaire = Role::create(['name' => 'stagiaire']);
        $stagiaire->givePermissionTo([
            'view pointages',
        ]);

        // Créer un admin par défaut
        $adminUser = User::create([
            'nom' => 'Admin',
            'prenom' => 'Super',
            'email' => 'admin@pointage.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'est_actif' => true,
        ]);
        $adminUser->assignRole('admin');
    }
}