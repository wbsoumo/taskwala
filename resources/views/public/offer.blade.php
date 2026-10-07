<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $campaign->name }} | Earn ₹{{ number_format($customerPayout, 0) }} Instantly</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
        :root {
            --primary-glow: rgba(99, 102, 241, 0.4);
            --accent-green: #10b981;
            --accent-glow: rgba(16, 185, 129, 0.4);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 16px;
            background-color: #0b0f17;
        }

        /* 1. Theme: Gradient Blue (BankSathi Midnight Premium) */
        body.theme-gradient_blue {
            background: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #0f172a 50%, #090d16 100%);
            color: #f8fafc;
        }
        .theme-gradient_blue .offer-card {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.75), 0 0 30px rgba(99, 102, 241, 0.15);
        }

        /* 2. Theme: Dark Glassmorphism */
        body.theme-dark_glass {
            background: radial-gradient(circle at 100% 0%, #181825 0%, #0d0e15 100%);
            color: #ffffff;
        }
        .theme-dark_glass .offer-card {
            background: rgba(20, 24, 38, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.8), 0 0 40px rgba(236, 72, 153, 0.1);
        }

        /* 3. Theme: Emerald Fortune */
        body.theme-emerald {
            background: radial-gradient(circle at 50% 0%, #064e3b 0%, #022c22 60%, #011711 100%);
            color: #ecfdf5;
        }
        .theme-emerald .offer-card {
            background: rgba(6, 78, 59, 0.85);
            border: 1px solid rgba(52, 211, 153, 0.25);
            box-shadow: 0 30px 60px rgba(0,0,0,0.8), 0 0 35px rgba(16, 185, 129, 0.2);
        }

        /* 4. Theme: Clean Light Minimalist */
        body.theme-clean_minimal {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #0f172a;
        }
        .theme-clean_minimal .offer-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        }

        .offer-card {
            border-radius: 28px;
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            padding: 36px 28px;
            position: relative;
            animation: cardAppear 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes cardAppear {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Floating Logo Hexagon Frame */
        .logo-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 16px;
        }
        .logo-frame {
            width: 88px;
            height: 88px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.2);
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 12px 24px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }
        .offer-card:hover .logo-frame {
            transform: scale(1.04) rotate(2deg);
        }
        .logo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 18px;
        }

        .category-badge {
            background: rgba(99, 102, 241, 0.2);
            border: 1px solid rgba(99, 102, 241, 0.4);
            color: #818cf8;
            font-size: 0.725rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 5px 14px;
            border-radius: 20px;
            text-transform: uppercase;
            display: inline-block;
        }
        .theme-clean_minimal .category-badge {
            background: #e0e7ff;
            border-color: #c7d2fe;
            color: #4338ca;
        }

        /* BankSathi Reward Cash Card */
        .reward-card {
            background: linear-gradient(135deg, #059669 0%, #10b981 50%, #34d399 100%);
            border-radius: 20px;
            padding: 20px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 28px rgba(16, 185, 129, 0.35);
            margin: 22px 0;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .reward-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.25) 0%, transparent 60%);
            pointer-events: none;
        }
        .reward-title {
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            opacity: 0.95;
        }
        .reward-amount {
            font-size: 2.6rem;
            font-weight: 800;
            line-height: 1.1;
            margin-top: 4px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        /* Step Indicators */
        .steps-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 14px;
            padding: 10px 14px;
            margin-bottom: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .theme-clean_minimal .steps-bar {
            background: #f1f5f9;
            border-color: #e2e8f0;
            color: #475569;
        }
        .step-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .step-num {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #10b981;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 800;
        }

        /* UPI Input */
        .upi-input-wrap {
            position: relative;
        }
        .upi-field {
            background: rgba(255, 255, 255, 0.06);
            border: 2px solid rgba(255, 255, 255, 0.15);
            color: #ffffff !important;
            border-radius: 16px !important;
            padding: 14px 18px !important;
            font-size: 1.05rem !important;
            font-weight: 600;
            transition: all 0.25s ease;
        }
        .theme-clean_minimal .upi-field {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #0f172a !important;
        }
        .upi-field:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: #10b981 !important;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.3) !important;
            outline: none;
        }

        /* UPI Autocomplete Dropdown */
        .upi-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(15, 23, 42, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            margin-top: 6px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
            backdrop-filter: blur(16px);
            z-index: 100;
            overflow: hidden;
            display: none;
        }
        .theme-clean_minimal .upi-dropdown {
            background: #ffffff;
            border-color: #cbd5e1;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .upi-dropdown-item {
            padding: 12px 18px;
            font-size: 0.95rem;
            font-weight: 600;
            color: #cbd5e1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.15s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .theme-clean_minimal .upi-dropdown-item {
            color: #334155;
            border-bottom-color: #f1f5f9;
        }
        .upi-dropdown-item:last-child {
            border-bottom: none;
        }
        .upi-dropdown-item:hover, .upi-dropdown-item.active {
            background: #10b981;
            color: #ffffff;
        }

        /* Action Button */
        .btn-complete {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            color: #ffffff;
            font-weight: 800;
            font-size: 1.05rem;
            padding: 16px 20px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.45);
            transition: all 0.25s ease;
            letter-spacing: 0.3px;
        }
        .btn-complete:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 14px 35px rgba(37, 99, 235, 0.6);
            color: #ffffff;
        }

        .trust-tag {
            font-size: 0.725rem;
            opacity: 0.7;
            margin-top: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
    </style>
</head>
<body class="theme-{{ $theme }}">

<div class="offer-card text-center">

    <!-- Campaign Logo -->
    <div class="logo-wrapper">
        <div class="logo-frame">
            @if(!empty($campaign->logo_url))
                <img src="{{ $campaign->logo_url }}" alt="{{ $campaign->name }}" class="logo-img">
            @else
                <div class="d-flex align-items-center justify-content-center w-100 h-100 text-primary font-weight-bold" style="font-size: 2rem;">
                    <i class="fas fa-bolt"></i>
                </div>
            @endif
        </div>
    </div>

    <!-- Category & Offer Name -->
    <div>
        <span class="category-badge mb-2">{{ $campaign->category }}</span>
        <h2 class="font-weight-extrabold mb-1" style="font-size: 1.75rem; letter-spacing: -0.5px;">{{ $campaign->name }}</h2>
        <p class="small mb-2 opacity-75" style="font-size: 0.875rem;">{{ $campaign->short_description ?? 'Complete the simple task below to claim your guaranteed reward.' }}</p>
    </div>

    <!-- BankSathi Guaranteed Reward Card -->
    <div class="reward-card">
        <div class="reward-title"><i class="fas fa-gift mr-1"></i> GUARANTEED CASH REWARD</div>
        <div class="reward-amount">₹{{ number_format($customerPayout, 0) }}</div>
    </div>

    <!-- Steps Indicator -->
    <div class="steps-bar">
        <div class="step-item"><span class="step-num">1</span> Enter UPI</div>
        <i class="fas fa-chevron-right opacity-50" style="font-size: 0.65rem;"></i>
        <div class="step-item"><span class="step-num">2</span> Complete Task</div>
        <i class="fas fa-chevron-right opacity-50" style="font-size: 0.65rem;"></i>
        <div class="step-item"><span class="step-num">3</span> Get Cash</div>
    </div>

    <!-- Errors Display -->
    @if($errors->any())
        <div class="alert alert-danger py-2 px-3 rounded-lg small mb-3 text-left">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ $errors->first() }}
        </div>
    @endif

    <!-- Form Submission -->
    <form action="{{ route('tracking.submit', $link->secure_token) }}" method="POST" id="taskForm">
        @csrf

        <div class="form-group text-left mb-4">
            <label class="font-weight-bold small text-uppercase mb-2 opacity-90 d-flex align-items-center justify-content-between">
                <span><i class="fas fa-wallet text-success mr-1"></i> Enter Your UPI ID</span>
                <span class="badge badge-success px-2 py-1" style="font-size: 0.65rem;">Direct Payout</span>
            </label>
            <div class="upi-input-wrap">
                <input type="text" name="upi_id" id="upiInput" class="form-control upi-field text-lowercase" placeholder="mobile@ybl" value="{{ old('upi_id') }}" required autocomplete="off">
                <div id="upiDropdown" class="upi-dropdown"></div>
            </div>
            <div id="upiFeedback" class="invalid-feedback d-none small mt-1 text-danger">Please enter a valid UPI ID (e.g. 9876543210@ybl).</div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-complete btn-block">
            Complete Task & Earn ₹{{ number_format($customerPayout, 0) }} <i class="fas fa-arrow-right ml-2"></i>
        </button>
    </form>

    <!-- Trust Tag & Taskwala Branding -->
    <div class="trust-tag">
        <i class="fas fa-lock text-success"></i> 256-Bit Encrypted &amp; Verified by 
        <a href="https://taskwala.co.in" target="_blank" class="text-decoration-none font-weight-bold color-inherit text-white opacity-90">taskwala.co.in</a>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const upiHandles = ['@ybl', '@sbi', '@okaxis', '@paytm', '@icici', '@ibl', '@axl', '@okicici', '@postbank'];

    function renderDropdown(username, handleSearch) {
        const dropdown = $('#upiDropdown');
        dropdown.empty();

        const filtered = upiHandles.filter(h => h.startsWith(handleSearch));

        if (filtered.length === 0 || username.trim() === '') {
            dropdown.hide();
            return;
        }

        filtered.forEach(handle => {
            const item = $(`
                <div class="upi-dropdown-item">
                    <span><strong>${username}</strong><span style="color:#10b981;">${handle}</span></span>
                    <span class="small opacity-75"><i class="fas fa-check-circle"></i></span>
                </div>
            `);
            item.on('click', function() {
                $('#upiInput').val(username + handle).focus();
                dropdown.hide();
                validateUpi();
            });
            dropdown.append(item);
        });

        dropdown.show();
    }

    function validateUpi() {
        const val = $('#upiInput').val().trim();
        const regex = /^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/;
        if (val.length > 0 && !regex.test(val)) {
            $('#upiInput').addClass('is-invalid');
            $('#upiFeedback').removeClass('d-none').show();
        } else {
            $('#upiInput').removeClass('is-invalid');
            $('#upiFeedback').addClass('d-none').hide();
        }
    }

    $('#upiInput').on('input', function() {
        const val = $(this).val().trim();
        validateUpi();

        if (val.includes('@')) {
            const parts = val.split('@');
            const username = parts[0];
            const handleSearch = '@' + parts[1];
            renderDropdown(username, handleSearch);
        } else {
            $('#upiDropdown').hide();
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.upi-input-wrap').length) {
            $('#upiDropdown').hide();
        }
    });
</script>
</body>
</html>

