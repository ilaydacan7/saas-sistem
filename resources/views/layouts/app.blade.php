<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('baslik', config('app.name'))</title>
    <style>
        :root {
            --zemin: #f6f7f9;
            --kart: #ffffff;
            --metin: #1c2024;
            --soluk: #61696f;
            --cizgi: #e3e6ea;
            --vurgu: #2f5bd8;
            --hata: #b3261e;
            --uyari-zemin: #fff6e5;
            --uyari-cizgi: #f0d5a8;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --zemin: #14171a;
                --kart: #1c2024;
                --metin: #e8eaed;
                --soluk: #9aa2aa;
                --cizgi: #2c3238;
                --vurgu: #7d9bf0;
                --hata: #f2907f;
                --uyari-zemin: #2e2616;
                --uyari-cizgi: #4d4020;
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--zemin);
            color: var(--metin);
            font: 15px/1.55 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .kabuk { max-width: 460px; margin: 0 auto; padding: 48px 20px; }
        .genis { max-width: 720px; }
        .kart {
            background: var(--kart);
            border: 1px solid var(--cizgi);
            border-radius: 12px;
            padding: 28px;
        }
        h1 { font-size: 22px; margin: 0 0 6px; letter-spacing: -0.01em; }
        .alt { color: var(--soluk); margin: 0 0 24px; font-size: 14px; }
        label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px; }
        input[type=text], input[type=email], input[type=password] {
            width: 100%;
            padding: 10px 12px;
            font: inherit;
            color: var(--metin);
            background: var(--zemin);
            border: 1px solid var(--cizgi);
            border-radius: 8px;
        }
        input:focus { outline: 2px solid var(--vurgu); outline-offset: -1px; }
        .alan { margin-bottom: 18px; }
        .ipucu { color: var(--soluk); font-size: 12px; margin-top: 6px; }
        .adres { display: flex; align-items: center; gap: 0; }
        .adres input { border-radius: 8px 0 0 8px; }
        .adres .son {
            padding: 10px 12px;
            border: 1px solid var(--cizgi);
            border-left: 0;
            border-radius: 0 8px 8px 0;
            background: var(--zemin);
            color: var(--soluk);
            white-space: nowrap;
        }
        button {
            width: 100%;
            padding: 11px 16px;
            font: inherit;
            font-weight: 600;
            color: #fff;
            background: var(--vurgu);
            border: 0;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover { filter: brightness(1.08); }
        .hata { color: var(--hata); font-size: 13px; margin-top: 6px; }
        .kutu {
            background: var(--uyari-zemin);
            border: 1px solid var(--uyari-cizgi);
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        a { color: var(--vurgu); }
        .satir { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .kucuk { font-size: 13px; color: var(--soluk); }
        .baglantilar { margin-top: 20px; text-align: center; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        td { padding: 9px 0; border-bottom: 1px solid var(--cizgi); }
        td:first-child { color: var(--soluk); width: 40%; }
        .cikis { width: auto; padding: 7px 14px; font-size: 13px; background: transparent; color: var(--soluk); border: 1px solid var(--cizgi); }
    </style>
</head>
<body>
    <div class="kabuk @yield('kabuk')">
        @yield('icerik')
    </div>
</body>
</html>
