<?php

namespace App\Console\Commands;

use App\Models\BillingRedeemCode;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class GenerateRedeemCodesCommand extends Command
{
    protected $signature = 'vector-decoder:generate-redeem-codes
        {batch : Batch name, e.g. 2026Q1}
        {count=10 : Number of codes}
        {--days=0 : VIP days granted}
        {--credits=0 : Credits granted}
        {--max-uses=1 : Max redemption times per code}
        {--expires= : Expire date, e.g. 2026-12-31}
        {--prefix= : Optional code prefix}
        {--len=16 : Random segment length}
        {--created-by=admin : Operator marker}';

    protected $description = 'Generate card-style redeem codes for membership and credits.';

    public function handle(): int
    {
        $batch = trim((string) $this->argument('batch'));
        $count = max(1, (int) $this->argument('count'));
        $days = max(0, (int) $this->option('days'));
        $credits = max(0, (int) $this->option('credits'));
        $maxUses = (int) $this->option('max-uses');
        $maxUses = $maxUses <= 0 ? null : $maxUses;
        $prefix = strtoupper(trim((string) $this->option('prefix')));
        $len = min(32, max(8, (int) $this->option('len')));
        $createdBy = trim((string) $this->option('created-by')) ?: 'admin';

        if ($days === 0 && $credits === 0) {
            $this->error('Both --days and --credits are zero. At least one must be > 0.');
            return self::FAILURE;
        }

        $expiresAt = null;
        $expiresText = trim((string) $this->option('expires'));
        if ($expiresText !== '') {
            try {
                $expiresAt = Carbon::parse($expiresText);
            } catch (\Throwable) {
                $this->error('Invalid --expires value. Example: 2026-12-31');
                return self::FAILURE;
            }
        }

        $created = [];
        for ($i = 0; $i < $count; $i++) {
            $code = $this->generateUniqueCode($prefix, $len);
            BillingRedeemCode::query()->create([
                'batch_name' => $batch,
                'code' => $code,
                'status' => 'active',
                'days' => $days,
                'credits' => $credits,
                'max_uses' => $maxUses,
                'used_count' => 0,
                'expires_at' => $expiresAt,
                'created_by' => $createdBy,
            ]);
            $created[] = $code;
        }

        $this->info("Generated {$count} redeem codes.");
        $this->line("batch={$batch} days={$days} credits={$credits} max_uses=" . ($maxUses ?? 'null'));
        $this->line('--- codes ---');
        foreach ($created as $code) {
            $this->line($code);
        }
        return self::SUCCESS;
    }

    private function generateUniqueCode(string $prefix, int $len): string
    {
        do {
            $random = strtoupper(Str::random($len));
            $random = preg_replace('/[^A-Z0-9]/', 'A', $random) ?? $random;
            $code = $prefix !== '' ? "{$prefix}-{$random}" : $random;
        } while (BillingRedeemCode::query()->where('code', $code)->exists());

        return $code;
    }
}

