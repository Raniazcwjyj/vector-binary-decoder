<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>任务进度 - {{ $task->id }}</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Noto+Sans+SC:wght@400;500;700;900&display=swap");

        :root {
            --ink: #102534;
            --muted: #5d7987;
            --line: #d1e1ec;
            --card: rgba(255, 255, 255, .88);
            --queue: #cf8a3e;
            --run: #0c9ad2;
            --ok: #109f67;
            --err: #c64a4a;
            --shadow: 0 22px 46px rgba(18, 68, 90, .14);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: "Noto Sans SC", "Sora", sans-serif;
            background:
                radial-gradient(980px 520px at -18% -10%, #d2ecff 0%, transparent 62%),
                radial-gradient(900px 520px at 115% 110%, #ffe7d7 0%, transparent 62%),
                linear-gradient(158deg, #f9fcff 0%, #edf5fa 46%, #f7fbff 100%);
        }

        .shell {
            width: min(980px, calc(100% - 22px));
            margin: 0 auto;
            padding: 26px 0 30px;
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 22px;
            background: var(--card);
            box-shadow: var(--shadow);
            padding: 16px;
            position: relative;
            overflow: hidden;
        }

        .panel::before,
        .panel::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(2px);
        }

        .panel::before {
            width: 220px;
            height: 220px;
            right: -60px;
            top: -90px;
            background: radial-gradient(circle at center, rgba(12, 154, 210, .2), rgba(12, 154, 210, 0) 72%);
        }

        .panel::after {
            width: 220px;
            height: 220px;
            left: -65px;
            bottom: -95px;
            background: radial-gradient(circle at center, rgba(255, 110, 59, .18), rgba(255, 110, 59, 0) 74%);
        }

        h1 {
            margin: 0 0 8px;
            font-family: "Sora", "Noto Sans SC", sans-serif;
            font-size: clamp(30px, 4.6vw, 46px);
            line-height: 1.06;
            letter-spacing: -.01em;
        }

        .task-id {
            margin: 0 0 12px;
            color: var(--muted);
            font-size: 14px;
        }

        code {
            border: 1px solid #d1e1eb;
            border-radius: 8px;
            background: #f2f9fd;
            color: #1f3b48;
            padding: 2px 7px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            word-break: break-all;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            border: 1px solid #d0e2ea;
            background: #f8fcff;
            color: #446774;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            padding: 7px 11px;
        }

        .pill::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 0 5px rgba(68, 103, 116, .12);
        }

        .pill[data-state="queued"] { color: var(--queue); }
        .pill[data-state="running"] { color: var(--run); }
        .pill[data-state="succeeded"] { color: var(--ok); }
        .pill[data-state="failed"] { color: var(--err); }

        .meter {
            border: 1px solid #d4e4ec;
            border-radius: 999px;
            background: #edf6fb;
            overflow: hidden;
            height: 14px;
            margin-bottom: 10px;
        }

        .meter > i {
            display: block;
            width: 5%;
            height: 100%;
            background: linear-gradient(102deg, #2fb4de 0%, #18b5b0 52%, #48c878 100%);
            background-size: 220% 100%;
            animation: flow 1.2s linear infinite;
            transition: width .3s ease;
        }

        .meter > i.done { animation: none; }

        .meta {
            min-height: 22px;
            color: #627f8c;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .error {
            display: none;
            border: 1px solid #eecbcb;
            border-radius: 12px;
            background: #fff4f4;
            color: #b54d4d;
            line-height: 1.6;
            font-size: 14px;
            padding: 10px 12px;
            margin-bottom: 10px;
            white-space: pre-wrap;
        }

        .links {
            display: none;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }

        .btn {
            text-decoration: none;
            border: 1px solid #cde0ea;
            border-radius: 11px;
            background: #f6fcff;
            color: #24596f;
            font-weight: 700;
            letter-spacing: .03em;
            padding: 10px 14px;
            transition: border-color .16s ease, background-color .16s ease;
        }

        .btn:hover {
            border-color: #95bfd2;
            background: #ecf8fd;
        }

        .nav {
            margin-top: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .nav a {
            color: #0d7ea6;
            text-decoration: none;
            font-weight: 700;
        }

        .nav a.kami {
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 6px 10px;
            color: #fff;
            background: linear-gradient(120deg, #ff7d44 0%, #f45a2e 45%, #df3c3c 100%);
            box-shadow: 0 10px 20px rgba(217, 95, 57, .2);
        }

        .nav a:hover { text-decoration: underline; }

        .helper {
            margin-top: 12px;
            border: 1px solid #d3e3ed;
            border-radius: 12px;
            background: #f5fbff;
            color: #355e72;
            line-height: 1.6;
            font-size: 13px;
            padding: 10px 12px;
        }

        .helper a {
            color: #0d7ea6;
            font-weight: 700;
            text-decoration: none;
        }

        .helper a:hover { text-decoration: underline; }

        @keyframes flow {
            from { background-position: 0 50%; }
            to { background-position: 220% 50%; }
        }

        @media (max-width: 720px) {
            .shell { width: min(980px, calc(100% - 16px)); padding: 14px 0 20px; }
            .panel { border-radius: 16px; padding: 12px; }
        }
    </style>
</head>
<body>
@php
    $kamiUrl = route('vector-web.kami');
    $statusLabels = [
        'queued' => '排队中',
        'running' => '处理中',
        'succeeded' => '已完成',
        'failed' => '失败',
    ];
@endphp
<main class="shell">
    <section class="panel">
        <h1>任务监控台</h1>
        <p class="task-id">任务 ID：<code>{{ $task->id }}</code></p>

        <div class="row">
            <div class="pill" id="statusPill" data-state="{{ $task->status }}">{{ $statusLabels[$task->status] ?? $task->status }}</div>
            @if ($task->billing_mode)
                <div class="pill">计费：{{ strtoupper((string) $task->billing_mode) }}</div>
            @endif
            @if ((int) $task->billing_credits_cost > 0)
                <div class="pill">消耗：{{ (int) $task->billing_credits_cost }}</div>
            @endif
            @if ($task->refunded_at)
                <div class="pill">已退款</div>
            @endif
        </div>

        <div class="meter">
            <i id="progressBar"></i>
        </div>

        <div class="meta" id="metaText"></div>
        <div class="error" id="errorBox"></div>

        <div class="links" id="resultLinks">
            <a class="btn" id="openSvg" href="#" target="_blank" rel="noopener">在线查看结果</a>
            <a class="btn" id="downloadSvg" href="#" target="_blank" rel="noopener">下载文件</a>
        </div>

        <div class="nav">
            <a href="{{ route('vector-web.upload') }}">返回上传页</a>
            <a href="{{ route('vector-web.billing.index') }}">充值中心</a>
            <a class="kami" href="{{ $kamiUrl }}" target="_blank" rel="noopener">购买卡密</a>
        </div>

        <div class="helper">
            处理失败或暂时不可上传时，可先前往 <a href="{{ route('vector-web.billing.index') }}">充值中心</a> 查看账户状态，
            或直接 <a href="{{ $kamiUrl }}" target="_blank" rel="noopener">购买卡密</a> 后再重试。
        </div>
    </section>
</main>

<script>
(() => {
    const statusPill = document.getElementById('statusPill');
    const progressBar = document.getElementById('progressBar');
    const resultLinks = document.getElementById('resultLinks');
    const openSvg = document.getElementById('openSvg');
    const downloadSvg = document.getElementById('downloadSvg');
    const errorBox = document.getElementById('errorBox');
    const metaText = document.getElementById('metaText');
    const statusUrl = @json(route('vector-web.tasks.status', ['task_id' => $task->id], false));
    const statusLabels = {
        queued: '排队中',
        running: '处理中',
        succeeded: '已完成',
        failed: '失败',
    };

    const setState = (state) => {
        statusPill.dataset.state = state;
        statusPill.textContent = statusLabels[state] || state;
    };

    const poll = async () => {
        try {
            const res = await fetch(statusUrl, { credentials: 'same-origin' });
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }

            const data = await res.json();
            errorBox.style.display = 'none';
            errorBox.textContent = '';

            setState(data.status);
            progressBar.style.width = (data.progress || 0) + '%';
            progressBar.classList.toggle('done', data.status === 'succeeded' || data.status === 'failed');

            if (data.status === 'running') {
                const elapsed = data.meta && data.meta.elapsed_ms ? Math.max(1, Math.round(data.meta.elapsed_ms / 1000)) : null;
                metaText.textContent = elapsed ? `处理中，已用时约 ${elapsed} 秒` : '处理中，请稍候...';
            } else if (data.status === 'queued') {
                metaText.textContent = '任务已进入队列，正在等待处理。';
            } else if (data.status === 'succeeded') {
                metaText.textContent = '处理完成，可查看或下载结果。';
            } else {
                metaText.textContent = '';
            }

            if (data.status === 'succeeded' && data.result_url) {
                resultLinks.style.display = 'flex';
                const raw = String(data.result_url || '');
                const normalized = raw.startsWith('http://')
                    ? raw.replace(/^http:\/\//i, 'https://')
                    : (raw.startsWith('/') ? raw : ('/' + raw));
                openSvg.href = normalized;
                downloadSvg.href = normalized + (normalized.includes('?') ? '&' : '?') + 'download=1';
                return;
            }

            if (data.status === 'failed') {
                errorBox.style.display = 'block';
                errorBox.textContent = (data.error_code || 'E_FAILED') + ': ' + (data.error_message || '任务失败');
                return;
            }

            if ((data.status === 'queued' || data.status === 'running') && data.error_code) {
                errorBox.style.display = 'block';
                errorBox.textContent = '系统重试中：' + data.error_code + (data.error_message ? ' - ' + data.error_message : '');
            }
        } catch (e) {
            errorBox.style.display = 'block';
            errorBox.textContent = '状态查询失败：' + (e && e.message ? e.message : e);
        }

        setTimeout(poll, 1800);
    };

    poll();
})();
</script>
</body>
</html>
