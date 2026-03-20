<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TravelOrder;
use App\Services\TravelOrderService;
use App\Enums\TravelOrderStatus;
use App\Http\Requests\StoreTravelOrderRequest;
use App\Http\Resources\TravelOrderResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TravelOrderController extends Controller
{
    public function __construct(protected TravelOrderService $service) {}

    public function index(Request $request)
    {
        // Iniciamos a query. O método 'when' é perfeito aqui.
        $orders = TravelOrder::query()
            ->when(!auth()->user()->is_admin, function ($query) {
                // Se NÃO for admin, aplica a restrição de dono
                $query->where('user_id', auth()->id());
            })
            ->filter($request->all()) // Nossa filtragem melhorada
            ->with('user') // Eager Loading para evitar o problema de N+1 no requester_name
            ->paginate();

        return TravelOrderResource::collection($orders);
    }

    public function store(StoreTravelOrderRequest $request)
    {
        $order = $this->service->create($request->validated(), auth()->id());
        
        return new TravelOrderResource($order);
    }

    public function show(TravelOrder $travelOrder)
    {
        $this->authorize('view', $travelOrder); 
        
        return new TravelOrderResource($travelOrder);
    }

    public function updateStatus(Request $request, TravelOrder $travelOrder)
    {
        $this->authorize('updateStatus', $travelOrder);

        $request->validate([
            'status' => ['required', Rule::enum(TravelOrderStatus::class)]
        ]);

        try {
            $status = TravelOrderStatus::from($request->status);
            $order = $this->service->updateStatus($travelOrder, $status);
            
            return new TravelOrderResource($order);
        } catch (\Exception $e) { 
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}