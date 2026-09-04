<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'data_scope', 'include_responsible_assets'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'include_responsible_assets' => 'boolean',
        ];
    }

    public function permittedDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user_scope');
    }

    public function permittedLocations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_user_scope');
    }

    public function permittedAssets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_user_scope');
    }

    public function hasFullDataAccess(): bool
    {
        return $this->hasRole('Διαχειριστής') || $this->data_scope === 'all';
    }
}
