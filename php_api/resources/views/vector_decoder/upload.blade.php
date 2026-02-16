<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>上传中心</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Noto+Sans+SC:wght@400;500;700;900&display=swap");

        :root {
            --bg: #eef5fa;
            --ink: #102534;
            --muted: #5a7483;
            --line: #d0dfeb;
            --card: rgba(255, 255, 255, .86);
            --card-strong: rgba(255, 255, 255, .94);
            --accent: #0d9bd8;
            --accent-2: #0ec49d;
            --hot: #ff6e3b;
            --hot-2: #f55a2e;
            --ok: #0a9d66;
            --ok-bg: #ebfbf3;
            --err: #cf5f3b;
            --err-bg: #fff3ee;
            --shadow: 0 22px 46px rgba(19, 72, 95, .16);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: "Noto Sans SC", "Sora", sans-serif;
            background:
                radial-gradient(1000px 520px at -18% -12%, #cde9fb 0%, transparent 62%),
                radial-gradient(960px 520px at 114% 114%, #ffe6d6 0%, transparent 62%),
                repeating-linear-gradient(125deg, rgba(13, 155, 216, .03) 0 2px, transparent 2px 12px),
                linear-gradient(155deg, #f9fcff 0%, #edf5fa 48%, #f6fbff 100%);
        }

        .shell {
            width: min(1180px, calc(100% - 24px));
            margin: 0 auto;
            padding: 26px 0 34px;
        }

        .hero {
            border: 1px solid var(--line);
            border-radius: 24px;
            background:
                linear-gradient(145deg, rgba(255,255,255,.9), rgba(248,252,255,.86));
            box-shadow: var(--shadow);
            padding: 22px;
            position: relative;
            overflow: hidden;
        }

        .hero::before,
        .hero::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            filter: blur(3px);
            pointer-events: none;
        }

        .hero::before {
            width: 260px;
            height: 260px;
            right: -60px;
            top: -90px;
            background: radial-gradient(circle at center, rgba(13, 155, 216, .22) 0%, rgba(13, 155, 216, 0) 72%);
        }

        .hero::after {
            width: 220px;
            height: 220px;
            left: -40px;
            bottom: -90px;
            background: radial-gradient(circle at center, rgba(255, 110, 59, .2) 0%, rgba(255, 110, 59, 0) 76%);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #b7d7eb;
            border-radius: 999px;
            padding: 6px 12px;
            background: #edf7fd;
            color: #137ba8;
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .eyebrow::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 0 5px rgba(19, 123, 168, .14);
            animation: pulse 2s ease infinite;
        }

        h1 {
            margin: 12px 0 8px;
            font-family: "Sora", "Noto Sans SC", sans-serif;
            font-weight: 800;
            font-size: clamp(32px, 5vw, 56px);
            line-height: 1.04;
            letter-spacing: -.02em;
        }

        h1 strong {
            color: var(--hot-2);
            text-shadow: 0 10px 20px rgba(245, 90, 46, .22);
        }

        .lead {
            margin: 0;
            max-width: 760px;
            font-size: 15px;
            line-height: 1.78;
            color: var(--muted);
        }

        .hero-tags {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .hero-tag {
            border: 1px solid #c9ddea;
            border-radius: 999px;
            background: #f4faff;
            color: #315f73;
            font-size: 12px;
            padding: 6px 11px;
        }

        .layout {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1.32fr .96fr;
            gap: 14px;
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--card);
            backdrop-filter: blur(8px);
            box-shadow: 0 14px 32px rgba(20, 68, 89, .11);
            padding: 14px;
        }

        .panel h2 {
            margin: 0 0 10px;
            font-family: "Sora", "Noto Sans SC", sans-serif;
            font-size: 24px;
            letter-spacing: .01em;
        }

        .msg {
            border-radius: 12px;
            margin-bottom: 10px;
            padding: 10px 12px;
            line-height: 1.56;
            font-size: 14px;
        }

        .msg.error {
            border: 1px solid #efc8ba;
            background: var(--err-bg);
            color: var(--err);
        }

        .msg.ok {
            border: 1px solid #bfe6d3;
            background: var(--ok-bg);
            color: var(--ok);
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .chip {
            border: 1px solid #cfe2ec;
            border-radius: 999px;
            background: #f3f9fd;
            color: #3c677a;
            padding: 6px 10px;
            font-size: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .full { grid-column: 1 / -1; }

        .field {
            border: 1px solid #d4e3eb;
            border-radius: 12px;
            background: var(--card-strong);
            padding: 10px;
        }

        .field label {
            display: block;
            margin-bottom: 7px;
            font-size: 12px;
            color: #355f73;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        input[type="file"],
        input[type="number"] {
            width: 100%;
            border: 1px solid #c7dbe7;
            border-radius: 10px;
            background: #fff;
            color: #1a3340;
            padding: 11px;
            font: inherit;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        input:focus {
            outline: none;
            border-color: #28a5d4;
            box-shadow: 0 0 0 3px rgba(40, 165, 212, .16);
        }

        .hint {
            margin-top: 7px;
            font-size: 12px;
            color: #6e8792;
        }

        .actions {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .btn {
            border: 0;
            border-radius: 12px;
            padding: 12px 18px;
            font: inherit;
            font-weight: 800;
            letter-spacing: .06em;
            color: #fff;
            background: linear-gradient(120deg, var(--hot) 0%, var(--hot-2) 42%, #e24747 100%);
            box-shadow: 0 12px 24px rgba(207, 96, 54, .24);
            cursor: pointer;
            transition: transform .16s ease, filter .16s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            filter: saturate(1.08);
        }

        .quiet {
            font-size: 13px;
            color: #65818d;
        }

        .meta-list {
            display: grid;
            gap: 8px;
        }

        .meta-item {
            border: 1px solid #d1e2eb;
            border-radius: 11px;
            background: rgba(255,255,255,.88);
            padding: 9px 10px;
        }

        .meta-label {
            font-size: 12px;
            color: #698591;
        }

        .meta-value {
            margin-top: 4px;
            font-weight: 700;
            color: #173341;
            word-break: break-all;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            border: 1px solid #c7e6d7;
            border-radius: 999px;
            background: #ecfaf3;
            color: #277e59;
            font-size: 12px;
            padding: 6px 11px;
        }

        .pill.warn {
            border-color: #f0cfbf;
            background: #fff4ee;
            color: #af5d3b;
        }

        .link {
            display: inline-block;
            margin-top: 10px;
            color: #0f7ea7;
            text-decoration: none;
            font-weight: 700;
        }

        .link:hover { text-decoration: underline; }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.12); opacity: .72; }
        }

        @media (max-width: 980px) {
            .layout { grid-template-columns: 1fr; }
        }

        @media (max-width: 720px) {
            .shell { width: min(1180px, calc(100% - 18px)); padding: 14px 0 20px; }
            .hero { border-radius: 16px; padding: 12px; }
            .panel { border-radius: 14px; padding: 10px; }
            .form-grid { grid-template-columns: 1fr; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
<main class="shell">
    <section class="hero">
        <span class="eyebrow">Vector Workspace</span>
        <h1>图片上传 <strong>任务中心</strong></h1>
        <p class="lead">请上传图片并提交任务，处理完成后可在任务页面查看与下载结果。</p>
        <div class="hero-tags">
            <span class="hero-tag">极速处理</span>
            <span class="hero-tag">状态可追踪</span>
            <span class="hero-tag">结果可下载</span>
        </div>

        <div class="layout">
            <section class="panel">
                <h2>开始上传</h2>

                @if ($errors->any())
                    <div class="msg error">{{ $errors->first() }}</div>
                @endif
                @if (session('billing_error'))
                    <div class="msg error">{{ session('billing_error') }}</div>
                @endif
                @if (session('billing_success'))
                    <div class="msg ok">{{ session('billing_success') }}</div>
                @endif

                <div class="chips">
                    <span class="chip">上传上限 {{ (int) $uploadMaxKb }} KB</span>
                    <span class="chip">默认等待 {{ (int) $defaultMaxWaitSeconds }} 秒</span>
                    <span class="chip">空闲判定 {{ (int) $defaultIdleSeconds }} 秒</span>
                </div>

                <form method="post" action="{{ route('vector-web.upload.submit') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-grid">
                        <div class="field full">
                            <label for="image">上传图片</label>
                            <input type="file" id="image" name="image" accept="image/*" required>
                            <div class="hint">支持 PNG、JPG、WEBP 等常见图片格式。</div>
                        </div>

                        <div class="field">
                            <label for="width">输出宽度</label>
                            <input type="number" id="width" name="width" value="400" min="1" max="4096">
                        </div>

                        <div class="field">
                            <label for="height">输出高度</label>
                            <input type="number" id="height" name="height" value="400" min="1" max="4096">
                        </div>

                        <div class="field">
                            <label for="max_wait_seconds">最大等待秒数</label>
                            <input type="number" id="max_wait_seconds" name="max_wait_seconds" value="{{ (int) $defaultMaxWaitSeconds }}" min="1" max="300">
                        </div>

                        <div class="field">
                            <label for="idle_seconds">空闲判定秒数</label>
                            <input type="number" id="idle_seconds" name="idle_seconds" value="{{ (int) $defaultIdleSeconds }}" min="1" max="20">
                        </div>
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn">提交任务</button>
                        <span class="quiet">提交后会自动跳转到任务进度页面。</span>
                    </div>
                </form>
            </section>

            <aside class="panel">
                <h2>账户信息</h2>

                @if (!$billingEnabled)
                    <div class="msg ok">当前站点未启用计费，上传无需扣费。</div>
                @else
                    <div class="meta-list">
                        <div class="meta-item">
                            <div class="meta-label">账户编号</div>
                            <div class="meta-value">{{ $billingAccount->account_code }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">会员到期</div>
                            <div class="meta-value">{{ $billingAccount->vip_expires_at ? $billingAccount->vip_expires_at->format('Y-m-d H:i:s') : '未开通' }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">积分余额</div>
                            <div class="meta-value">{{ (int) $billingAccount->balance_credits }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">单次消耗</div>
                            <div class="meta-value">{{ (int) $creditCostPerTask }} 积分</div>
                        </div>
                    </div>

                    <div style="margin-top:10px;">
                        @if ($billingCanUpload)
                            <span class="pill">可上传</span>
                        @else
                            <span class="pill warn">不可上传，请先充值</span>
                        @endif
                    </div>

                    <a class="link" href="{{ route('vector-web.billing.index') }}">前往充值中心</a>
                @endif
            </aside>
        </div>
    </section>
</main>
</body>
</html>
