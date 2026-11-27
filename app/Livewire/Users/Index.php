<?php

namespace App\Livewire\Users;

use App\Jobs\SendTelegramMessageJob;
use App\Livewire\Concerns\RequiresPermission;
use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\TelegramService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use RequiresPermission;
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

    public string $color = '#3b82f6';

    public string $newPassword = '';

    /** Show the password in the field (after "Generate" or by the eye button). */
    public bool $revealPassword = false;

    /** End every session of the user so the old password stops working at once. */
    public bool $signOutEverywhere = true;

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
        $this->color = '#3b82f6';
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
        $this->color = $user->color ?: '#3b82f6';
        $this->showModal = true;
    }

    /**
     * Open Modal for password reset.
     */
    public function openResetModal(int $userId): void
    {
        $this->requirePermission('manage_users');

        $target = User::findOrFail($userId);

        if (! $this->canChangePasswordOf($target)) {
            session()->flash('error', 'Only administrators can change the password of an administrator.');

            return;
        }

        $this->resetValidation();
        $this->editingUserId = $userId;
        $this->newPassword = '';
        $this->revealPassword = false;
        $this->signOutEverywhere = true;
        $this->showModal = false;
        $this->showResetModal = true;
    }

    /**
     * Administrators can change anybody's password; everybody else with access to this page only the password of
     * people who are not administrators (otherwise a curator could take over an admin account).
     */
    private function canChangePasswordOf(User $target): bool
    {
        return auth()->user()->hasRole('admin') || ! $target->hasRole('admin');
    }

    public function closeResetModal(): void
    {
        $this->showResetModal = false;
        $this->newPassword = '';
        $this->revealPassword = false;
    }

    /**
     * Fill the field with a random password (letters and digits, easy to read out or paste).
     */
    public function generatePassword(): void
    {
        $this->requirePermission('manage_users');

        $this->newPassword = Str::password(14, symbols: false);
        $this->revealPassword = true;
        $this->resetValidation('newPassword');
    }

    /**
     * Save newly created or edited user.
     */
    public function saveUser(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'role' => 'required|in:admin,manager,curator,worker',
            'color' => 'nullable|string|max:30',
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
                'color' => $this->color ?: '#3b82f6',
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
                'color' => $this->color ?: '#3b82f6',
            ]);
            $user->assignRole($this->role);
            session()->flash('message', 'User successfully created.');
        }

        $this->showModal = false;
    }

    /**
     * Change the password of a user. The old one stops working at once; the person is told it was changed.
     */
    public function resetPassword(): void
    {
        $this->requirePermission('manage_users');

        $this->validate([
            'newPassword' => 'required|string|min:8|max:255',
        ]);

        $user = User::findOrFail($this->editingUserId);

        if (! $this->canChangePasswordOf($user)) {
            session()->flash('error', 'Only administrators can change the password of an administrator.');
            $this->closeResetModal();

            return;
        }

        $user->forceFill([
            'password' => Hash::make($this->newPassword),
            'remember_token' => Str::random(60),
        ])->save();

        if ($this->signOutEverywhere) {
            $this->endSessionsOf($user);
        }

        $actor = auth()->user();

        ActivityLog::create([
            'user_id' => $actor->id,
            'action' => 'user_password_changed',
            'description' => "Password of {$user->name} ({$user->email}) was changed by {$actor->name}".($this->signOutEverywhere ? ', signed out everywhere' : ''),
        ]);

        if ($user->id !== $actor->id) {
            $this->tellUserPasswordChanged($user, $actor);
        }

        $this->closeResetModal();
        session()->flash('message', "Password of {$user->name} changed".($this->signOutEverywhere && $user->id !== $actor->id ? '; they were signed out of all devices.' : '.'));
    }

    /**
     * Delete the stored sessions (database session driver); the current session of the person doing it is kept.
     */
    private function endSessionsOf(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->when($user->id === auth()->id(), fn ($query) => $query->where('id', '!=', session()->getId()))
            ->delete();
    }

    /**
     * In-app and Telegram notice (never with the password itself).
     */
    private function tellUserPasswordChanged(User $user, User $actor): void
    {
        $message = "Your password was changed by {$actor->name}. If you did not expect this, tell an administrator.";

        $user->notify(new AppNotification('Your password was changed', $message, route('profile'), 'password_changed'));

        if ($user->telegram_id) {
            SendTelegramMessageJob::dispatch($user->telegram_id, '🔐 '.TelegramService::escapeMarkdownV2($message));
        }
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
            'resetUser' => $this->showResetModal && $this->editingUserId ? User::find($this->editingUserId) : null,
            'viewerIsAdmin' => auth()->user()->hasRole('admin'),
        ]);
    }
}
