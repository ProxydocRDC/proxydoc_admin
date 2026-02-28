<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Accès refusé | PROXYDOC</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Instrument Sans', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            color: #e2e8f0;
            padding: 2rem;
        }
        .container {
            text-align: center;
            max-width: 480px;
        }
        .code {
            font-size: 8rem;
            font-weight: 700;
            line-height: 1;
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 1rem;
            color: #94a3b8;
        }
        p {
            margin-top: 0.75rem;
            color: #64748b;
            line-height: 1.6;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding: 0.75rem 1.5rem;
            background: #13A4D3;
            color: white;
            text-decoration: none;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: all 0.2s;
            box-shadow: 0 4px 14px rgba(19, 164, 211, 0.4);
        }
        .btn:hover {
            background: #0d8ab8;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(19, 164, 211, 0.5);
        }
        .btn svg {
            width: 1.25rem;
            height: 1.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">403</div>
        <h1>Accès refusé</h1>
        <p>{{ $message ?: 'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.' }}</p>
        <a href="{{ url('/admin') }}" class="btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Retour à l'accueil
        </a>
    </div>
</body>
</html>
