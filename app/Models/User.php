<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

/**
 * Class User
 * @property string $id
 * @property string $name
 * @property string $email
 * @property bool $is_admin
 */
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasUlids;

    // =========================================================================
    // CONFIGURAÇÕES E ATRIBUTOS
    // =========================================================================

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Define as conversões de tipos (Laravel 11+ style).
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    // =========================================================================
    // RELACIONAMENTOS
    // =========================================================================

    /**
     * Um usuário possui muitos pedidos de viagem.
     */
    public function travelOrders(): HasMany
    {
        return $this->hasMany(TravelOrder::class);
    }

    // =========================================================================
    // MÉTODOS JWT (Autenticação Stateless)
    // =========================================================================

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Claims personalizadas no payload do token.
     * Incluímos o status de admin para facilitar verificações no Front-end sem novas queries.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'is_admin' => $this->is_admin,
        ];
    }
}