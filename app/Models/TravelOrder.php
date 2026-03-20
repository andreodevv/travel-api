<?php

namespace App\Models;

use App\Enums\TravelOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TravelOrder extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atributos que podem ser preenchidos em massa.
     */
    protected $fillable = [
        'user_id',
        'origin',
        'destination',
        'departure_date',
        'return_date',
        'status',
    ];

    /**
     * Define valores padrão para os atributos do modelo ANTES de salvar no banco.
     * Isso resolve o problema do Enum vir null após o create().
     */
    protected $attributes = [
        'status' => TravelOrderStatus::REQUESTED,
    ];

    /**
     * Casts de atributos.
     * Isso transforma a string do banco no objeto Enum automaticamente.
     */
    protected $casts = [
        'status' => TravelOrderStatus::class,
        'departure_date' => 'date',
        'return_date' => 'date',
    ];

    /**
     * Relacionamento: Um pedido pertence a um usuário.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Regra de Negócio: Verifica se o pedido pode ser cancelado.
     * Centralizar isso aqui evita repetição de IFs nos Controllers.
     */
    public function canBeCanceled(): bool
    {
        return $this->status->canCancel();
    }

    /**
     * Scope para filtragem (Requisito do teste: Listar com filtros).
     * Isso deixa o seu Controller extremamente limpo.
     */
    public function scopeFilter($query, array $filters)
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
                        ->orWhere('requester_name', 'like', "%{$search}%");
                });
            });
    }
}