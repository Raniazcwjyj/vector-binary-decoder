#!/usr/bin/env bash
set -euo pipefail

# Requeue tasks left in queued state (for example when redis was down).
# Usage:
#   APP_DIR=/www/wwwroot/vector.533133.xyz bash scripts/linux/requeue_pending_tasks.sh

APP_DIR="${APP_DIR:-/www/wwwroot/vector.533133.xyz}"
cd "$APP_DIR"

php -r '
require "vendor/autoload.php";
$app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$q=config("vector_decoder.queue_name","conversions");
$n=0;
foreach(App\Models\ConversionTask::query()->where("status","queued")->whereNull("started_at")->orderByDesc("created_at")->limit(500)->get() as $t){
  App\Jobs\CaptureConvertJob::dispatch($t->id)->onQueue($q);
  $n++;
}
echo "requeued={$n}\n";
'
