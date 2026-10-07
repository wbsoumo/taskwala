<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $campaign->name }} | Complete Task & Earn ₹{{ number_format($customerPayout, 2) }}</title>

    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        /* 1. Theme: Gradient Blue */
        body.theme-gradient_blue {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e293b 100%);
            color: #fff;
        }
        .theme-gradient_blue .offer-card {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        /* 2. Theme: Dark Glassmorphism */
        body.theme-dark_glass {
            background: radial-gradient(circle at top left, #111827, #030712);
            color: #f9fafb;
        }
        .theme-dark_glass .offer-card {
            background: rgba(17, 24, 39, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        }

        /* 3. Theme: Emerald Fortune */
        body.theme-emerald {
            background: linear-gradient(135deg, #064e3b 0%, #022c22 100%);
            color: #ecfdf5;
        }
        .theme-emerald .offer-card {
            background: rgba(6, 78, 59, 0.9);
            border: 1px solid rgba(16, 185, 129, 0.2);
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
        }

        /* 4. Theme: Clean Minimalist */
        body.theme-clean_minimal {
            background: #f8fafc;
            color: #0f172a;
        }
        .theme-clean_minimal .offer-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .offer-card {
            border-radius: 24px;
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            padding: 32px 24px;
        }

        .reward-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            border-radius: 16px;
            padding: 16px 20px;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .upi-chip {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: inherit;
            font-size: 0.825rem;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-block;
            margin: 3px;
        }
        .theme-clean_minimal .upi-chip {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #334155;
        }
        .upi-chip:hover, .upi-chip:active {
            background: #3b82f6;
            color: #fff;
            border-color: #3b82f6;
            transform: translateY(-1px);
        }

        .btn-complete {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 16px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
            transition: all 0.2s ease;
        }
        .btn-complete:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(37, 99, 235, 0.5);
            color: #fff;
        }

        .brand-footer {
            font-size: 0.75rem;
            opacity: 0.65;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body class="theme-{{ $theme }}">

<div class="offer-card text-center">

    <!-- Campaign Logo / Fallback Icon -->
    <div class="mb-3">
        @if(!empty($campaign->logo_url))
            <img src="{{ $campaign->logo_url }}" alt="{{ $campaign->name }}" class="img-fluid rounded-circle shadow-sm" style="max-height: 80px; width: 80px; object-fit: cover;">
        @else
            <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle shadow" style="width: 72px; height: 72px; font-size: 2rem;">
                <i class="fas fa-gift"></i>
            </div>
        @endif
    </div>

    <!-- Category & Campaign Name -->
    <span class="badge badge-pill badge-primary px-3 py-1 mb-2 text-uppercase font-weight-bold" style="font-size: 0.75rem;">{{ $campaign->category }}</span>
    <h3 class="font-weight-bold mb-2">{{ $campaign->name }}</h3>
    <p class="small mb-4 opacity-75">{{ $campaign->short_description ?? 'Complete the simple task below to claim your reward.' }}</p>

    <!-- Reward Highlight -->
    <div class="reward-badge mb-4">
        <span class="text-uppercase small d-block font-weight-bold opacity-90" style="letter-spacing: 1px;">YOUR CASH REWARD</span>
        <h2 class="font-weight-extrabold mb-0 mt-1" style="font-size: 2.2rem;">₹{{ number_format($customerPayout, 2) }}</h2>
    </div>

    <!-- Errors Display -->
    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 rounded-lg small mb-3 text-left">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ $errors->first() }}
        </div>
    @endif

    <!-- UPI Task Submission Form -->
    <form action="{{ route('tracking.submit', $link->secure_token) }}" method="POST" id="taskForm">
        @csrf

        <div class="form-group text-left mb-3">
            <label class="font-weight-bold small text-uppercase mb-2 opacity-90">
                <i class="fas fa-wallet text-success mr-1"></i> Enter Your UPI ID for Cash Transfer
            </label>
            <div class="input-group input-group-lg">
                <input type="text" name="upi_id" id="upiInput" class="form-control rounded-lg text-lowercase font-weight-bold" placeholder="username@ybl" value="{{ old('upi_id') }}" required autocomplete="off" style="font-size: 1rem;">
            </div>
            <div id="upiFeedback" class="invalid-feedback d-none small mt-1">Please enter a valid UPI format (e.g. name@ybl).</div>
        </div>

        <!-- Quick Tap UPI Handle Suggestions -->
        <div class="mb-4 text-left">
            <span class="small font-weight-bold opacity-75 d-block mb-1">Quick Select Handle:</span>
            <div class="d-flex flex-wrap">
                <span class="upi-chip" onclick="appendHandle('@ybl')">@ybl</span>
                <span class="upi-chip" onclick="appendHandle('@sbi')">@sbi</span>
                <span class="upi-chip" onclick="appendHandle('@okaxis')">@okaxis</span>
                <span class="upi-chip" onclick="appendHandle('@paytm')">@paytm</span>
                <span class="upi-chip" onclick="appendHandle('@icici')">@icici</span>
                <span class="upi-chip" onclick="appendHandle('@ibl')">@ibl</span>
                <span class="upi-chip" onclick="appendHandle('@axl')">@axl</span>
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-complete btn-block">
            <i class="fas fa-arrow-right mr-2"></i> Complete Task & Earn ₹{{ number_format($customerPayout, 2) }}
        </button>
    </form>

    <!-- Small Taskwala Branding Footer -->
    <div class="brand-footer text-center mt-4 pt-3 border-top border-secondary">
        <a href="https://taskwala.co.in" target="_blank" class="text-decoration-none color-inherit opacity-75">
            <i class="fas fa-shield-alt text-primary mr-1"></i> Powered by <strong>taskwala.co.in</strong>
        </a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function appendHandle(handle) {
        let input = $('#upiInput').val().trim();
        if (input.includes('@')) {
            input = input.split('@')[0];
        }
        if (input.length > 0) {
            $('#upiInput').val(input + handle).focus();
        } else {
            $('#upiInput').val('username' + handle).focus().select();
        }
    }

    $('#upiInput').on('input', function() {
        const val = $(this).val().trim();
        const regex = /^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/;
        if (val.length > 0 && !regex.test(val)) {
            $(this).addClass('is-invalid');
            $('#upiFeedback').removeClass('d-none').show();
        } else {
            $(this).removeClass('is-invalid');
            $('#upiFeedback').addClass('d-none').hide();
        }
    });
</script>
</body>
</html>
