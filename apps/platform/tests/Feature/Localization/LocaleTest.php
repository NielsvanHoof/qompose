<?php

declare(strict_types=1);

use App\Enums\AuditEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('locale is resolved from the authenticated user preference', function () {
    $user = User::factory()->create(['locale' => 'nl']);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'nl')
            ->where('translations.Settings', 'Instellingen'));
});

test('locale is resolved from the locale cookie when the user has no preference', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'nl')
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'nl')
            ->where('translations.Dashboard', 'Dashboard'));
});

test('locale is resolved from the accept language header as a fallback', function () {
    $this->withHeaders(['Accept-Language' => 'nl-NL,nl;q=0.9,en;q=0.8'])
        ->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'nl')
            ->where('translations.Save', 'Opslaan'));
});

test('available locales are shared with inertia', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('available_locales', [
                ['code' => 'en', 'label' => 'English'],
                ['code' => 'nl', 'label' => 'Nederlands'],
            ]));
});

test('authenticated users can update their preferred language', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->patch(route('language.update'), ['locale' => 'nl'])
        ->assertRedirect(route('language.edit'))
        ->assertPlainCookie('locale', 'nl');

    expect($user->fresh()->locale)->toBe('nl');
});

test('language settings page can be rendered', function () {
    $this->withoutVite();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('language.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/language'));
});

test('locale update requires a supported locale', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('language.update'), ['locale' => 'fr'])
        ->assertSessionHasErrors('locale');
});

test('questionnaire completed notification message is translated for dutch locale', function () {
    app()->setLocale('nl');

    expect(__(':client finished the questionnaire for “:dossier”.', [
        'client' => 'Jane Client',
        'dossier' => '2025 Payroll',
    ]))->toBe('Jane Client heeft de vragenlijst voor “2025 Payroll” afgerond.');
});

test('auth page translation keys are shared with inertia for guests', function () {
    $this->withHeaders(['Accept-Language' => 'nl-NL,nl;q=0.9'])
        ->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'nl')
            ->where('translations.Log in', 'Inloggen')
            ->where('translations.Remember me', 'Onthoud mij')
            ->where('translations.Secure document exchange', 'Veilige documentuitwisseling'));
});

test('audit event labels are translated for the active locale', function () {
    app()->setLocale('nl');

    expect(AuditEvent::DossierViewed->label())->toBe('Dossier bekeken')
        ->and(AuditEvent::DocumentUploaded->label())->toBe('Document geüpload')
        ->and(AuditEvent::AccessDenied->label())->toBe('Toegang geweigerd');
});

test('media and workspace translation keys are shared with inertia', function () {
    $user = User::factory()->create(['locale' => 'nl']);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('translations.All documents', 'Alle documenten')
            ->where('translations.Recent activity', 'Recente activiteit')
            ->where('translations.Create firm', 'Kantoor aanmaken')
            ->where('translations.Pending', 'In afwachting'));
});

test('client dashboard and questionnaire translation keys are shared with inertia', function () {
    $user = User::factory()->create(['locale' => 'nl']);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('translations.All clients', 'Alle klanten')
            ->where('translations.Create client', 'Klant aanmaken')
            ->where('translations.Review queue', 'Beoordelingswachtrij')
            ->where('translations.Awaiting client', 'Wacht op klant')
            ->where('translations.File upload', 'Bestandsupload')
            ->where('translations.Yes / no', 'Ja / nee')
            ->where('translations.Draft', 'Concept')
            ->where('translations.In review', 'In beoordeling'));
});
