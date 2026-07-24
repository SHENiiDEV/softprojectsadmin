<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterRole = '';

    // Modal controls
    public bool $showModal = false;

    public bool $showResetModal = false;

    public ?int $editingUserId = null;

    // Form inputs
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'worker';

    public string $newPassword = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterRole' => ['except' => ''],
    ];

    /**
     * Authorize user access in mount.
     */
    public function mount(): void
    {
        if (! auth()->user()->can('manage_users')) {
            abort(403, 'Unauthorized.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterRole(): void
    {
        $this->resetPage();
    }

    /**
     * Open Modal for creation.
     */
    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'worker';
        $this->showModal = true;
    }

    /**
     * Open Modal for editing.
     */
    public function openEditModal(int $userId): void
    {
        $this->resetValidation();
        $user = User::findOrFail($userId);
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->roles->first()?->name ?? 'worker';
        $this->showModal = true;
    }

    /**
     * Open Modal for password reset.
     */
    public function openResetModal(int $userId): void
    {
        $this->resetValidation();
        $this->editingUserId = $userId;
        $this->newPassword = '';
        $this->showResetModal = true;
    }

    /**
     * Save newly created or edited user.
     */
    public function saveUser(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'role' => 'required|in:admin,manager,curator,worker',
        ];

        if ($this->editingUserId) {
            $rules['email'] = [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingUserId),
            ];
            $this->validate($rules);

            $user = User::findOrFail($this->editingUserId);
            $user->update([
                'name' => $this->name,
                'email' => $this->email,
            ]);
            $user->syncRoles([$this->role]);
            session()->flash('message', 'User successfully updated.');
        } else {
            $rules['email'] = 'required|string|email|max:255|unique:users,email';
            $rules['password'] = 'required|string|min:8';
            $this->validate($rules);

            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);
            $user->assignRole($this->role);
            session()->flash('message', 'User successfully created.');
        }

        $this->showModal = false;
    }

    /**
     * Reset user password by moderator.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'newPassword' => 'required|string|min:8',
        ]);

        $user = User::findOrFail($this->editingUserId);
        $user->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->showResetModal = false;
        session()->flash('message', 'User password successfully reset.');
    }

    /**
     * Delete user from the system. (Admin only)
     */
    public function deleteUser(int $userId): void
    {
        if (! auth()->user()->hasRole('admin')) {
            session()->flash('error', 'Only administrators can delete users.');

            return;
        }

        if (auth()->id() === $userId) {
            session()->flash('error', 'You cannot delete yourself.');

            return;
        }

        $user = User::findOrFail($userId);
        $user->delete();
        session()->flash('message', 'User successfully deleted.');
    }

    public function render()
    {
        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $users = User::query()
            ->with('roles')
            ->when($this->search, function ($query) use ($like) {
                $query->where(function ($q) use ($like) {
                    $q->where('name', $like, '%'.$this->search.'%')
                        ->orWhere('email', $like, '%'.$this->search.'%');
                });
            })
            ->when($this->filterRole, function ($query) {
                $query->role($this->filterRole);
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.users.index', [
            'users' => $users,
        ]);
    }
}
