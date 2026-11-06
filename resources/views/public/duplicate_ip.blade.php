<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Network Change Recommended | {{ $campaign->name }}</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #1d4ed8;
            --warning: #f59e0b;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0b1120 0%, #0f172a 50%, #1e1b4b 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 12px;
        }
        .warn-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 24px;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.6), 0 0 30px rgba(245, 158, 11, 0.15);
            max-width: 480px;
            width: 100%;
            overflow: hidden;
        }
        .badge-ip {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
            padding: 6px 12px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.95rem;
        }
        .step-pill {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 10px;
        }
        .btn-retry {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #fff;
            font-weight: 700;
            border: none;
            border-radius: 14px;
            padding: 14px 20px;
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.5);
            transition: all 0.2s ease;
        }
        .btn-retry:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 14px 25px -5px rgba(37, 99, 235, 0.7);
        }
    </style>
</head>
<body>

<div class="warn-card p-4 p-md-5 text-center">
    <div class="mb-3">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 72px; height: 72px; background: rgba(245, 158, 11, 0.15); border: 2px solid rgba(245, 158, 11, 0.4);">
            <i class="fas fa-shield-alt text-warning fa-2x"></i>
        </span>
    </div>

    <span class="badge badge-warning text-dark font-weight-bold px-3 py-1 mb-2" style="border-radius: 20px; font-size: 0.75rem;">
        ANTI-FRAUD & CONVERSION GUARANTEE
    </span>

    <h4 class="font-weight-bold mb-2">Duplicate IP Detected</h4>
    <p class="text-muted small mb-3">
        Our system noticed that your current connection IP has already participated in <strong>{{ $campaign->name }}</strong>.
    </p>

    <div class="mb-3">
        <span class="badge-ip">
            <i class="fas fa-network-wired mr-1"></i> Current IP: {{ $clientIp }}
        </span>
    </div>

    <div class="text-left my-4">
        <div class="step-pill d-flex align-items-center">
            <div class="mr-3 text-warning font-weight-bold" style="font-size: 1.25rem;">1</div>
            <div class="small">
                <strong>Switch your internet connection:</strong><br>
                <span class="text-muted">Turn off Wi-Fi and switch to Mobile Data, or toggle Airplane mode on and off.</span>
            </div>
        </div>
        <div class="step-pill d-flex align-items-center">
            <div class="mr-3 text-primary font-weight-bold" style="font-size: 1.25rem;">2</div>
            <div class="small">
                <strong>Verify and proceed:</strong><br>
                <span class="text-muted">Tap the button below. Once a fresh IP is detected, you will be redirected to complete your task and claim your reward.</span>
            </div>
        </div>
    </div>

    <a href="{{ route('tracking.redirect', ['token' => $token]) }}" class="btn btn-retry btn-block mb-3">
        <i class="fas fa-sync-alt mr-2"></i> Verify New IP & Continue
    </a>

    <a href="{{ route('tracking.redirect', ['token' => $token, 'force' => 1]) }}" class="text-muted small d-inline-block">
        <i class="fas fa-angle-right mr-1"></i> Continue with current IP anyway
    </a>
</div>

</body>
</html>
