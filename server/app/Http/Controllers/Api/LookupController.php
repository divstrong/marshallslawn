<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\PropertyResource;
use App\Models\Customer;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Reference data the Estimator needs while building a quote.
 */
class LookupController extends Controller
{
    /**
     * GET /api/customers?q=
     */
    public function customers(Request $request): AnonymousResourceCollection
    {
        $term = trim((string) $request->query('q', ''));

        $query = Customer::query();

        if (strlen($term) >= 2) {
            $query->searchName($term);
        }

        $customers = $query
            ->orderByRaw('COALESCE(company_name, last_name)')
            ->limit(30)
            ->get();

        return CustomerResource::collection($customers);
    }

    /**
     * GET /api/customers/{customer}/properties
     */
    public function properties(Customer $customer): AnonymousResourceCollection
    {
        return PropertyResource::collection(
            $customer->properties()->orderByDesc('is_primary')->orderBy('address')->get()
        );
    }

    /**
     * GET /api/services — active services for quote line items.
     */
    public function services(): JsonResponse
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->full_name ?: $service->name,
                'default_price' => (float) $service->default_price,
            ]);

        return response()->json(['data' => $services]);
    }
}
