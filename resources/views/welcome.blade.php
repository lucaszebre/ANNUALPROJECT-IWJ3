<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="CodeCampus: learn to code with indexed videos and auto-graded exercises. Coming soon.">
    <title>CodeCampus · Coming soon</title>
    <style>
        :root {
            --bg: #0b1020;
            --fg: #e8ecf8;
            --muted: #8f9bbd;
            --accent: #7c9cff;
            --accent-2: #5ee6b8;
            --card: rgba(255, 255, 255, 0.04);
            --border: rgba(255, 255, 255, 0.09);
        }
        * { box-sizing: border-box; margin: 0; }
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px 16px;
            background:
                radial-gradient(60rem 30rem at 15% -10%, rgba(124, 156, 255, 0.18), transparent 60%),
                radial-gradient(50rem 30rem at 110% 110%, rgba(94, 230, 184, 0.12), transparent 60%),
                var(--bg);
            color: var(--fg);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.5;
        }
        main { width: 100%; max-width: 640px; text-align: center; }
        .logo {
            display: inline-flex; align-items: center; gap: 10px;
            font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
            font-size: 1.05rem; color: var(--muted);
        }
        .logo b { color: var(--fg); }
        h1 {
            margin-top: 28px;
            font-size: clamp(2.4rem, 8vw, 4rem);
            line-height: 1.05;
            letter-spacing: -0.03em;
        }
        h1 span {
            background: linear-gradient(90deg, var(--accent), var(--accent-2));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .lead { margin-top: 16px; font-size: 1.1rem; color: var(--muted); }
        .terminal {
            margin: 36px auto 0; max-width: 460px; text-align: left;
            background: var(--card); border: 1px solid var(--border); border-radius: 12px;
            padding: 16px 18px;
            font-family: ui-monospace, "SF Mono", Menlo, Consolas, monospace; font-size: 0.92rem;
        }
        .terminal p { color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .terminal .ok { color: var(--accent-2); }
        .cursor {
            display: inline-block; width: 0.6em; height: 1.1em; vertical-align: text-bottom;
            background: var(--accent); animation: blink 1s steps(1) infinite;
        }
        @keyframes blink { 50% { opacity: 0; } }
        @media (prefers-reduced-motion: reduce) { .cursor { animation: none; } }
        ul {
            list-style: none; padding: 0; margin-top: 36px;
            display: flex; flex-wrap: wrap; justify-content: center; gap: 10px;
        }
        li {
            padding: 6px 14px; border: 1px solid var(--border); border-radius: 999px;
            font-size: 0.9rem; color: var(--muted);
        }
        footer { margin-top: 48px; font-size: 0.85rem; color: var(--muted); }
    </style>
</head>
<body>
    <main>
        <div class="logo" aria-label="CodeCampus">&lt;/&gt; <b>codecampus</b></div>

        <h1>Something is <span>compiling</span>.</h1>
        <p class="lead">Learn to code with searchable videos, timecoded questions and exercises graded the moment you submit them.</p>

        <div class="terminal" aria-hidden="true">
            <p>$ php artisan campus:launch</p>
            <p class="ok">✓ videos transcoded to HLS</p>
            <p class="ok">✓ sandbox locked down</p>
            <p>› launching soon <span class="cursor"></span></p>
        </div>

        <ul>
            <li>Indexed video</li>
            <li>Auto-graded exercises</li>
            <li>Teacher analytics</li>
        </ul>

        <footer>ESGI 3IWJ · {{ date('Y') }}</footer>
    </main>
</body>
</html>
