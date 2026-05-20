<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuoteResource;
use App\Models\Employee;
use App\Models\Estimate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Quotes (estimates) owned by the authenticated Estimator.
 */
class QuoteController extends Controller
{
    private const STATUSES = ['draft', 'sent', 'accepted', 'declined', 'expired'];

    /**
     * GET /api/quotes?status=
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var Employee $employee */
        $employee = $request->user();

        $query = Estimate::query()
            ->with(['customer', 'property'])
            ->where('created_by', $employee->id);

        $status = (string) $request->query('status', '');
        if (in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        return QuoteResource::collection($query->orderByDesc('created_at')->get());
    }

    /**
     * GET /api/quotes/{quote}
     */
    public function show(Estimate $quote): QuoteResource
    {
        $quote->load(['customer', 'property', 'lineItems' => fn ($q) => $q->orderBy('sort_order')]);

        return new QuoteResource($quote);
    }

    /**
     * POST /api/quotes
     */
    public function store(Request $request): QuoteResource
    {
        /** @var Employee $employee */
        $employee = $request->user();
        $data = $this->validateQuote($request);

        $quote = DB::transaction(function () use ($data, $employee): Estimate {
            $quote = Estimate::create([
                'customer_id' => $data['customer_id'],
                'property_id' => $data['property_id'] ?? null,
                'created_by' => $employee->id,
                'status' => $data['status'] ?? 'draft',
                'square_footage' => $data['square_footage'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
            ]);

            $this->syncLineItems($quote, $data['line_items'] ?? []);

            return $quote;
        });

        return new QuoteResource($quote->load(['customer', 'property', 'lineItems']));
    }

    /**
     * PUT|PATCH /api/quotes/{quote}
     */
    public function update(Request $request, Estimate $quote): QuoteResource
    {
        $data = $this->validateQuote($request);

        DB::transaction(function () use ($quote, $data): void {
            $quote->update([
                'customer_id' => $data['customer_id'],
                'property_id' => $data['property_id'] ?? null,
                'status' => $data['status'] ?? $quote->status,
                'square_footage' => $data['square_footage'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if (array_key_exists('line_items', $data)) {
                $quote->lineItems()->delete();
                $this->syncLineItems($quote, $data['line_items'] ?? []);
            }
        });

        return new QuoteResource($quote->fresh(['customer', 'property', 'lineItems']));
    }

    /**
     * Recreate line items and recompute the quote totals.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncLineItems(Estimate $quote, array $items): void
    {
        $subtotal = 0.0;

        foreach (array_values($items) as $index => $item) {
            $quantity = (float) ($item['quantity'] ?? 1);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $lineTotal = round($quantity * $unitPrice, 2);
            $subtotal += $lineTotal;

            $quote->lineItems()->create([
                'description' => $item['description'] ?? '',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $lineTotal,
                'sort_order' => $index + 1,
            ]);
        }

        $subtotal = round($subtotal, 2);
        $quote->update([
            'subtotal' => $subtotal,
            'tax' => 0,
            'total' => $subtotal,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateQuote(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'status' => ['nullable', 'in:' . implode(',', self::STATUSES)],
            'square_footage' => ['nullable', 'numeric'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.description' => ['required', 'string', 'max:500'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
