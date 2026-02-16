<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>购买卡密</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Noto+Sans+SC:wght@400;500;700;900&display=swap");

        :root {
            --ink: #102534;
            --muted: #5f7887;
            --line: #d2e1ec;
            --card: rgba(255, 255, 255, .9);
            --accent: #0d9bd8;
            --accent-2: #13bca0;
            --hot: #ff7b44;
            --hot-2: #ef5734;
            --shadow: 0 24px 48px rgba(16, 68, 90, .16);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: "Noto Sans SC", "Sora", sans-serif;
            background:
                radial-gradient(1050px 560px at -15% -15%, #d1ecff 0%, transparent 62%),
                radial-gradient(980px 560px at 115% 115%, #ffe6d7 0%, transparent 62%),
                repeating-linear-gradient(126deg, rgba(13, 155, 216, .03) 0 2px, transparent 2px 11px),
                linear-gradient(156deg, #f9fcff 0%, #edf5fa 47%, #f7fbff 100%);
        }

        .shell {
            width: min(980px, calc(100% - 22px));
            margin: 0 auto;
            padding: 26px 0 30px;
        }

        .hero {
            border: 1px solid var(--line);
            border-radius: 24px;
            background: var(--card);
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
            pointer-events: none;
            filter: blur(2px);
        }

        .hero::before {
            width: 280px;
            height: 280px;
            right: -90px;
            top: -110px;
            background: radial-gradient(circle at center, rgba(13, 155, 216, .24), rgba(13, 155, 216, 0) 70%);
        }

        .hero::after {
            width: 250px;
            height: 250px;
            left: -85px;
            bottom: -120px;
            background: radial-gradient(circle at center, rgba(255, 123, 68, .22), rgba(255, 123, 68, 0) 74%);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #b8d8eb;
            border-radius: 999px;
            background: #edf8ff;
            color: #137da8;
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
            box-shadow: 0 0 0 5px rgba(19, 125, 168, .14);
        }

        h1 {
            margin: 12px 0 8px;
            font-family: "Sora", "Noto Sans SC", sans-serif;
            font-size: clamp(32px, 5vw, 56px);
            line-height: 1.03;
            letter-spacing: -.02em;
        }

        .lead {
            margin: 0;
            max-width: 760px;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.72;
        }

        .main-grid {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1.08fr .92fr;
            gap: 14px;
        }

        .panel {
            border: 1px solid #d3e2ec;
            border-radius: 16px;
            background: rgba(255, 255, 255, .94);
            padding: 14px;
        }

        .panel h2 {
            margin: 0 0 10px;
            font-family: "Sora", "Noto Sans SC", sans-serif;
            font-size: 23px;
            letter-spacing: .01em;
        }

        .qq-box {
            border: 1px solid #d0e2ed;
            border-radius: 12px;
            background: #f4faff;
            padding: 12px;
        }

        .qq-label {
            color: #698492;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .qq-id {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: .04em;
            color: #1c4355;
            word-break: break-all;
        }

        .btns {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border: 1px solid #cadfeb;
            border-radius: 11px;
            background: #f6fbff;
            color: #255a71;
            font-weight: 700;
            padding: 11px 12px;
            transition: transform .16s ease, border-color .16s ease;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-1px);
            border-color: #a9c9db;
        }

        .btn.hot {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(120deg, var(--hot) 0%, var(--hot-2) 45%, #de3d3d 100%);
            box-shadow: 0 10px 20px rgba(222, 86, 56, .22);
        }

        .btn.accent {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(120deg, var(--accent) 0%, #159ee9 45%, var(--accent-2) 100%);
            box-shadow: 0 10px 20px rgba(33, 132, 191, .22);
        }

        .tips {
            margin-top: 10px;
            border: 1px solid #d4e3ed;
            border-radius: 11px;
            background: #fbfeff;
            padding: 10px 12px;
            color: #5f7b89;
            font-size: 13px;
            line-height: 1.65;
        }

        .steps {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .step {
            border: 1px solid #d3e2ec;
            border-radius: 11px;
            background: #f7fcff;
            padding: 10px;
        }

        .step strong {
            display: block;
            margin-bottom: 3px;
            color: #224859;
            font-size: 14px;
        }

        .step span {
            color: #64808f;
            font-size: 13px;
            line-height: 1.55;
        }

        .footer-links {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        @media (max-width: 900px) {
            .main-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 680px) {
            .shell { width: min(980px, calc(100% - 16px)); padding: 14px 0 18px; }
            .hero { border-radius: 16px; padding: 12px; }
            .panel { border-radius: 13px; padding: 10px; }
            .btns { grid-template-columns: 1fr; }
            .qq-id { font-size: 26px; }
        }
    </style>
</head>
<body>
<main class="shell">
    <section class="hero">
        <span class="eyebrow">Kami Purchase</span>
        <h1>购买卡密</h1>
        <p class="lead">添加客服 QQ 后发送“购买卡密”即可。购买完成后，回到充值中心兑换并立即使用。</p>

        <div class="main-grid">
            <section class="panel">
                <h2>联系购买</h2>
                <div class="qq-box">
                    <div class="qq-label">客服 QQ</div>
                    <div class="qq-id" id="qqNumber">5772668</div>
                </div>
                <div class="btns">
                    <a class="btn hot" href="https://wpa.qq.com/msgrd?v=3&uin=5772668&site=qq&menu=yes" target="_blank" rel="noopener">立即联系 QQ</a>
                    <a class="btn accent" href="tencent://message/?uin=5772668&Site=vector&Menu=yes">唤起 QQ 客户端</a>
                </div>
                <div class="footer-links">
                    <button class="btn" type="button" id="copyBtn">复制 QQ 号</button>
                    <a class="btn" href="{{ route('vector-web.billing.index') }}">前往充值中心</a>
                    <a class="btn" href="{{ route('vector-web.upload') }}">返回上传页</a>
                </div>
                <div class="tips" id="copyHint">提示：如果“唤起 QQ 客户端”无反应，请点击“立即联系 QQ”或复制 QQ 号手动添加。</div>
            </section>

            <aside class="panel">
                <h2>新手流程</h2>
                <ul class="steps">
                    <li class="step">
                        <strong>1. 添加客服 QQ</strong>
                        <span>点击“立即联系 QQ”或“唤起 QQ 客户端”，联系 QQ：5772668。</span>
                    </li>
                    <li class="step">
                        <strong>2. 购买卡密</strong>
                        <span>按客服指引完成购买后，会获得可兑换的卡密字符串。</span>
                    </li>
                    <li class="step">
                        <strong>3. 兑换并开始上传</strong>
                        <span>回到充值中心输入卡密完成兑换，然后返回上传页提交任务。</span>
                    </li>
                </ul>
            </aside>
        </div>
    </section>
</main>

<script>
(() => {
    const copyBtn = document.getElementById('copyBtn');
    const copyHint = document.getElementById('copyHint');
    const qqNumber = document.getElementById('qqNumber').textContent.trim();

    copyBtn.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(qqNumber);
            copyHint.textContent = `已复制 QQ：${qqNumber}，请打开 QQ 添加好友。`;
        } catch (_) {
            copyHint.textContent = `复制失败，请手动复制 QQ：${qqNumber}`;
        }
    });
})();
</script>
</body>
</html>
