<?php

namespace App\Services;

use App\Exceptions\BillingException;
use App\Models\BillingAccount;
use App\Models\BillingLedger;
use App\Models\BillingRedeemCode;
use App\Models\BillingRedeemLog;
use App\Models\ConversionTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingService
{
    private const SESSION_KEY_ACCOUNT_ID = 'vector_billing_account_id';

    public function isEnabled(): bool
    {
        return (bool) config('vector_decoder.billing_enabled', true);
    }

    public function creditCostPerTask(): int
    {
        return max(1, (int) config('vector_decoder.billing_credit_cost_per_task', 1));
    }

    public function ensureSessionAccount(Request $request): BillingAccount
    {
        $accountId = (string) $request->session()->get(self::SESSION_KEY_ACCOUNT_ID, '');
        if ($accountId !== '') {
            $account = BillingAccount::query()->find($accountId);
            if ($account instanceof BillingAccount) {
                return $account;
            }
        }

        $account = BillingAccount::query()->create([
            'id' => (string) Str::uuid(),
            'account_code' => $this->generateAccountCode(),
            'balance_credits' => 0,
            'total_spent_credits' => 0,
            'vip_expires_at' => null,
        ]);

        $request->session()->put(self::SESSION_KEY_ACCOUNT_ID, $account->id);
        return $account;
    }

    public function bindSessionAccountByCode(Request $request, string $accountCode): BillingAccount
    {
        $normalized = $this->normalizeTextCode($accountCode);
        if ($normalized === '') {
            throw new BillingException('账户编号不能为空。', 'E_BILLING_ACCOUNT_REQUIRED');
        }

        $account = BillingAccount::query()->where('account_code', $normalized)->first();
        if (!$account) {
            throw new BillingException('未找到该账户编号，请检查后重试。', 'E_BILLING_ACCOUNT_NOT_FOUND');
        }

        $request->session()->put(self::SESSION_KEY_ACCOUNT_ID, $account->id);
        return $account;
    }

    public function canCreateTask(BillingAccount $account): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }
        if ($account->hasVipAccess()) {
            return true;
        }
        return $account->balance_credits >= $this->creditCostPerTask();
    }

    public function assertCanCreateTask(BillingAccount $account): void
    {
        if ($this->canCreateTask($account)) {
            return;
        }

        $cost = $this->creditCostPerTask();
        throw new BillingException("余额不足。当前每次上传需要 {$cost} 积分，请先兑换卡密。", 'E_BILLING_INSUFFICIENT');
    }

    public function chargeForTask(BillingAccount $account, ConversionTask $task): array
    {
        if (!$this->isEnabled()) {
            $task->billing_account_id = $account->id;
            $task->billing_mode = 'free';
            $task->billing_credits_cost = 0;
            $task->billed_at = now();
            $task->save();

            return ['mode' => 'free', 'credits_cost' => 0];
        }

        $cost = $this->creditCostPerTask();

        return DB::transaction(function () use ($account, $task, $cost): array {
            $lockedTask = ConversionTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            if ($lockedTask->billed_at !== null) {
                return [
                    'mode' => (string) ($lockedTask->billing_mode ?? 'unknown'),
                    'credits_cost' => (int) ($lockedTask->billing_credits_cost ?? 0),
                ];
            }

            $lockedAccount = BillingAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();
            if ($lockedAccount->hasVipAccess()) {
                $lockedTask->billing_account_id = $lockedAccount->id;
                $lockedTask->billing_mode = 'vip';
                $lockedTask->billing_credits_cost = 0;
                $lockedTask->billed_at = now();
                $lockedTask->save();

                $this->syncTaskBillingFields($task, $lockedTask);
                return ['mode' => 'vip', 'credits_cost' => 0];
            }

            if ($lockedAccount->balance_credits < $cost) {
                throw new BillingException("余额不足。当前每次上传需要 {$cost} 积分，请先兑换卡密。", 'E_BILLING_INSUFFICIENT');
            }

            $lockedAccount->balance_credits -= $cost;
            $lockedAccount->total_spent_credits += $cost;
            $lockedAccount->save();

            BillingLedger::query()->create([
                'billing_account_id' => $lockedAccount->id,
                'task_id' => $lockedTask->id,
                'type' => 'consume',
                'credits_delta' => -$cost,
                'balance_after' => $lockedAccount->balance_credits,
                'note' => '任务预扣积分',
                'meta_json' => ['task_id' => $lockedTask->id],
            ]);

            $lockedTask->billing_account_id = $lockedAccount->id;
            $lockedTask->billing_mode = 'credit';
            $lockedTask->billing_credits_cost = $cost;
            $lockedTask->billed_at = now();
            $lockedTask->save();

            $this->syncTaskBillingFields($task, $lockedTask);
            return ['mode' => 'credit', 'credits_cost' => $cost];
        });
    }

    public function refundTaskOnFailure(ConversionTask $task, string $reason): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }
        if ($task->billing_mode !== 'credit') {
            return false;
        }
        if ((int) $task->billing_credits_cost <= 0) {
            return false;
        }
        if ($task->refunded_at !== null) {
            return false;
        }
        if (!is_string($task->billing_account_id) || $task->billing_account_id === '') {
            return false;
        }

        return DB::transaction(function () use ($task, $reason): bool {
            $lockedTask = ConversionTask::query()->whereKey($task->id)->lockForUpdate()->first();
            if (!$lockedTask) {
                return false;
            }
            if ($lockedTask->billing_mode !== 'credit' || $lockedTask->refunded_at !== null) {
                return false;
            }

            $cost = (int) ($lockedTask->billing_credits_cost ?? 0);
            if ($cost <= 0 || !is_string($lockedTask->billing_account_id) || $lockedTask->billing_account_id === '') {
                return false;
            }

            $lockedAccount = BillingAccount::query()->whereKey($lockedTask->billing_account_id)->lockForUpdate()->first();
            if (!$lockedAccount) {
                return false;
            }

            $lockedAccount->balance_credits += $cost;
            $lockedAccount->total_spent_credits = max(0, (int) $lockedAccount->total_spent_credits - $cost);
            $lockedAccount->save();

            BillingLedger::query()->create([
                'billing_account_id' => $lockedAccount->id,
                'task_id' => $lockedTask->id,
                'type' => 'refund',
                'credits_delta' => $cost,
                'balance_after' => $lockedAccount->balance_credits,
                'note' => '任务失败自动退款',
                'meta_json' => ['reason' => $reason],
            ]);

            $lockedTask->refunded_at = now();
            $lockedTask->save();

            $task->refunded_at = $lockedTask->refunded_at;
            return true;
        });
    }

    public function redeemByCode(BillingAccount $account, string $rawCode): array
    {
        if (!$this->isEnabled()) {
            throw new BillingException('计费系统未启用。', 'E_BILLING_DISABLED');
        }

        $codeText = $this->normalizeTextCode($rawCode);
        if ($codeText === '') {
            throw new BillingException('卡密不能为空。', 'E_REDEEM_CODE_REQUIRED');
        }

        return DB::transaction(function () use ($account, $codeText): array {
            $code = BillingRedeemCode::query()->where('code', $codeText)->lockForUpdate()->first();
            if (!$code) {
                throw new BillingException('卡密不存在，请检查输入。', 'E_REDEEM_CODE_NOT_FOUND');
            }
            if ($code->status !== 'active') {
                throw new BillingException('该卡密已失效或已使用。', 'E_REDEEM_CODE_USED');
            }
            if ($code->expires_at && $code->expires_at->isPast()) {
                throw new BillingException('该卡密已过期。', 'E_REDEEM_CODE_EXPIRED');
            }
            if ($code->max_uses !== null && $code->used_count >= $code->max_uses) {
                $code->status = 'used';
                $code->save();
                throw new BillingException('该卡密已达到使用次数上限。', 'E_REDEEM_CODE_USED');
            }
            if ((int) $code->days <= 0 && (int) $code->credits <= 0) {
                throw new BillingException('该卡密配置无效，请联系管理员。', 'E_REDEEM_INVALID');
            }

            $lockedAccount = BillingAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();

            $days = (int) $code->days;
            $credits = (int) $code->credits;

            if ($days > 0) {
                $start = $lockedAccount->vip_expires_at && $lockedAccount->vip_expires_at->isFuture()
                    ? $lockedAccount->vip_expires_at->copy()
                    : now();
                $lockedAccount->vip_expires_at = $start->addDays($days);
            }

            if ($credits > 0) {
                $lockedAccount->balance_credits += $credits;
            }

            $lockedAccount->save();

            if ($credits > 0) {
                BillingLedger::query()->create([
                    'billing_account_id' => $lockedAccount->id,
                    'task_id' => null,
                    'type' => 'redeem',
                    'credits_delta' => $credits,
                    'balance_after' => $lockedAccount->balance_credits,
                    'note' => '卡密充值',
                    'meta_json' => ['code' => $code->code, 'days' => $days],
                ]);
            }

            BillingRedeemLog::query()->create([
                'billing_account_id' => $lockedAccount->id,
                'billing_redeem_code_id' => $code->id,
                'code' => $code->code,
                'days' => $days,
                'credits' => $credits,
                'meta_json' => ['batch_name' => $code->batch_name],
            ]);

            $code->used_count += 1;
            $code->last_redeemed_at = now();
            if ($code->max_uses !== null && $code->used_count >= $code->max_uses) {
                $code->status = 'used';
            }
            $code->save();

            return [
                'days' => $days,
                'credits' => $credits,
                'balance_credits' => (int) $lockedAccount->balance_credits,
                'vip_expires_at' => $lockedAccount->vip_expires_at,
            ];
        });
    }

    private function generateAccountCode(): string
    {
        do {
            $candidate = 'VC' . strtoupper(Str::random(10));
        } while (BillingAccount::query()->where('account_code', $candidate)->exists());

        return $candidate;
    }

    private function normalizeTextCode(string $text): string
    {
        $text = strtoupper(trim($text));
        return preg_replace('/[^A-Z0-9\\-]/', '', $text) ?? '';
    }

    private function syncTaskBillingFields(ConversionTask $target, ConversionTask $source): void
    {
        $target->billing_account_id = $source->billing_account_id;
        $target->billing_mode = $source->billing_mode;
        $target->billing_credits_cost = $source->billing_credits_cost;
        $target->billed_at = $source->billed_at;
        $target->refunded_at = $source->refunded_at;
    }
}

