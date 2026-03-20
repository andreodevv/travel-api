<?php

namespace App\Models;

use App\Models\TravelOrder;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasUlids;

    /**
     * Atributos preenchíveis.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * Atributos ocultos em serialização (JSON).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts de tipos.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean', // Garante que venha como true/false, não 1/0
        ];
    }

    // --- Relacionamentos ---

    /**
     * Um usuário possui muitos pedidos de viagem.
     */
    public function travelOrders(): HasMany
    {
        return $this->hasMany(TravelOrder::class);
    }

    // --- Métodos Requeridos pelo JWT ---

    /**
     * Identificador que será armazenado no "subject" do token.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Claims personalizadas para o payload do token.
     * Útil para injetar se o usuário é admin diretamente no token, se quiser.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'is_admin' => $this->is_admin,
        ];
    }
}