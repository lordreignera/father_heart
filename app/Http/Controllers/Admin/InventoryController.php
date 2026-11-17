<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:160']]);
        $items = InventoryItem::query()->when(filled($filters['q'] ?? null), fn ($query) => $query->where('name', 'like', '%'.$filters['q'].'%'))
            ->orderBy('name')->paginate(25)->withQueryString();
        $movements = InventoryMovement::with(['item', 'recorder'])->orderByDesc('occurred_on')->orderByDesc('id')->limit(20)->get();
        return view('admin.inventory.index', compact('items', 'movements'));
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160', Rule::unique('inventory_items', 'name')],
            'unit' => ['required', 'string', 'max:40'],
        ]);
        InventoryItem::create($data);
        return redirect()->route('admin.inventory.index')->with('status', 'Item added. Record an approved opening count before stock movements.');
    }

    public function storeMovement(Request $request, InventoryItem $item): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['opening', 'in', 'out'])],
            'quantity' => ['required', 'integer', 'between:0,1000000000'],
            'occurred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now('Africa/Kampala')->toDateString()],
            'reference' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'opening_confirmed' => $request->input('type') === 'opening' ? ['required', 'accepted'] : ['nullable'],
        ]);

        DB::transaction(function () use ($item, $data, $request): void {
            $locked = InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $quantity = (int) $data['quantity'];
            if ($data['type'] === 'opening' && $locked->opening_date) {
                throw ValidationException::withMessages(['type' => 'This item already has an opening count.']);
            }
            if ($data['type'] !== 'opening' && ! $locked->opening_date) {
                throw ValidationException::withMessages(['type' => 'Record an approved opening count first.']);
            }
            if ($data['type'] !== 'opening' && $quantity < 1) {
                throw ValidationException::withMessages(['quantity' => 'Stock in and out must be at least one.']);
            }
            if ($locked->opening_date && $data['occurred_on'] < $locked->opening_date->format('Y-m-d')) {
                throw ValidationException::withMessages(['occurred_on' => 'The movement date cannot be before the opening count.']);
            }
            $latestDate = $locked->movements()->max('occurred_on');
            if ($latestDate && $data['occurred_on'] < \Carbon\Carbon::parse($latestDate)->toDateString()) {
                throw ValidationException::withMessages(['occurred_on' => 'The movement date cannot be earlier than the latest recorded movement.']);
            }
            if ($data['type'] === 'out' && $quantity > $locked->quantity) {
                throw ValidationException::withMessages(['quantity' => 'Stock out cannot exceed the available balance.']);
            }
            if ($data['type'] === 'opening') {
                $locked->quantity = $quantity;
                $locked->opening_date = $data['occurred_on'];
            } else {
                $locked->quantity = (int) $locked->quantity + ($data['type'] === 'in' ? $quantity : -$quantity);
            }
            $locked->save();
            $locked->movements()->create([
                'recorded_by' => $request->user()->id, 'type' => $data['type'], 'quantity' => $quantity,
                'occurred_on' => $data['occurred_on'], 'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null,
            ]);
        });

        return redirect()->route('admin.inventory.index')->with('status', 'Inventory movement recorded.');
    }
}
