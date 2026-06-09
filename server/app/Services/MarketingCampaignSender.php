<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MarketingCampaign;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

/**
 * Renders and sends HTML marketing campaigns (campaign builder feature).
 *
 * Recipients are resolved from explicit customer ids plus customer tag groups;
 * each email's content is personalised per recipient before rendering the
 * chosen template.
 */
class MarketingCampaignSender
{
    /** Content fields that support {first_name} / {last_name} / {company} tokens. */
    private const PERSONALISED_FIELDS = ['headline', 'body', 'footer_note'];

    /**
     * Customers that should receive a campaign: anyone explicitly selected or
     * carrying one of the selected tags, restricted to those with an email.
     *
     * @param  array<int, string>  $tags
     * @param  array<int, int>  $customerIds
     */
    public function recipientsQuery(array $tags, array $customerIds): Builder
    {
        $tags = array_values(array_filter($tags));
        $customerIds = array_values(array_filter(array_map('intval', $customerIds)));

        $query = Customer::query()
            ->whereNotNull('email')
            ->where('email', '!=', '');

        if ($tags === [] && $customerIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $sub) use ($tags, $customerIds): void {
            if ($customerIds !== []) {
                $sub->orWhereIn('id', $customerIds);
            }
            foreach ($tags as $tag) {
                $sub->orWhereJsonContains('tags', $tag);
            }
        });
    }

    /**
     * Render a campaign's HTML for a specific customer (or a generic preview
     * when $customer is null).
     */
    public function render(MarketingCampaign $campaign, ?Customer $customer = null): string
    {
        $template = array_key_exists((string) $campaign->template, MarketingCampaign::TEMPLATES)
            ? $campaign->template
            : 'announcement';

        $content = $campaign->content ?? [];

        foreach (self::PERSONALISED_FIELDS as $field) {
            if (! empty($content[$field])) {
                $content[$field] = $this->personalise($content[$field], $customer);
            }
        }

        return View::make("emails.marketing.{$template}", ['content' => $content])->render();
    }

    /**
     * Send the campaign to all resolved recipients and mark it sent.
     *
     * @return int  Number of recipients emailed.
     */
    public function send(MarketingCampaign $campaign): int
    {
        $recipients = $this->recipientsQuery(
            $campaign->recipient_tags ?? [],
            $campaign->recipient_customer_ids ?? [],
        )->get();

        $sent = 0;
        foreach ($recipients as $customer) {
            $html = $this->render($campaign, $customer);

            Mail::html($html, function ($message) use ($customer, $campaign): void {
                $message->to($customer->email)->subject($campaign->subject);
            });

            $sent++;
        }

        $campaign->update([
            'status' => 'sent',
            'sent_at' => now(),
            'recipient_count' => $sent,
            'html_content' => $this->render($campaign, null),
        ]);

        return $sent;
    }

    /**
     * Replace personalisation tokens. With no customer (preview) sample values
     * are used so the merge fields are visible.
     */
    private function personalise(string $text, ?Customer $customer): string
    {
        $first = $customer?->first_name ?: ($customer ? '' : 'Jordan');
        $last = $customer?->last_name ?: ($customer ? '' : 'Smith');
        $company = $customer?->company_name ?: ($customer ? '' : 'Acme Co.');
        $name = trim("{$first} {$last}");

        return strtr($text, [
            '{first_name}' => $first ?: 'there',
            '{last_name}' => $last,
            '{company}' => $company,
            '{name}' => $name ?: 'there',
        ]);
    }
}
