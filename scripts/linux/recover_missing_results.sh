#!/usr/bin/env bash
set -euo pipefail

# Recover missing result files for succeeded tasks from engine_output.
# Usage:
#   APP_DIR=/www/wwwroot/vector.533133.xyz ENGINE_DIR=/www/wwwroot/vector-binary-decoder-master/python_engine bash scripts/linux/recover_missing_results.sh

APP_DIR="${APP_DIR:-/www/wwwroot/vector.533133.xyz}"
ENGINE_DIR="${ENGINE_DIR:-/www/wwwroot/vector-binary-decoder-master/python_engine}"

cd "$APP_DIR"

php -r '
require "vendor/autoload.php";
$app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$engineDir=getenv("ENGINE_DIR") ?: "/www/wwwroot/vector-binary-decoder-master/python_engine";
$svc=app(App\Services\TaskResultService::class);
$prefix=trim((string)config("vector_decoder.results_prefix","vector-decoder/results"),"/");
$fixed=0; $missing=0;

foreach(App\Models\ConversionTask::query()->where("status","succeeded")->orderByDesc("created_at")->limit(500)->get() as $t){
  $path=$t->result_path ?: "$prefix/{$t->id}/output.svg";
  if($svc->getSvg($path)!==null){ continue; }

  $src=$engineDir."/engine_output/{$t->id}/output.svg";
  if(!is_file($src)){ echo "MISS {$t->id}\n"; $missing++; continue; }

  $svg=file_get_contents($src);
  $stored=$svc->saveSvg($t->id,$svg);

  $t->result_path=$stored["path"];
  $t->result_svg_size=(int)$stored["size"];
  $t->save();

  echo "FIX {$t->id} => {$stored["path"]}\n";
  $fixed++;
}
echo "DONE fixed={$fixed} missing={$missing}\n";
'
