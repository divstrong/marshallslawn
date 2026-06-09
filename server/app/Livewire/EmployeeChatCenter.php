<?php

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Models\Employee;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Admin chat center (Communication > Messages). Search an employee's chat thread,
 * open it, and review the full history / reply — the same foreman <-> office
 * conversation surfaced on the Dispatch board, available on its own page.
 */
class EmployeeChatCenter extends Component
{
    use WithFileUploads;

    public string $search = '';

    public ?int $selectedEmployeeId = null;

    public string $body = '';

    public $attachment = null;

    public function selectEmployee(int $id): void
    {
        $this->selectedEmployeeId = $id;
        $this->body = '';

        // Opening a thread marks the foreman's messages as read.
        ChatMessage::query()
            ->where('employee_id', $id)
            ->where('sender', ChatMessage::SENDER_FOREMAN)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        unset($this->messages, $this->selectedEmployee, $this->threads);
    }

    public function clearSelection(): void
    {
        $this->selectedEmployeeId = null;
    }

    /**
     * Thread list. With no search: employees who have a conversation, newest
     * activity first. With a search: any active employee matching the query, so
     * the office can start a fresh thread too.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function threads(): array
    {
        $search = trim($this->search);

        if ($search !== '') {
            $employees = Employee::query()
                ->where('status', 'active')
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->limit(30)
                ->get();
        } else {
            $orderedIds = ChatMessage::query()
                ->selectRaw('employee_id, MAX(created_at) as last_at')
                ->groupBy('employee_id')
                ->orderByDesc('last_at')
                ->pluck('employee_id')
                ->all();

            if ($orderedIds === []) {
                return [];
            }

            $employees = Employee::query()
                ->whereIn('id', $orderedIds)
                ->get()
                ->sortBy(fn (Employee $e) => array_search($e->id, $orderedIds, true))
                ->values();
        }

        return $employees->map(function (Employee $employee) {
            $last = ChatMessage::query()
                ->where('employee_id', $employee->id)
                ->latest()
                ->first();

            $unread = ChatMessage::query()
                ->where('employee_id', $employee->id)
                ->where('sender', ChatMessage::SENDER_FOREMAN)
                ->whereNull('read_at')
                ->count();

            return [
                'id' => (int) $employee->id,
                'name' => $this->displayName($employee),
                'role' => $employee->role,
                'initials' => $this->initials($employee),
                'last_preview' => $last
                    ? ($last->body ?: '[' . ($last->attachment_type ?? 'attachment') . ']')
                    : null,
                'last_at' => $last?->created_at?->diffForHumans(),
                'unread' => $unread,
            ];
        })->all();
    }

    #[Computed]
    public function selectedEmployee(): ?array
    {
        if (! $this->selectedEmployeeId) {
            return null;
        }

        $employee = Employee::find($this->selectedEmployeeId);
        if (! $employee) {
            return null;
        }

        return [
            'id' => (int) $employee->id,
            'name' => $this->displayName($employee),
            'role' => $employee->role,
            'initials' => $this->initials($employee),
            'phone' => $employee->mobile_phone ?: $employee->phone,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function messages(): array
    {
        if (! $this->selectedEmployeeId) {
            return [];
        }

        return ChatMessage::query()
            ->with('senderUser:id,name')
            ->where('employee_id', $this->selectedEmployeeId)
            ->orderBy('created_at')
            ->limit(500)
            ->get()
            ->map(fn (ChatMessage $message) => [
                'id' => (int) $message->id,
                'sender' => $message->sender,
                'sender_name' => $message->sender === ChatMessage::SENDER_OFFICE
                    ? ($message->senderUser?->name ?? 'Office')
                    : 'Foreman',
                'body' => $message->body,
                'attachment_type' => $message->attachment_type,
                'attachment_url' => $message->attachmentUrl(),
                'attachment_name' => $message->attachment_name,
                'time' => $message->created_at?->format('M j, g:i A'),
            ])
            ->all();
    }

    public function send(): void
    {
        $body = trim($this->body);
        if (! $this->selectedEmployeeId || $body === '') {
            return;
        }

        ChatMessage::create([
            'employee_id' => $this->selectedEmployeeId,
            'sender' => ChatMessage::SENDER_OFFICE,
            'sender_user_id' => auth()->id(),
            'body' => $body,
        ]);

        $this->body = '';
        unset($this->messages, $this->threads);
        $this->dispatch('chat-center:updated');
    }

    public function updatedAttachment(): void
    {
        if (! $this->selectedEmployeeId || ! $this->attachment) {
            return;
        }

        $file = $this->attachment;
        $mime = (string) $file->getMimeType();
        $type = str_starts_with($mime, 'video/')
            ? 'video'
            : (str_starts_with($mime, 'image/') ? 'photo' : 'file');

        $path = $file->store('chat-media', 'public');

        ChatMessage::create([
            'employee_id' => $this->selectedEmployeeId,
            'sender' => ChatMessage::SENDER_OFFICE,
            'sender_user_id' => auth()->id(),
            'attachment_type' => $type,
            'attachment_disk' => 'public',
            'attachment_path' => $path,
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_mime' => $mime,
            'attachment_size' => $file->getSize(),
        ]);

        $this->attachment = null;
        unset($this->messages, $this->threads);
        $this->dispatch('chat-center:updated');
    }

    private function displayName(Employee $employee): string
    {
        return trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''))
            ?: ($employee->name ?? 'Employee');
    }

    private function initials(Employee $employee): string
    {
        $initials = strtoupper(
            substr($employee->first_name ?? '', 0, 1) . substr($employee->last_name ?? '', 0, 1)
        );

        if ($initials === '') {
            $initials = strtoupper(substr($employee->name ?? '?', 0, 2));
        }

        return $initials ?: '?';
    }

    public function render()
    {
        return view('livewire.employee-chat-center');
    }
}
