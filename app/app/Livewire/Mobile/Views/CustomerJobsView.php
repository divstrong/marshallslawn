<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Message;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class CustomerJobsView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public string $filter = 'all';
    public ?int $viewingJobId = null;
    public string $newMessage = '';

    public function mount()
    {
        $this->language = session('mobile_app_language', 'en');
    }

    public function getCustomerProperty(): ?Customer
    {
        $customerId = session('mobile_app_user_id');
        return $customerId ? Customer::find($customerId) : null;
    }

    public function getJobsProperty()
    {
        if (!$this->customer) return collect();

        $query = Job::where('customer_id', $this->customer->id)
            ->with(['property', 'crew'])
            ->orderByDesc('scheduled_date');

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        return $query->get();
    }

    public function getViewingJobProperty(): ?Job
    {
        if (!$this->viewingJobId) return null;
        return Job::with(['property', 'crew', 'messages'])->find($this->viewingJobId);
    }

    public function viewJob(int $id)
    {
        $this->viewingJobId = $id;
    }

    public function closeJob()
    {
        $this->viewingJobId = null;
        $this->newMessage = '';
    }

    public function sendMessage()
    {
        if (!$this->viewingJobId || !$this->newMessage || !$this->customer) return;

        Message::create([
            'sender_type' => Customer::class,
            'sender_id' => $this->customer->id,
            'job_id' => $this->viewingJobId,
            'body' => $this->newMessage,
            'channel' => 'app',
        ]);

        $this->newMessage = '';
        session()->flash('success', 'Message sent!');
    }

    public function approveJob(int $id)
    {
        $job = Job::where('customer_id', $this->customer?->id)->find($id);
        if ($job) {
            $job->update(['status' => 'completed']);
            session()->flash('success', 'Job approved!');
        }
    }

    public function render()
    {
        return view('livewire.mobile.views.customer-jobs', [
            't' => $this->translations,
        ]);
    }
}
