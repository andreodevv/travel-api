<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TravelOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

/**
 * Class TravelOrder
 * * @property string $id Identificador único (ULID)
 * @property string $order_number Número de negócio (Business Key)
 * @property string $user_id Relacionamento com o solicitante
 * @property string $origin Cidade de origem
 * @property string $destination Cidade de destino
 * @property \Illuminate\Support\Carbon $departure_date Data de partida
 * @property \Illuminate\Support\Carbon|null $return_date Data de retorno (opcional)
 * @property TravelOrderStatus $status Estado atual do pedido (Enum)
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * * @property-read \App\Models\User $user
 */
class TravelOrder extends Model implements Auditable
{
    use HasFactory, 
        SoftDeletes, 
        HasUlids, 
        AuditableTrait;

    // =========================================================================
    // BLOCO 0: CONFIGURAÇÕES DE AUDITORIA (Compliance & Traceability)
    // =========================================================================

    /**
     * Campos que serão ignorados na trilha de auditoria.
     * * Removemos o 'updated_at' para evitar registros redundantes 
     * quando não há alteração real nos dados de negócio.
     * * @var array<int, string>
     */
    protected $auditExclude = [
        'updated_at',
    ];

    // =========================================================================
    // BLOCO 1: CONFIGURAÇÕES E ATRIBUTOS (Properties & Casts)
    // =========================================================================

    /**
     * Atributos que podem ser preenchidos em massa.
     * * @var array<int, string>
     */
    protected $fillable = [
        'order_number',
        'user_id',
        'origin',
        'destination',
        'departure_date',
        'return_date',
        'status',
        'processed_at'
    ];

    /**
     * Define valores padrão para os atributos do modelo ANTES de salvar no banco.
     * Isso resolve o problema do Enum vir null após o create().
     * * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => TravelOrderStatus::REQUESTED,
    ];

    /**
     * Casts de atributos.
     * Isso transforma a string do banco no objeto Enum automaticamente.
     * * @var array<string, string>
     */
    protected $casts = [
        'status' => TravelOrderStatus::class,
        'departure_date' => 'date',
        'return_date' => 'date',
        'processed_at' => 'datetime'
    ];

    // =========================================================================
    // BLOCO 2: CUSTOMIZAÇÃO DE ROTAS (Route Model Binding)
    // =========================================================================

    /**
     * Personalização do Route Model Binding.
     * Permite que a API resolva recursos tanto pelo UUID/ULID técnico 
     * quanto pelo Order Number (Business Key), melhorando a DX e UX.
     * * @param mixed $value
     * @param string|null $field
     * @return Model
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->where('id', $value)
            ->orWhere('order_number', $value)
            ->firstOrFail();
    }

    // =========================================================================
    // BLOCO 3: RELACIONAMENTOS (Relationships)
    // =========================================================================

    /**
     * Relacionamento: Um pedido pertence a um usuário.
     * * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =========================================================================
    // BLOCO 4: REGRAS DE NEGÓCIO (Domain Logic)
    // =========================================================================

    /**
     * Regra de Negócio: Verifica se o pedido pode ser cancelado.
     * Centralizar isso aqui evita repetição de IFs nos Controllers.
     * * @return bool
     */
    public function canBeCanceled(): bool
    {
        return $this->status->canCancel();
    }

    // =========================================================================
    // BLOCO 5: ESCOPOS DE CONSULTA (Query Scopes)
    // =========================================================================

    /**
     * Scope para filtragem (Requisito do teste: Listar com filtros).
     * Isso deixa o seu Controller extremamente limpo.
     * * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($filters['destination'] ?? null, function ($q, $destination) {
                $q->where('destination', 'like', "%{$destination}%");
            })
            ->when($filters['origin'] ?? null, function ($q, $origin) {
                $q->where('origin', 'like', "%{$origin}%");
            })
            // Filtragem por Período (Requisito do Desafio)
            // Filtra pedidos que tenham a data de DEPARTURE dentro do range
            ->when($filters['start_date'] ?? null, function ($q, $date) {
                $q->whereDate('departure_date', '>=', $date);
            })
            ->when($filters['end_date'] ?? null, function ($q, $date) {
                $q->whereDate('departure_date', '<=', $date);
            })
            // Busca global (opcional, mas brilha em testes)
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('destination', 'like', "%{$search}%")
                        ->orWhere('origin', 'like', "%{$search}%")
                        ->orWhereRelation('user', 'name', 'like', "%{$search}%");
                });
            });
    }
}