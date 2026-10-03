<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in to Bagsakan POS | Worthy Acosta</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="{{ asset('assets/css/bagsakan.css') }}?v={{ time() }}">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #FFFFFF;
            color: #1E293B;
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        .login-page-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Left Showcase Column */
        .showcase-column {
            flex: 1.1;
            background-color: #FFFFFF;
            padding: 48px 56px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            border-right: 1px solid #F1F5F9;
        }

        .showcase-top-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .showcase-top-logo img {
            height: 38px;
            width: auto;
            object-fit: contain;
        }

        .showcase-top-logo span {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--color-deep-navy);
            letter-spacing: -0.01em;
        }

        .showcase-center-content {
            max-width: 520px;
            margin: 20px 0;
        }

        .showcase-image-wrapper {
            position: relative;
            margin-bottom: 36px;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(16, 42, 78, 0.12), 0 4px 12px rgba(16, 42, 78, 0.06);
            border: 1px solid rgba(16, 42, 78, 0.06);
        }

        .showcase-image {
            width: 100%;
            height: 380px;
            object-fit: cover;
            display: block;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .showcase-image-wrapper:hover .showcase-image {
            transform: scale(1.03);
        }

        .hero-headline {
            font-size: 2.75rem;
            font-weight: 900;
            line-height: 1.12;
            color: #102A4E;
            letter-spacing: -0.03em;
            margin-bottom: 14px;
        }

        .hero-highlight {
            color: #075998;
            display: block;
        }

        .hero-tagline {
            font-size: 0.98rem;
            color: #64748B;
            line-height: 1.55;
            font-weight: 450;
        }

        .showcase-footer-text {
            font-size: 0.78rem;
            color: #94A3B8;
            font-weight: 500;
        }

        /* Right Form Column */
        .form-column {
            flex: 0.9;
            background-color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
        }

        .form-wrapper {
            width: 100%;
            max-width: 390px;
        }

        .form-brand-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .form-logo-img {
            height: 56px;
            width: auto;
            object-fit: contain;
            margin-bottom: 14px;
        }

        .form-brand-kicker {
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #075998;
            margin-bottom: 6px;
        }

        .form-main-title {
            font-size: 1.55rem;
            font-weight: 800;
            color: #102A4E;
            letter-spacing: -0.02em;
        }

        .custom-input-group {
            margin-bottom: 18px;
        }

        .custom-label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .custom-input {
            width: 100%;
            padding: 12px 14px;
            font-size: 0.92rem;
            font-family: inherit;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            color: #0F172A;
            background-color: #FFFFFF;
            transition: all 0.2s ease;
            outline: none;
        }

        .custom-input:focus {
            border-color: #075998;
            box-shadow: 0 0 0 3px rgba(7, 89, 152, 0.12);
        }

        .custom-input::placeholder {
            color: #94A3B8;
        }

        .btn-submit-login {
            width: 100%;
            padding: 13px;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: inherit;
            color: #FFFFFF;
            background: #075998;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
            box-shadow: 0 4px 14px rgba(7, 89, 152, 0.25);
        }

        .btn-submit-login:hover {
            background: #102A4E;
            box-shadow: 0 6px 18px rgba(16, 42, 78, 0.3);
            transform: translateY(-1px);
        }

        .btn-submit-login:active {
            transform: translateY(0);
        }

        /* Demo roles box */
        .demo-roles-box {
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px dashed #E2E8F0;
        }

        .demo-roles-title {
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 10px;
            text-align: center;
        }

        .demo-pill-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            font-size: 0.76rem;
            font-weight: 600;
            border-radius: 6px;
            background: #F0F9FF;
            color: #0369A1;
            border: 1px solid #BAE6FD;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 3px 2px;
            text-decoration: none;
        }

        .demo-pill-btn:hover {
            background: #075998;
            color: #FFFFFF;
            border-color: #075998;
        }

        .error-banner {
            background-color: #FEF2F2;
            border: 1px solid #FEE2E2;
            color: #991B1B;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .login-page-container {
                flex-direction: column;
            }

            .showcase-column {
                border-right: none;
                border-bottom: 1px solid #F1F5F9;
                padding: 36px 24px;
            }

            .showcase-image {
                height: 260px;
            }

            .hero-headline {
                font-size: 2.1rem;
            }

            .form-column {
                padding: 36px 24px;
            }
        }
    </style>
