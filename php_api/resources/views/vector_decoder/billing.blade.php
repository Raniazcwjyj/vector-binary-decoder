<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>充值中心</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;800&family=Noto+Sans+SC:wght@400;500;700;900&display=swap");

        :root {
            --bg: #09131a;
            --panel: rgba(12, 30, 41, .84);
            --line: rgba(143, 200, 228, .25);
            --ink: #effbff;
            --muted: #9ab9c5;
            --ok: #45ea9a;
            --warn: #ffca89;
            --err: #ff9b9b;
            --cool: #54d7ff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: "Noto Sans SC", sans-serif;
            background:
                radial-gradient(900px 520px at -10% -10%, rgba(55, 210, 255, .2), transparent 60%),
                radial-gradient(980px 540px at 110% 120%, rgba(255, 140, 70, .16), transparent 62%),
                linear-gradient(150deg, #04090e 0%, #07131b 45%, #050912 100%);
        }

        .shell {
            max-width: 1200px;
            margin: 0 auto;
            padding: 22px 14px 40px;
        }

        .head h1 {
            margin: 0 0 8px;
            font-family: "Orbitron", "Noto Sans SC", sans-serif;
            font-size: clamp(28px, 4.2vw, 46px);
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .head p {
            margin: 0 0 14px;
            color: var(--muted);
            line-height: 1.7;
        }

        .grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 12px;
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 16px;
            background: var(--panel);
            padding: 12px;
        }

        h2 {
            margin: 0 0 10px;
            font-family: "Orbitron", "Noto Sans SC", sans-serif;
            font-size: 20px;
            letter-spacing: .02em;
        }

        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .stat {
            border: 1px solid rgba(145, 202, 231, .24);
            border-radius: 11px;
            background: rgba(8, 19, 28, .72);
            padding: 9px 10px;
        }

        .stat b {
            display: block;
            margin-top: 3px;
            font-size: 15px;
            word-break: break-all;
        }

        .stat span {
            color: #89a7b2;
            font-size: 12px;
        }

        .msg {
            border-radius: 11px;
            padding: 10px 12px;
            margin-bottom: 10px;
            font-size: 14px;
            line-height: 1.55;
        }

        .msg.ok {
            border: 1px solid rgba(69, 234, 154, .45);
            background: rgba(69, 234, 154, .14);
            color: #a4f3ca;
        }

        .msg.err {
            border: 1px solid rgba(255, 156, 156, .45);
            background: rgba(255, 110, 110, .12);
            color: #ffc1c1;
        }

        .form {
            margin-top: 10px;
            border: 1px solid rgba(146, 202, 230, .24);
            border-radius: 12px;
            background: rgba(9, 21, 29, .72);
            padding: 10px;
        }

        .form label {
            display: block;
            margin-bottom: 7px;
            color: #8fd3ee;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        input[type="text"] {
            width: 100%;
            border: 1px solid rgba(131, 192, 223, .35);
            border-radius: 10px;
            background: rgba(3, 11, 17, .8);
            color: #eefaff;
            padding: 11px;
            font: inherit;
        }

        input:focus {
            outline: none;
            border-color: var(--cool);
            box-shadow: 0 0 0 3px rgba(84, 215, 255, .18);
        }

        .actions {
            margin-top: 9px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
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
            background: linear-gradient(118deg, #33b7f5 0%, #216cf4 48%, #6743e3 100%);
            cursor: pointer;
        }

        .hint {
            color: #8ba8b5;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th, td {
            border-bottom: 1px solid rgba(142, 199, 227, .17);
            padding: 8px 6px;
            text-align: left;
        }

        th {
            color: #9ccde4;
            font-weight: 700;
            font-size: 12px;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        td {
            color: #e9f8ff;
            vertical-align: top;
        }

        .delta.plus { color: var(--ok); }
        .delta.minus { color: var(--warn); }

        .nav {
            margin-top: 14px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .nav a {
            color: #8bd7ff;
            text-decoration: none;
            font-weight: 700;
        }

        .nav a:hover { text-decoration: underline; }

        @media (max-width: 980px) {
            .grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 700px) {
            .stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<main class="shell">
    <section class="head">
        <h1>Billing Center</h1>
        <p>使用卡密为当前账户充值会员时长或积分。上传任务时系统会自动识别：会员期内免费，否则按积分计费，失败任务自动退款。</p>
    </section>

    @if (session('billing_success'))
        <div class="msg ok">{{ session('billing_success') }}</div>
    @endif
    @if (session('billing_error'))
        <div class="msg err">{{ session('billing_error') }}</div>
    @endif
    @if ($errors->any())
        <div class="msg err">{{ $errors->first() }}</div>
    @endif

    <section class="grid">
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
                    <span class="hint">兑换后立即生效。</span>
                </div>
            </form>

            <form class="form" method="post" action="{{ route('vector-web.billing.bind') }}">
                @csrf
                <label for="account_code">切换到账户编号</label>
                <input id="account_code" name="account_code" type="text" maxlength="32" placeholder="输入已有账户编号，跨设备恢复用">
                <div class="actions">
                    <button type="submit" class="btn">切换账户</button>
                    <span class="hint">建议保存当前账户编号，避免清理浏览器后丢失。</span>
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

