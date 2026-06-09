<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\MarketingCampaign;
use App\Services\MarketingCampaignSender;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Marketing campaign builder + manager (embedded on the Admin > Marketing page).
 *
 * Create/manage HTML email campaigns, pick one of three templates, edit the
 * content/links/imagery, and send to selected customers and/or tag groups.
 */
class MarketingCampaigns extends Component
{
    /** 'list' or 'edit'. */
    public string $mode = 'list';

    public ?int $campaignId = null;

    // --- Editor fields ---
    public string $name = '';
    public string $subject = '';
    public string $template = 'announcement';

    public string $headline = '';
    public string $body = '';
    public string $imageUrl = '';
    public string $buttonLabel = '';
    public string $buttonUrl = '';
    public string $footerNote = '';

    /** @var array<int, string> */
    public array $recipientTags = [];

    /** @var array<int, int> */
    public array $recipientCustomerIds = [];

    public string $customerSearch = '';

    public ?string $flash = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'template' => ['required', 'in:' . implode(',', array_keys(MarketingCampaign::TEMPLATES))],
            'headline' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'imageUrl' => ['nullable', 'url', 'max:2048'],
            'buttonUrl' => ['nullable', 'url', 'max:2048'],
        ];
    }

    #[Computed]
    public function campaigns()
    {
        return MarketingCampaign::query()
            ->orderByDesc('id')
            ->get();
    }

    /** @return array<int, string> */
    #[Computed]
    public function availableTags(): array
    {
        return Customer::query()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    #[Computed]
    public function customerResults()
    {
        $search = trim($this->customerSearch);
        if (strlen($search) < 2) {
            return collect();
        }

        return Customer::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(15)
            ->get(['id', 'first_name', 'last_name', 'company_name', 'email']);
    }

    #[Computed]
    public function selectedCustomers()
    {
        if ($this->recipientCustomerIds === []) {
            return collect();
        }

        return Customer::query()
            ->whereIn('id', $this->recipientCustomerIds)
            ->get(['id', 'first_name', 'last_name', 'company_name', 'email']);
    }

    #[Computed]
    public function recipientCount(): int
    {
        return app(MarketingCampaignSender::class)
            ->recipientsQuery($this->recipientTags, $this->recipientCustomerIds)
            ->count();
    }

    #[Computed]
    public function previewHtml(): string
    {
        $campaign = $this->draftCampaign();

        return app(MarketingCampaignSender::class)->render($campaign, null);
    }

    public function newCampaign(): void
    {
        $this->reset([
            'campaignId', 'name', 'subject', 'headline', 'body', 'imageUrl',
            'buttonLabel', 'buttonUrl', 'footerNote', 'recipientTags',
            'recipientCustomerIds', 'customerSearch', 'flash',
        ]);
        $this->template = 'announcement';
        $this->headline = 'A message from Marshall\'s Lawn';
        $this->body = "Hi {first_name},\n\nThanks for being a valued customer. Here's what's new this season.";
        $this->buttonLabel = 'Learn more';
        $this->footerNote = "You're receiving this because you're a Marshall's Lawn customer.";
        $this->mode = 'edit';
    }

    public function editCampaign(int $id): void
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $content = $campaign->content ?? [];

        $this->campaignId = $campaign->id;
        $this->name = $campaign->name;
        $this->subject = $campaign->subject ?? '';
        $this->template = $campaign->template ?: 'announcement';
        $this->headline = $content['headline'] ?? '';
        $this->body = $content['body'] ?? '';
        $this->imageUrl = $content['image_url'] ?? '';
        $this->buttonLabel = $content['button_label'] ?? '';
        $this->buttonUrl = $content['button_url'] ?? '';
        $this->footerNote = $content['footer_note'] ?? '';
        $this->recipientTags = $campaign->recipient_tags ?? [];
        $this->recipientCustomerIds = $campaign->recipient_customer_ids ?? [];
        $this->flash = null;
        $this->mode = 'edit';
    }

    public function setTemplate(string $template): void
    {
        if (array_key_exists($template, MarketingCampaign::TEMPLATES)) {
            $this->template = $template;
        }
    }

    public function toggleTag(string $tag): void
    {
        if (in_array($tag, $this->recipientTags, true)) {
            $this->recipientTags = array_values(array_filter($this->recipientTags, fn ($t) => $t !== $tag));
        } else {
            $this->recipientTags[] = $tag;
        }
    }

    public function addCustomer(int $id): void
    {
        if (! in_array($id, $this->recipientCustomerIds, true)) {
            $this->recipientCustomerIds[] = $id;
        }
        $this->customerSearch = '';
    }

    public function removeCustomer(int $id): void
    {
        $this->recipientCustomerIds = array_values(array_filter($this->recipientCustomerIds, fn ($c) => $c !== $id));
    }

    public function save(): ?int
    {
        $this->validate();

        $campaign = MarketingCampaign::updateOrCreate(
            ['id' => $this->campaignId],
            [
                'name' => $this->name,
                'subject' => $this->subject,
                'template' => $this->template,
                'content' => $this->contentArray(),
                'recipient_tags' => array_values($this->recipientTags),
                'recipient_customer_ids' => array_values($this->recipientCustomerIds),
                'status' => $this->campaignId
                    ? (MarketingCampaign::find($this->campaignId)?->status ?? 'draft')
                    : 'draft',
            ],
        );

        $this->campaignId = $campaign->id;

        return $campaign->id;
    }

    public function saveDraft(): void
    {
        $this->save();
        $this->flash = 'Campaign saved as draft.';
        $this->mode = 'list';
    }

    public function sendCampaign(): void
    {
        $this->save();

        if ($this->recipientCount === 0) {
            $this->addError('recipients', 'Select at least one customer or tag with a valid email address.');

            return;
        }

        $campaign = MarketingCampaign::findOrFail($this->campaignId);
        $count = app(MarketingCampaignSender::class)->send($campaign);

        $this->flash = "Campaign sent to {$count} recipient(s).";
        $this->mode = 'list';
    }

    public function deleteCampaign(int $id): void
    {
        MarketingCampaign::whereKey($id)->delete();
        $this->flash = 'Campaign deleted.';
    }

    public function backToList(): void
    {
        $this->mode = 'list';
    }

    /**
     * @return array<string, string>
     */
    private function contentArray(): array
    {
        return [
            'headline' => $this->headline,
            'body' => $this->body,
            'image_url' => $this->imageUrl,
            'button_label' => $this->buttonLabel,
            'button_url' => $this->buttonUrl,
            'footer_note' => $this->footerNote,
        ];
    }

    /** Build an unsaved campaign instance mirroring the editor (for preview). */
    private function draftCampaign(): MarketingCampaign
    {
        return new MarketingCampaign([
            'subject' => $this->subject,
            'template' => $this->template,
            'content' => $this->contentArray(),
        ]);
    }

    public function render()
    {
        return view('livewire.marketing-campaigns');
    }
}
