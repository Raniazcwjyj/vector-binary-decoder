<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>充值中心</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Noto+Sans+SC:wght@400;500;700;900&display=swap");

        :root {
            --ink: #112634;
            --muted: #5f7988;
            --line: #d2e1ec;
            --card: rgba(255, 255, 255, .88);
            --card-strong: rgba(255, 255, 255, .95);
            --accent: #0d99d7;
            --accent-2: #0db5a2;
            --hot: #ff6f3c;
            --ok: #109f67;
            --ok-bg: #e9fbf2;
            --err: #c35656;
            --err-bg: #fff2f2;
            --shadow: 0 22px 46px rgba(18, 69, 90, .15);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: "Noto Sans SC", "Sora", sans-serif;
            background:
                radial-gradient(980px 520px at -15% -12%, #d3edff 0%, transparent 62%),
                radial-gradient(900px 520px at 116% 112%, #ffe7d8 0%, transparent 62%),
                repeating-linear-gradient(128deg, rgba(13, 153, 215, .03) 0 2px, transparent 2px 11px),
                linear-gradient(157deg, #f9fcff 0%, #edf5fa 47%, #f7fbff 100%);
        }

        .shell {
            width: min(1200px, calc(100% - 24px));
            margin: 0 auto;
            padding: 24px 0 34px;
        }

        .head {
            border: 1px solid var(--line);
            border-radius: 24px;
            background: linear-gradient(145deg, rgba(255,255,255,.9), rgba(248,252,255,.86));
            box-shadow: var(--shadow);
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .head::before,
        .head::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(2px);
        }

        .head::before {
            width: 250px;
            height: 250px;
            right: -75px;
            top: -90px;
            background: radial-gradient(circle at center, rgba(13, 153, 215, .2), rgba(13, 153, 215, 0) 72%);
        }

        .head::after {
            width: 220px;
            height: 220px;
            left: -58px;
            bottom: -85px;
            background: radial-gradient(circle at center, rgba(255, 111, 60, .16), rgba(255, 111, 60, 0) 74%);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #b8d7ea;
            border-radius: 999px;
            background: #ecf7fd;
            color: #127ea9;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            padding: 6px 12px;
        }

        .eyebrow::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 0 5px rgba(18, 126, 169, .14);
        }

        h1 {
            margin: 12px 0 8px;
            font-family: "Sora", "Noto Sans SC", sans-serif;
            font-weight: 800;
            font-size: clamp(30px, 4.8vw, 50px);
            line-height: 1.05;
            letter-spacing: -.01em;
        }

        .lead {
            margin: 0;
            max-width: 760px;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.74;
        }

        .alerts {
            margin-top: 12px;
            display: grid;
            gap: 8px;
        }

        .msg {
            border-radius: 12px;
            padding: 10px 12px;
            line-height: 1.55;
            font-size: 14px;
        }

        .msg.ok {
            border: 1px solid #bce5d1;
            background: var(--ok-bg);
            color: var(--ok);
        }

        .msg.err {
            border: 1px solid #efc9c9;
            background: var(--err-bg);
            color: var(--err);
        }

        .layout {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1.16fr .84fr;
            gap: 14px;
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 18px;
            background: var(--card);
            box-shadow: 0 14px 32px rgba(20, 69, 90, .11);
            padding: 14px;
        }

        .card h2 {
            margin: 0 0 10px;
            font-family: "Sora", "Noto Sans SC", sans-serif;
            font-size: 22px;
            letter-spacing: .01em;
        }

        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .stat {
            border: 1px solid #d1e2ec;
            border-radius: 11px;
            background: var(--card-strong);
            padding: 9px 10px;
        }

        .stat span {
            color: #698593;
            font-size: 12px;
        }

        .stat b {
            display: block;
            margin-top: 3px;
            color: #183340;
            font-size: 15px;
            word-break: break-all;
        }

        .form {
            margin-top: 10px;
            border: 1px solid #d2e2ec;
            border-radius: 12px;
            background: var(--card-strong);
            padding: 10px;
        }

        .form label {
            display: block;
            margin-bottom: 7px;
            color: #366173;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        input[type="text"] {
            width: 100%;
            border: 1px solid #c8dce7;
            border-radius: 10px;
            background: #fff;
            color: #1b3543;
            padding: 11px;
            font: inherit;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        input:focus {
            outline: none;
            border-color: #2aa4d2;
            box-shadow: 0 0 0 3px rgba(42, 164, 210, .16);
        }

        .actions {
            margin-top: 9px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .btn {
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            font: inherit;
            font-weight: 700;
            letter-spacing: .03em;
            color: #fff;
            background: linear-gradient(120deg, var(--accent) 0%, #1498f0 48%, var(--accent-2) 100%);
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(36, 130, 198, .22);
        }

        .hint {
            color: #68818f;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th, td {
            border-bottom: 1px solid #d6e4ed;
            padding: 8px 6px;
            text-align: left;
        }

        th {
            color: #5d7c8b;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        td {
            color: #1d3a48;
            vertical-align: top;
            word-break: break-word;
        }

        .delta.plus { color: var(--ok); }
        .delta.minus { color: #c98634; }

        .nav {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .nav a {
            color: #0d7ea6;
            text-decoration: none;
            font-weight: 700;
        }

        .nav a:hover { text-decoration: underline; }

        @media (max-width: 980px) {
            .layout { grid-template-columns: 1fr; }
        }

        @media (max-width: 700px) {
            .shell { width: min(1200px, calc(100% - 16px)); padding: 14px 0 20px; }
            .head { border-radius: 16px; padding: 12px; }
            .card { border-radius: 14px; padding: 10px; }
            .stats { grid-template-columns: 1fr; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
<main class="shell">
    <section class="head">
        <span class="eyebrow">Billing Console</span>
        <h1>充值中心</h1>
        <p class="lead">在这里管理账户、兑换卡密与查看记录。页面仅展示与你当前账户相关的信息。</p>

        <div class="alerts">
            @if (!$billingEnabled)
                <div class="msg ok">当前站点未启用计费，以下充值功能仅作展示。</div>
            @endif
            @if (session('billing_success'))
                <div class="msg ok">{{ session('billing_success') }}</div>
            @endif
            @if (session('billing_error'))
                <div class="msg err">{{ session('billing_error') }}</div>
            @endif
            @if ($errors->any())
                <div class="msg err">{{ $errors->first() }}</div>
            @endif
        </div>
    </section>

    <section class="layout">
        <article class="card">
            <h2>当前账户</h2>
            <div class="stats">
                <div class="stat">
                    <span>账户编号</span>
                    <b>{{ $account->account_code }}</b>
                </div>
                <div class="stat">
                    <span>会员到期</span>
                    <b>{{ $account->vip_expires_at ? $account->vip_expires_at->format('Y-m-d H:i:s') : '未开通' }}</b>
                </div>
                <div class="stat">
                    <span>积分余额</span>
                    <b>{{ (int) $account->balance_credits }}</b>
                </div>
                <div class="stat">
                    <span>单次任务消耗</span>
                    <b>{{ (int) $creditCostPerTask }} 积分</b>
                </div>
            </div>

            <form class="form" method="post" action="{{ route('vector-web.billing.redeem') }}">
                @csrf
                <label for="code">兑换卡密</label>
                <input id="code" name="code" type="text" maxlength="64" placeholder="输入卡密，例如 VC-ABCD1234EFGH5678">
                <div class="actions">
                    <button type="submit" class="btn">立即兑换</button>
                    <span class="hint">兑换成功后会立即更新账户状态。</span>
                </div>
            </form>

            <form class="form" method="post" action="{{ route('vector-web.billing.bind') }}">
                @csrf
                <label for="account_code">切换到账户编号</label>
                <input id="account_code" name="account_code" type="text" maxlength="32" placeholder="输入已有账户编号，用于跨设备恢复">
                <div class="actions">
                    <button type="submit" class="btn">切换账户</button>
                    <span class="hint">建议保存当前账户编号，避免更换设备后丢失。</span>
                </div>
            </form>
        </article>

        <aside class="card">
            <h2>最近充值记录</h2>
            <table>
                <thead>
                <tr>
                    <th>时间</th>
                    <th>卡密</th>
                    <th>会员天数</th>
                    <th>积分</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($recentRedeems as $row)
                    <tr>
                        <td>{{ $row->created_at?->format('m-d H:i') }}</td>
                        <td>{{ $row->code }}</td>
                        <td>{{ (int) $row->days }}</td>
                        <td>{{ (int) $row->credits }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">暂无记录</td></tr>
                @endforelse
                </tbody>
            </table>
        </aside>
    </section>

    <section class="card" style="margin-top:12px;">
        <h2>积分流水</h2>
        <table>
            <thead>
            <tr>
                <th>时间</th>
                <th>类型</th>
                <th>变动</th>
                <th>余额</th>
                <th>备注</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($recentLedgers as $row)
                <tr>
                    <td>{{ $row->created_at?->format('m-d H:i') }}</td>
                    <td>{{ $row->type }}</td>
                    <td class="delta {{ (int) $row->credits_delta >= 0 ? 'plus' : 'minus' }}">
                        {{ (int) $row->credits_delta >= 0 ? '+' : '' }}{{ (int) $row->credits_delta }}
                    </td>
                    <td>{{ (int) $row->balance_after }}</td>
                    <td>{{ $row->note ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">暂无流水</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <div class="nav">
        <a href="{{ route('vector-web.upload') }}">返回上传页</a>
    </div>
</main>
</body>
</html>