</head>

<body>
    <div class="login-page-container">
        <!-- Left Showcase Side -->
        <div class="showcase-column">
            <div class="showcase-top-logo">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Worthy Acosta Logo">
                <span>Worthy Acosta Trading</span>
            </div>

            <div class="showcase-center-content">
                <div class="showcase-image-wrapper">
                    <img src="{{ asset('assets/images/bagsakan_team.jpg') }}" alt="Worthy Acosta Bagsakan Operations"
                        class="showcase-image">
                </div>

                <h1 class="hero-headline">
                    Empowering Fresh
                    <span class="hero-highlight">Bagsakan Excellence.</span>
                </h1>
            </div>

            <div class="showcase-footer-text">
                &copy; {{ date('Y') }} Worthy Acosta Trading & Bagsakan Systems. All rights reserved.
            </div>
        </div>

        <!-- Right Form Side -->
        <div class="form-column">
            <div class="form-wrapper">
                <div class="form-brand-header">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Worthy Acosta Logo" class="form-logo-img">
                    <div class="form-brand-kicker">Worthy Acosta Bagsakan</div>
                    <h2 class="form-main-title">Log into Bagsakan POS</h2>
                </div>

                @if ($errors->any())
                    <div class="error-banner">
                        <i class="bi bi-exclamation-circle-fill" style="font-size: 1rem;"></i>
                        <div>{{ $errors->first() }}</div>
                    </div>
                @endif

                <form action="{{ route('login.submit') }}" method="POST">
                    @csrf

                    <div class="custom-input-group">
                        <label class="custom-label" for="email_or_username">Email or Username</label>
                        <input type="text" id="email_or_username" name="email_or_username" class="custom-input"
                            placeholder="admin@bagsakan.com or admin"
                            value="{{ old('email_or_username', 'admin@bagsakan.com') }}" required autofocus>
                    </div>

                    <div class="custom-input-group">
                        <label class="custom-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="custom-input"
                            placeholder="Enter password" value="admin123" required>
                    </div>

                    <button type="submit" class="btn-submit-login">
                        <span>Log in</span>
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </form>

                <!-- Quick Demo Role Switcher -->
                <div class="demo-roles-box">
                    <div class="demo-roles-title">Demo Quick Login:</div>
                    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 4px;">
                        <span class="demo-pill-btn" onclick="fillLogin('admin@bagsakan.com', 'admin123')">
                            <i class="bi bi-shield-check"></i> Admin / Owner
                        </span>
                        <span class="demo-pill-btn" onclick="fillLogin('purchasing@bagsakan.com', 'purchasing123')">
                            <i class="bi bi-cart-plus"></i> Purchasing Staff
                        </span>
                        <span class="demo-pill-btn" onclick="fillLogin('cashier@bagsakan.com', 'cashier123')">
                            <i class="bi bi-person-badge"></i> POS Cashier
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Toast Container -->
    <div id="globalToastContainer" class="toast-container"></div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/bagsakan.js') }}"></script>
    <script>
        function fillLogin(user, pass) {
            document.getElementById('email_or_username').value = user;
            document.getElementById('password').value = pass;
        }

        @if(session('success'))
            document.addEventListener('DOMContentLoaded', () => {
                showToast('info', 'Signed Out', @json(session('success')));
            });
        @endif

        @if(session('error'))
            document.addEventListener('DOMContentLoaded', () => {
                showToast('error', 'Error', @json(session('error')));
            });
        @endif
    </script>
</body>
</html>
