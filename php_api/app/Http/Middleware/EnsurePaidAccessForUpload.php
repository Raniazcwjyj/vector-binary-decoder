<?php

namespace App\Http\Middleware;

use App\Services\BillingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePaidAccessForUpload
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var BillingService $billingService */
        $billingService = app(BillingService::class);

        if (!(bool) config('vector_decoder.billing_enabled', true)) {
            return $next($request);
        }
        if (!(bool) config('vector_decoder.billing_enforce_web_upload', true)) {
            return $next($request);
        }

        $account = $billingService->ensureSessionAccount($request);
        if ($billingService->canCreateTask($account)) {
            return $next($request);
        }

        return redirect()
            ->route('vector-web.billing.index')
            ->with('billing_error', '当前账号无可用会员或积分，请先兑换卡密后再上传。');
    }
}
