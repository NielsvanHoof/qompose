<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LocaleUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class LocaleController extends Controller
{
    /**
     * Show the user's language settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/language');
    }

    /**
     * Update the user's preferred language.
     */
    public function update(LocaleUpdateRequest $request): RedirectResponse
    {
        $locale = $request->validated('locale');
        $user = $request->authenticatedUser();

        $user->update(['locale' => $locale]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Language updated.')]);

        return to_route('language.edit')
            ->cookie('locale', $locale, 60 * 24 * 365);
    }
}
