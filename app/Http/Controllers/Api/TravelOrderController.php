<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TravelOrder;
use App\Services\TravelOrderService;
use App\Enums\TravelOrderStatus;
use App\Http\Requests\StoreTravelOrderRequest;
use App\Http\Resources\TravelOrderResource;
use App\Http\Resources\AuditResource; // <-- Importação do AuditResource adicionada
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

/**
 * Class TravelOrderController
 * * Gerencia o ciclo de vida dos pedidos de viagem, lidando com a camada
 * de transporte (HTTP), autorização de acesso e filtragem de recursos.
 */
class TravelOrderController extends Controller
{
    /**
     * @param TravelOrderService $service Camada de negócio para operações de pedidos.
     */
    public function __construct(protected TravelOrderService $service) {}

    /**
     * Lista os pedidos de viagem com suporte a filtros e paginação.
     * * @param Request $request Contém filtros opcionais (status, origin, destination, search).
     * @return AnonymousResourceCollection Coleção paginada de pedidos com Eager Loading do usuário.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = TravelOrder::query()
            ->when(!auth()->user()->is_admin, function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->filter($request->all())
            ->with('user') 
            ->paginate();

        return TravelOrderResource::collection($orders);
    }

    /**
     * Registra um novo pedido de viagem para o usuário autenticado.
     * * @param StoreTravelOrderRequest $request Valida campos de origem, destino e datas.
     * @return TravelOrderResource Pedido criado com ID (ULID) e Número de Negócio.
     */
    public function store(StoreTravelOrderRequest $request): TravelOrderResource
    {
        $order = $this->service->create($request->validated(), auth()->id());
        
        return new TravelOrderResource($order);
    }

    /**
     * Retorna os detalhes de um pedido específico.
     * * @param TravelOrder $travelOrder Resolvido automaticamente via Route Model Binding (ID ou Order Number).
     * @return TravelOrderResource
     * @throws \Illuminate\Auth\Access\AuthorizationException Caso o usuário não tenha permissão de acesso.
     */
    public function show(TravelOrder $travelOrder): TravelOrderResource
    {
        $this->authorize('view', $travelOrder); 
        
        return new TravelOrderResource($travelOrder);
    }

    /**
     * Atualiza o status de um pedido (Aprovação/Cancelamento).
     *
     * @param Request $request
     * @param TravelOrder $travelOrder
     * @return TravelOrderResource
     */
    public function updateStatus(Request $request, TravelOrder $travelOrder): TravelOrderResource
    {
        $this->authorize('updateStatus', $travelOrder);

        $request->validate([
            'status' => ['required', Rule::enum(TravelOrderStatus::class)]
        ]);

        $status = TravelOrderStatus::from($request->status);
        
        // Se der erro, o Service lança a exception e o Laravel intercepta sozinho
        $order = $this->service->updateStatus($travelOrder, $status);
        
        return new TravelOrderResource($order);
    }
    // =========================================================================
    // BLOCO 6: AUDITORIA E COMPLIANCE (Admin Only)
    // =========================================================================

    /**
     * Recupera o histórico de auditoria completo do pedido.
     * * Exclusivo para administradores. Permite visualizar a trilha de 
     * modificações (Timeline) para garantir transparência e compliance.
     * * @param TravelOrder $travelOrder
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function audits(TravelOrder $travelOrder): AnonymousResourceCollection
    {
        // Validação via Policy (Regra: viewAudits)
        $this->authorize('viewAudits', $travelOrder);

        // Busca as auditorias com o usuário que realizou a ação
        $audits = $travelOrder->audits()
            ->with('user')
            ->latest()
            ->get();

        return AuditResource::collection($audits);
    }
}