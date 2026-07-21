<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\Localization\ResolveApplicationLocaleAction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function __construct(
        private readonly ResolveApplicationLocaleAction $resolveApplicationLocale,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolveApplicationLocale->handle($request));

        return $next($request);
    }
}
