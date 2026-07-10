<?php

namespace App\Livewire;

use App\Models\Setting;
use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Settings extends Component
{
    use WithFileUploads;

    public string $activeTab = 'general'; // general | roles
    public array $rolePermissions = [];

    public array $permissionsList = [
        'view_projects' => 'View Companies Registry',
        'manage_projects' => 'Manage Companies (Create/Edit/Archive)',
        'view_tasks' => 'View Kanban Board',
        'manage_tasks' => 'Manage Tasks (Create/Edit/Delete/Drag)',
        'view_deadlines' => 'View Deadline Center',
        'view_calendar' => 'View Calendar Dashboard',
        'view_activity' => 'View Activity Center',
        'view_credentials' => 'View Credentials Vault',
        'manage_credentials' => 'Manage Credentials (Create/Edit/Delete)',
        'view_reports' => 'View Reports (Time & Productivity)',
        'manage_users' => 'Manage Users Settings',
    ];

    // App fields
    public string $app_name = '';
    public $app_logo;
    public string $app_logo_path = '';

    // SMTP fields
    public string $mail_host = '';
    public string $mail_port = '';
    public string $mail_username = '';
    public string $mail_password = '';
    public string $mail_encryption = '';
    public string $mail_from_address = '';
    public string $mail_from_name = '';

    // Telegram fields
    public string $telegram_bot_token = '';
    public string $telegram_bot_username = '';

    // Default Manager
    public ?int $default_manager_id = null;

    protected array $rules = [
        'app_name' => 'required|string|max:255',
        'app_logo' => 'nullable|image|max:1024',
        'mail_host' => 'nullable|string|max:255',
        'mail_port' => 'nullable|integer',
        'mail_username' => 'nullable|string|max:255',
        'mail_password' => 'nullable|string|max:255',
        'mail_encryption' => 'nullable|string|max:20',
        'mail_from_address' => 'nullable|email',
        'mail_from_name' => 'nullable|string|max:255',
        'telegram_bot_token' => 'nullable|string|max:255',
        'telegram_bot_username' => 'nullable|string|max:255',
        'default_manager_id' => 'nullable|exists:users,id',
    ];

    /**
     * Mount component and load current values.
     */
    public function mount(): void
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $this->app_name = Setting::get('app_name', 'SoftProject Hub');
        $this->app_logo_path = Setting::get('app_logo_path', '');
        
        $this->mail_host = Setting::get('mail_host', '');
        $this->mail_port = Setting::get('mail_port', '');
        $this->mail_username = Setting::get('mail_username', '');
        $this->mail_password = Setting::get('mail_password', '');
        $this->mail_encryption = Setting::get('mail_encryption', 'tls');
        $this->mail_from_address = Setting::get('mail_from_address', '');
        $this->mail_from_name = Setting::get('mail_from_name', '');

        $this->telegram_bot_token = Setting::get('telegram_bot_token', '');
        $this->telegram_bot_username = Setting::get('telegram_bot_username', '');

        $this->default_manager_id = Setting::get('default_manager_id', null);

        // Load roles and their permissions
        $roles = Role::where('name', '!=', 'admin')->get();
        foreach ($roles as $role) {
            foreach (array_keys($this->permissionsList) as $permission) {
                Permission::findOrCreate($permission, 'web');
                $this->rolePermissions[$role->name][$permission] = $role->hasPermissionTo($permission);
            }
        }
    }

    /**
     * Save settings to DB.
     */
    public function save(): void
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $this->validate();

        Setting::set('app_name', $this->app_name);

        if ($this->app_logo) {
            // Delete old logo file if exists
            if ($this->app_logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->app_logo_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($this->app_logo_path);
            }
            $this->app_logo_path = $this->app_logo->store('logos', 'public');
            Setting::set('app_logo_path', $this->app_logo_path);
        }

        Setting::set('mail_host', $this->mail_host);
        Setting::set('mail_port', $this->mail_port, 'integer');
        Setting::set('mail_username', $this->mail_username);
        Setting::set('mail_password', $this->mail_password);
        Setting::set('mail_encryption', $this->mail_encryption);
        Setting::set('mail_from_address', $this->mail_from_address);
        Setting::set('mail_from_name', $this->mail_from_name);

        Setting::set('telegram_bot_token', $this->telegram_bot_token);
        Setting::set('telegram_bot_username', $this->telegram_bot_username);

        Setting::set('default_manager_id', $this->default_manager_id, 'integer');

        session()->flash('message', 'System settings successfully updated.');
    }

    /**
     * Toggle permission for a specific role dynamically.
     */
    public function togglePermission(string $roleName, string $permissionName): void
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $role = Role::findByName($roleName, 'web');
        $currentValue = $this->rolePermissions[$roleName][$permissionName] ?? false;
        $newValue = !$currentValue;
        
        if ($newValue) {
            $role->givePermissionTo($permissionName);
        } else {
            $role->revokePermissionTo($permissionName);
        }
        
        $this->rolePermissions[$roleName][$permissionName] = $newValue;
        
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        $roleTitle = ucfirst($roleName);
        $permTitle = $this->permissionsList[$permissionName];
        $action = $newValue ? 'granted' : 'revoked';
        
        session()->flash('message', "Permission '{$permTitle}' was {$action} for role '{$roleTitle}'.");
    }

    public function render()
    {
        $managers = User::role(['admin', 'manager'])->orderBy('name')->get();
        return view('livewire.settings', [
            'managers' => $managers,
        ])->layout('layouts.app');
    }
}
