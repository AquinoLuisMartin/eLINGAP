<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['role_id', 'username', 'email', 'password_hash', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'is_active'])]
#[Hidden(['password_hash', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password_hash' => 'hashed',
        ];
    }

    /**
     * The schema stores the hash in password_hash instead of Laravel's default password column.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function search(Builder $query, string $term): Builder
    {
        $term = '%'.mb_strtolower($term).'%';

        return $query->where(function (Builder $query) use ($term) {
            $query->whereRaw('lower(username) like ?', [$term])
                ->orWhereRaw('lower(first_name) like ?', [$term])
                ->orWhereRaw('lower(last_name) like ?', [$term]);
        });
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->role?->name === $role;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    public function isOscaStaff(): bool
    {
        return $this->hasRole(UserRole::OscaStaff);
    }

    /**
     * Named route for the user's role home after sign-in.
     */
    public function homeRouteName(): string
    {
        return match ($this->role?->name) {
            UserRole::Admin => 'administration.dashboard',
            UserRole::OscaStaff => 'dashboard',
            default => 'login',
        };
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->name_suffix,
        ])));
    }
}
