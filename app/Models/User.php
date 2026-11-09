<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    protected $fillable = [
        'name', 'gender', 'birthplace', 'date_of_birth', 'phone_number',
        'email', 'email_verified_at', 'password', 'address', 'photo', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    // samakan dengan default kolom DB agar model yang baru dibuat langsung terbaca aktif
    protected $attributes = ['is_active' => true];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    /** Kolom yang tidak dicatat di log aktivitas. */
    protected array $logExcept = ['password', 'remember_token'];

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /** PRD 5.2: hanya super admin yang menetapkan role super_admin. */
    public function assignableRoles(): array
    {
        $roles = array_keys(config('mantaaba.roles'));

        return $this->isSuperAdmin() ? $roles : array_values(array_diff($roles, ['super_admin']));
    }

    /** Non-SA tidak boleh menyentuh akun super admin. */
    public function canManage(User $target): bool
    {
        return $this->isSuperAdmin() || ! $target->isSuperAdmin();
    }

    /** Berikan paket hak akses (PRD 5.4) sebagai permission langsung. */
    public function givePackage(string $package): static
    {
        return $this->givePermissionTo(Permissions::package($package));
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }
}
