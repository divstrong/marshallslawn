<?php

namespace App\Livewire;

use App\Models\Crew;
use App\Models\CrewType;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Settings -> Crew Types. Drives the crew "type" checkboxes on a crew and the
 * quick-filter buttons on the dispatch board, so the order set here is the
 * order those buttons appear in.
 */
class CrewTypeManager extends Component
{
    public array $types = [];

    // Create/edit form
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $formLabel = '';
    public bool $formActive = true;

    public function mount(): void
    {
        $this->loadTypes();
    }

    private function loadTypes(): void
    {
        $types = CrewType::orderBy('sort_order')->orderBy('label')->get();

        // Crew counts gate deletion — a type in use can't be removed without
        // silently orphaning the key sitting in crews.type.
        $this->types = $types->map(fn (CrewType $t) => array_merge($t->toArray(), [
            'crews_count' => Crew::query()->whereJsonContains('type', $t->name)->count(),
        ]))->all();
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->formLabel = '';
        $this->formActive = true;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $type = CrewType::find($id);
        if (! $type) {
            return;
        }

        $this->editingId = $type->id;
        $this->formLabel = $type->label;
        $this->formActive = $type->is_active;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function saveType(): void
    {
        $label = trim($this->formLabel);
        if ($label === '') {
            return;
        }

        if ($this->editingId) {
            $type = CrewType::find($this->editingId);
            if (! $type) {
                return;
            }

            // The machine name is deliberately not regenerated on rename: it is
            // the value stored inside crews.type, so changing it would detach
            // every crew already carrying this type.
            $type->update(['label' => $label, 'is_active' => $this->formActive]);
        } else {
            CrewType::create([
                'name' => $this->uniqueName($label),
                'label' => $label,
                'is_active' => $this->formActive,
                'sort_order' => (int) (CrewType::max('sort_order') ?? 0) + 1,
            ]);
        }

        $this->showForm = false;
        $this->loadTypes();
    }

    public function deleteType(int $id): void
    {
        $type = CrewType::find($id);
        if (! $type) {
            return;
        }

        if (Crew::query()->whereJsonContains('type', $type->name)->exists()) {
            session()->flash('crew-type-error', 'Cannot delete a type that is assigned to crews. Deactivate it instead.');

            return;
        }

        $type->delete();
        $this->loadTypes();
    }

    /** Swap sort_order with the neighbour in $direction ('up' or 'down'). */
    public function move(int $id, string $direction): void
    {
        $type = CrewType::find($id);
        if (! $type) {
            return;
        }

        $neighbour = CrewType::query()
            ->when(
                $direction === 'up',
                fn ($q) => $q->where('sort_order', '<', $type->sort_order)->orderByDesc('sort_order'),
                fn ($q) => $q->where('sort_order', '>', $type->sort_order)->orderBy('sort_order'),
            )
            ->first();

        if (! $neighbour) {
            return;
        }

        $order = $type->sort_order;
        $type->update(['sort_order' => $neighbour->sort_order]);
        $neighbour->update(['sort_order' => $order]);

        $this->loadTypes();
    }

    private function uniqueName(string $label): string
    {
        $base = Str::slug($label, '_') ?: 'crew_type';
        $name = $base;
        $i = 2;
        while (CrewType::where('name', $name)->exists()) {
            $name = $base . '_' . $i++;
        }

        return $name;
    }

    public function render()
    {
        return view('livewire.crew-type-manager');
    }
}
