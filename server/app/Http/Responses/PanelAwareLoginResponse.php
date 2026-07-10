<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * The admin and customer panels share one session, and therefore one
 * `url.intended` key. Filament's stock LoginResponse calls
 * `redirect()->intended()` unconditionally, so a customer who first landed on
 * an admin URL (which stashed `url.intended`) would authenticate on the portal
 * and then get bounced straight back to the admin panel — where the admin
 * guard promptly rejects them.
 *
 * This response only honours `url.intended` when the stashed URL belongs to the
 * panel the user actually just signed in to.
 */
class PanelAwareLoginResponse implements LoginResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        $intended = session()->pull('url.intended');

        if (is_string($intended) && $this->panelOwningUrl($intended)?->getId() === $panel->getId()) {
            return redirect()->to($intended);
        }

        return redirect()->to($panel->getUrl());
    }

    /**
     * Resolve which registered panel serves a given URL, matching on the longest
     * path prefix so `/portal/...` wins over a root-mounted panel at `/`.
     */
    protected function panelOwningUrl(string $url): ?Panel
    {
        $path = trim(parse_url($url, PHP_URL_PATH) ?: '', '/');

        $match = null;
        $matchedLength = -1;

        foreach (Filament::getPanels() as $panel) {
            $panelPath = trim($panel->getPath(), '/');

            $owns = $panelPath === ''
                ? true
                : ($path === $panelPath || str_starts_with($path, $panelPath . '/'));

            if ($owns && strlen($panelPath) > $matchedLength) {
                $match = $panel;
                $matchedLength = strlen($panelPath);
            }
        }

        return $match;
    }
}
