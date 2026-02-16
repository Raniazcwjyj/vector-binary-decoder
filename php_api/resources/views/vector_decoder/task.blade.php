<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>任务追踪 - {{ $task->id }}</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;800&family=Noto+Sans+SC:wght@400;500;700;900&display=swap");

        :root {
            --bg: #08131a;
            --panel: rgba(12, 30, 41, .86);
            --line: rgba(142, 199, 227, .25);
            --ink: #ecfbff;
            --muted: #9abdc9;
            --run: #38d0ff;
            --ok: #41e997;
            --queue: #ffb155;
            --err: #ff6a6a;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: "Noto Sans SC", sans-serif;
            background:
                radial-gradient(980px 580px at -12% -8%, rgba(50, 208, 255, .2), transparent 60%),
                radial-gradient(920px 560px at 112% 115%, rgba(255, 110, 64, .15), transparent 60%),
                linear-gradient(140deg, #04090d 0%, #08141d 40%, #060a11 100%);
        }

        .shell {
            max-width: 980px;
            margin: 0 auto;
            padding: 24px 14px 36px;
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 22px;
            background: var(--panel);
            box-shadow: 0 24px 52px rgba(0, 0, 0, .35);
            padding: 16px;
        }

        h1 {
            margin: 0 0 10px;
            font-family: "Orbitron", "Noto Sans SC", sans-serif;
            font-size: clamp(28px, 4.2vw, 46px);
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .task-id {
            color: var(--muted);
            margin: 0 0 12px;
            font-size: 14px;
        }

        code {
            border: 1px solid rgba(136, 199, 231, .35);
            background: rgba(8, 20, 28, .74);
            color: #def4ff;
            border-radius: 8px;
            padding: 2px 7px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            word-break: break-all;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            border: 1px solid rgba(138, 196, 226, .32);
            background: rgba(7, 19, 27, .74);
            padding: 8px 12px;
            font-family: "Orbitron", "Noto Sans SC", sans-serif;
            font-size: 12px;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .pill::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 0 4px color-mix(in srgb, currentColor 18%, transparent);
        }

        .pill[data-state="queued"] { color: var(--queue); }
        .pill[data-state="running"] { color: var(--run); }
        .pill[data-state="succeeded"] { color: var(--ok); }
        .pill[data-state="failed"] { color: var(--err); }

        .meter {
            border: 1px solid rgba(138, 196, 226, .25);
            border-radius: 999px;
            background: rgba(8, 20, 28, .74);
            overflow: hidden;
            height: 14px;
            margin-bottom: 12px;
        }

        .meter > i {
            display: block;
            width: 5%;
            height: 100%;
            background: linear-gradient(104deg, #39d1ff 0%, #2fd3ad 45%, #7de765 100%);
            background-size: 220% 100%;
            animation: flow 1.2s linear infinite;
            transition: width .3s ease;
        }

        .meter > i.done {
            animation: none;
        }

        .meta {
            min-height: 22px;
            color: #95b3bf;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .error {
            display: none;
            border-radius: 12px;
            border: 1px solid rgba(255, 129, 129, .45);
            background: rgba(255, 97, 97, .12);
            color: #ffb4b4;
            padding: 10px 12px;
            margin-bottom: 10px;
            line-height: 1.6;
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
            border-radius: 11px;
            border: 1px solid rgba(138, 198, 228, .35);
            background: rgba(9, 22, 31, .85);
            color: #a6ddf5;
            padding: 10px 14px;
            font-weight: 700;
            letter-spacing: .03em;
        }

        .btn:hover {
            border-color: rgba(166, 221, 245, .65);
        }

        .nav {
            margin-top: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .nav a {
            color: #8ad6ff;
            text-decoration: none;
            font-weight: 700;
        }

        .nav a:hover { text-decoration: underline; }

        @keyframes flow {
            from { background-position: 0 50%; }
            to { background-position: 220% 50%; }
        }
    </style>
</head>
<body>
<main class="shell">
    <section class="panel">
        <h1>Task Monitor</h1>
        <p class="task-id">任务 ID：<code>{{ $task->id }}</code></p>

        <div class="row">
            <div class="pill" id="statusPill" data-state="{{ $task->status }}">{{ $task->status }}</div>
            @if ($task->billing_mode)
                <div class="pill">billing: {{ $task->billing_mode }}</div>
            @endif
            @if ((int) $task->billing_credits_cost > 0)
                <div class="pill">cost: {{ (int) $task->billing_credits_cost }}</div>
            @endif
            @if ($task->refunded_at)
                <div class="pill">refunded</div>
            @endif
        </div>

        <div class="meter">
            <i id="progressBar"></i>
        </div>

        <div class="meta" id="metaText"></div>
        <div class="error" id="errorBox"></div>

        <div class="links" id="resultLinks">
            <a class="btn" id="openSvg" href="#" target="_blank" rel="noopener">在线查看 SVG</a>
            <a class="btn" id="downloadSvg" href="#" target="_blank" rel="noopener">下载 SVG</a>
        </div>

        <div class="nav">
            <a href="{{ route('vector-web.upload') }}">返回上传页</a>
            <a href="{{ route('vector-web.billing.index') }}">充值中心</a>
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

    const setState = (state) => {
        statusPill.dataset.state = state;
        statusPill.textContent = state;
    };

    const poll = async () => {
        try {
            const res = await fetch(statusUrl, { credentials: 'same-origin' });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();

            errorBox.style.display = 'none';
            errorBox.textContent = '';

            setState(data.status);
            progressBar.style.width = (data.progress || 0) + '%';
            progressBar.classList.toggle('done', data.status === 'succeeded' || data.status === 'failed');

            if (data.meta) {
                const chunks = data.meta.chunks_used ?? '-';
                const loops = data.meta.loops ?? '-';
                const shapes = data.meta.shapes ?? '-';
                metaText.textContent = `统计：chunks=${chunks}，loops=${loops}，shapes=${shapes}`;
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
                errorBox.textContent = '重试中: ' + data.error_code + ': ' + (data.error_message || '-');
            }
        } catch (e) {
            errorBox.style.display = 'block';
            errorBox.textContent = '状态查询失败: ' + (e && e.message ? e.message : e);
        }
        setTimeout(poll, 1800);
    };

    poll();
})();
</script>
</body>
</html>

