<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Vector 上传中心</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;800&family=Noto+Sans+SC:wght@400;500;700;900&display=swap");

        :root {
            --bg: #09131a;
            --panel: rgba(12, 29, 40, .78);
            --panel-2: rgba(10, 23, 32, .9);
            --line: rgba(146, 203, 232, .24);
            --ink: #ecfbff;
            --muted: #9ebdc8;
            --hot: #ff8a1f;
            --hot-2: #ff5b1f;
            --cool: #32d0ff;
            --green: #3ee58f;
            --warn-bg: rgba(255, 130, 58, .12);
            --warn-line: rgba(255, 170, 95, .45);
            --ok-bg: rgba(62, 229, 143, .14);
            --ok-line: rgba(80, 236, 158, .44);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: "Noto Sans SC", sans-serif;
            background:
                radial-gradient(1000px 580px at -8% -12%, rgba(35, 162, 230, .24), transparent 60%),
                radial-gradient(980px 540px at 110% 115%, rgba(255, 130, 31, .2), transparent 62%),
                linear-gradient(145deg, #050a0f 0%, #08131d 35%, #060b12 100%);
        }

        .shell {
            max-width: 1220px;
            margin: 0 auto;
            padding: 24px 16px 42px;
        }

        .top {
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 16px 16px 14px;
            background:
                linear-gradient(130deg, rgba(26, 51, 71, .55), rgba(10, 24, 35, .82)),
                repeating-linear-gradient(
                    -35deg,
                    rgba(89, 191, 238, .07) 0 2px,
                    transparent 2px 10px
                );
            box-shadow: 0 24px 50px rgba(0, 0, 0, .38);
        }

        .headline {
            margin: 0 0 12px;
            font-family: "Orbitron", "Noto Sans SC", sans-serif;
            font-size: clamp(28px, 4.8vw, 52px);
            line-height: 1.06;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .headline strong {
            color: var(--hot);
            text-shadow: 0 0 18px rgba(255, 138, 31, .45);
        }

        .lead {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
            max-width: 920px;
            font-size: 15px;
        }

        .grid {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1.45fr .95fr;
            gap: 14px;
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--panel);
            backdrop-filter: blur(8px);
            padding: 14px;
        }

        .card h2, .card h3 {
            margin: 0 0 10px;
            font-family: "Orbitron", "Noto Sans SC", sans-serif;
            letter-spacing: .02em;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .full { grid-column: 1 / -1; }

        .field {
            border: 1px solid rgba(145, 204, 233, .24);
            border-radius: 12px;
            background: rgba(10, 22, 31, .72);
            padding: 10px;
        }

        .field label {
            display: block;
            margin-bottom: 7px;
            color: #97d8f2;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        input[type="file"],
        input[type="number"],
        input[type="text"] {
            width: 100%;
            border: 1px solid rgba(129, 189, 219, .35);
            border-radius: 10px;
            background: rgba(2, 10, 16, .72);
            color: #ebf8ff;
            padding: 11px 12px;
            font: inherit;
        }

        input:focus {
            outline: none;
            border-color: var(--cool);
            box-shadow: 0 0 0 3px rgba(50, 208, 255, .2);
        }

        .hint {
            margin-top: 7px;
            color: #88a8b4;
            font-size: 12px;
            line-height: 1.45;
        }

        .btn {
            border: 0;
            border-radius: 11px;
            padding: 12px 16px;
            font: inherit;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #fff;
            cursor: pointer;
            background: linear-gradient(118deg, var(--hot) 0%, var(--hot-2) 44%, #f12c7e 100%);
            box-shadow: 0 12px 20px rgba(216, 74, 31, .28);
            transition: transform .14s ease, filter .14s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            filter: saturate(1.12);
        }

        .cta-line {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .quiet {
            color: #97afba;
            font-size: 13px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            border: 1px solid rgba(134, 208, 241, .35);
            background: rgba(8, 22, 31, .76);
            padding: 6px 10px;
            font-size: 12px;
            color: #9fd4ea;
            margin-right: 6px;
            margin-bottom: 6px;
        }

        .pill.ok {
            border-color: var(--ok-line);
            background: var(--ok-bg);
            color: #8ff1bf;
        }

        .pill.warn {
            border-color: var(--warn-line);
            background: var(--warn-bg);
            color: #ffc38f;
        }

        .msg {
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 10px;
            line-height: 1.55;
            font-size: 14px;
        }

        .msg.error {
            border: 1px solid var(--warn-line);
            background: var(--warn-bg);
            color: #ffbf8e;
        }

        .msg.ok {
            border: 1px solid var(--ok-line);
            background: var(--ok-bg);
            color: #9ef4c5;
        }

        .meta-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-top: 8px;
        }

        .meta-item {
            border-radius: 10px;
            border: 1px solid rgba(144, 204, 234, .24);
            background: rgba(8, 18, 26, .68);
            padding: 9px 10px;
        }

        .meta-label {
            color: #8aa6b3;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .meta-value {
            color: #f1fbff;
            font-weight: 700;
            word-break: break-all;
        }

        a.link {
            color: #7cd4ff;
            text-decoration: none;
            font-weight: 700;
        }

        a.link:hover { text-decoration: underline; }

        @media (max-width: 980px) {
            .grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 720px) {
            .shell { padding: 14px 10px 24px; }
            .top { border-radius: 16px; padding: 10px; }
            .card { border-radius: 14px; padding: 10px; }
            .form-grid { grid-template-columns: 1fr; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
<main class="shell">
    <section class="top">
        <h1 class="headline">Vector <strong>Capture</strong> Upload</h1>
        <p class="lead">
            上传图片后系统会自动抓取向量数据并生成 SVG。现在已接入卡密计费：支持会员时长与积分，失败任务自动退款，适合直接商业化运营。
        </p>

        <div class="grid">
            <section class="card">
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

                <div class="pill">上传上限：{{ (int) $uploadMaxKb }} KB</div>
                <div class="pill">默认等待：{{ (int) $defaultMaxWaitSeconds }} 秒</div>
                <div class="pill">空闲判定：{{ (int) $defaultIdleSeconds }} 秒</div>

                <form method="post" action="{{ route('vector-web.upload.submit') }}" enctype="multipart/form-data" style="margin-top:10px;">
                    @csrf
                    <div class="form-grid">
                        <div class="field full">
                            <label for="image">上传图片</label>
                            <input type="file" id="image" name="image" accept="image/*" required>
                            <div class="hint">支持 PNG/JPG/WebP 等常见格式。</div>
                        </div>

                        <div class="field">
                            <label for="width">SVG 宽度</label>
                            <input type="number" id="width" name="width" value="400" min="1" max="4096">
                        </div>

                        <div class="field">
                            <label for="height">SVG 高度</label>
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

                    <div class="cta-line">
                        <button type="submit" class="btn">提交任务</button>
                        <span class="quiet">任务创建后会自动跳转到状态页。</span>
                    </div>
                </form>
            </section>

            <aside class="card" style="background: var(--panel-2);">
                <h3>计费账户</h3>

                @if (!$billingEnabled)
                    <div class="msg ok">当前站点已关闭计费，上传不扣积分。</div>
                @else
                    <div class="meta-list">
                        <div class="meta-item">
                            <div class="meta-label">账户编号</div>
                            <div class="meta-value">{{ $billingAccount->account_code }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">会员到期</div>
                            <div class="meta-value">
                                {{ $billingAccount->vip_expires_at ? $billingAccount->vip_expires_at->format('Y-m-d H:i:s') : '未开通' }}
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">积分余额</div>
                            <div class="meta-value">{{ (int) $billingAccount->balance_credits }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">单次消耗</div>
                            <div class="meta-value">{{ (int) $creditCostPerTask }} 积分/任务（会员期内免费）</div>
                        </div>
                    </div>

                    <div style="margin-top:10px;">
                        @if ($billingCanUpload)
                            <span class="pill ok">可上传</span>
                        @else
                            <span class="pill warn">不可上传：请先兑换卡密</span>
                        @endif
                    </div>

                    <div style="margin-top:12px;">
                        <a class="link" href="{{ route('vector-web.billing.index') }}">打开充值中心</a>
                    </div>
                @endif
            </aside>
        </div>
    </section>
</main>
</body>
</html>

