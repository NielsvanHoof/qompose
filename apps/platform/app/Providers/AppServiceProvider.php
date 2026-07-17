<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Ocr\StartsDocumentOcr;
use App\Enums\OcrDriver;
use App\Models\Activity;
use App\Models\Client;
use App\Models\ClientAccessGrant;
use App\Models\DocumentRequest;
use App\Models\Dossier;
use App\Models\QuestionnaireTemplate;
use App\Models\UploadedDocument;
use App\Policies\Audit\ActivityPolicy;
use App\Policies\Clients\ClientPolicy;
use App\Policies\Dossiers\DocumentRequestPolicy;
use App\Policies\Dossiers\DossierPolicy;
use App\Policies\Dossiers\UploadedDocumentPolicy;
use App\Policies\Portal\ClientAccessGrantPolicy;
use App\Policies\Questionnaires\QuestionnaireTemplatePolicy;
use App\Services\Ocr\Configuration\OcrConfigurationValidator;
use App\Services\Ocr\Drivers\OcrDriverFactory;
use Aws\Sqs\SqsClient;
use Aws\Textract\TextractClient;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

use function is_string;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerOcrBindings();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(OcrConfigurationValidator $ocrConfigurationValidator): void
    {
        $ocrConfigurationValidator->validate();
        $this->configurePolicies();
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );

        Model::shouldBeStrict();

        Model::preventLazyLoading(! app()->isProduction());
    }

    /**
     * Bind OCR driver + AWS clients used by Textract start/consume.
     */
    private function registerOcrBindings(): void
    {
        $this->app->singleton(TextractClient::class, function (): TextractClient {
            return new TextractClient($this->awsClientConfig());
        });

        $this->app->singleton(SqsClient::class, function (): SqsClient {
            return new SqsClient($this->awsClientConfig());
        });

        $this->app->bind(
            StartsDocumentOcr::class,
            fn ($app): StartsDocumentOcr => $app->make(OcrDriverFactory::class)->make(
                (string) config('ocr.driver', OcrDriver::Mock->value),
            ),
        );
    }

    /**
     * @return array{version: string, region: string, credentials?: array{key: string, secret: string}}
     */
    private function awsClientConfig(): array
    {
        $key = config('ocr.textract.key');
        $secret = config('ocr.textract.secret');
        $region = config('ocr.textract.region', 'eu-west-1');

        /** @var array{version: string, region: string, credentials?: array{key: string, secret: string}} $clientConfig */
        $clientConfig = [
            'version' => 'latest',
            'region' => is_string($region) && $region !== '' ? $region : 'eu-west-1',
        ];

        if (is_string($key) && $key !== '' && is_string($secret) && $secret !== '') {
            $clientConfig['credentials'] = [
                'key' => $key,
                'secret' => $secret,
            ];
        }

        return $clientConfig;
    }

    private function configurePolicies(): void
    {
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(ClientAccessGrant::class, ClientAccessGrantPolicy::class);
        Gate::policy(DocumentRequest::class, DocumentRequestPolicy::class);
        Gate::policy(Dossier::class, DossierPolicy::class);
        Gate::policy(QuestionnaireTemplate::class, QuestionnaireTemplatePolicy::class);
        Gate::policy(UploadedDocument::class, UploadedDocumentPolicy::class);
    }
}
