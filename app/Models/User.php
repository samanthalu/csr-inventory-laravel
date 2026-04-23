<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const PERMISSION_READ       = 1;    // 0001
    const PERMISSION_CREATE     = 2;    // 0010
    const PERMISSION_EDIT       = 4;    // 0100
    const PERMISSION_DELETE     = 8;    // 1000

    const TYPE_ADMIN          = 'admin';
    const TYPE_ICT            = 'ict';
    const TYPE_ADMINISTRATION = 'administration';
    const TYPE_STANDARD       = 'standard';

    public function hasPermission($permission): bool
    {
        return ($this->permissions & $permission) === $permission;
    }

    public function isAdmin(): bool          { return $this->user_type === self::TYPE_ADMIN; }
    public function isIct(): bool            { return $this->user_type === self::TYPE_ICT; }
    public function isAdministration(): bool { return $this->user_type === self::TYPE_ADMINISTRATION; }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'user_type',
        'permissions',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
        ];
    }
}
