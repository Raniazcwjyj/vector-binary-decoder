<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\BillingException;
use App\Http\Controllers\Controller;
use App\Models\BillingLedger;
use App\Models\BillingRedeemLog;
use App\Services\BillingService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index(Request $request, BillingService $billingService)
    {
        $account = $billingService->ensureSessionAccount($request);

        return view('vector_decoder.billing', [
            'account' => $account,
            'billingEnabled' => $billingService->isEnabled(),
            'creditCostPerTask' => $billingService->creditCostPerTask(),
            'recentRedeems' => BillingRedeemLog::query()
                ->where('billing_account_id', $account->id)
                ->latest('id')
                ->limit(12)
                ->get(),
            'recentLedgers' => BillingLedger::query()
                ->where('billing_account_id', $account->id)
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function redeem(Request $request, BillingService $billingService)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        try {
            $account = $billingService->ensureSessionAccount($request);
            $result = $billingService->redeemByCode($account, (string) $validated['code']);
        } catch (BillingException $e) {
            return redirect()
                ->route('vector-web.billing.index')
                ->with('billing_error', $e->getMessage());
        }

        $tips = [];
        if ((int) ($result['days'] ?? 0) > 0) {
            $tips[] = '+' . (int) $result['days'] . ' 天会员';
        }
        if ((int) ($result['credits'] ?? 0) > 0) {
            $tips[] = '+' . (int) $result['credits'] . ' 积分';
        }
        if ($tips === []) {
            $tips[] = '卡密已使用';
        }

        return redirect()
            ->route('vector-web.billing.index')
            ->with('billing_success', '兑换成功：' . implode('，', $tips));
    }

    public function bind(Request $request, BillingService $billingService)
    {
        $validated = $request->validate([
            'account_code' => ['required', 'string', 'max:32'],
        ]);

        try {
            $account = $billingService->bindSessionAccountByCode($request, (string) $validated['account_code']);
        } catch (BillingException $e) {
            return redirect()
                ->route('vector-web.billing.index')
                ->with('billing_error', $e->getMessage());
        }

        return redirect()
            ->route('vector-web.billing.index')
            ->with('billing_success', '已切换到账户：' . $account->account_code);
    }
}

