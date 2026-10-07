<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Bagsakan POS - Worthy Acosta</title>

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
            color: #102A4E;
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
            max-width: 400px;
        }

        .form-brand-header {
            text-align: center;
            margin-bottom: 28px;
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
            margin-bottom: 8px;
        }

        .form-subtitle {
            font-size: 0.88rem;
            color: #64748B;
            line-height: 1.5;
        }

        .custom-input-group {
            margin-bottom: 20px;
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

        .btn-submit-action {
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
            margin-top: 20px;
            box-shadow: 0 4px 14px rgba(7, 89, 152, 0.25);
        }

        .btn-submit-action:hover {
            background: #102A4E;
            box-shadow: 0 6px 18px rgba(16, 42, 78, 0.3);
            transform: translateY(-1px);
        }

        .btn-submit-action:active {
            transform: translateY(0);
        }

        .btn-back-login {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            padding: 11px;
            font-size: 0.88rem;
            font-weight: 600;
            color: #475569;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 12px;
            transition: all 0.2s ease;
        }

        .btn-back-login:hover {
            background: #F1F5F9;
            color: #0F172A;
            border-color: #CBD5E1;
        }

        .error-banner {
            background-color: #FEF2F2;
            border: 1px solid #FEE2E2;
            color: #991B1B;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 0.84rem;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .success-banner {
            background-color: #F0FDF4;
            border: 1px solid #DCFCE7;
            color: #166534;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 0.84rem;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
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
                    <img src="{{ asset('assets/images/bagsakan_team.jpg') }}"
                        alt="Bagsakan Kalidad Food Products Trading Operations" class="showcase-image">
                </div>

                <h1 class="hero-headline">
                    Secure Account
                    <span class="hero-highlight">Recovery Portal.</span>
                </h1>
                <p class="hero-tagline">
                    Enter your email to receive an instant verification link and reset your Bagsakan POS credentials
                    securely.
                </p>
            </div>

            <div class="showcase-footer-text">
                &copy; {{ date('Y') }} Bagsakan Kalidad Food Products Trading. All rights reserved.
            </div>
        </div>

        <!-- Right Form Side -->
        <div class="form-column">
            <div class="form-wrapper">
                <div class="form-brand-header">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Worthy Acosta Logo" class="form-logo-img">
                    <div class="form-brand-kicker">Account Security</div>
                    <h2 class="form-main-title">Forgot Password?</h2>
                    <p class="form-subtitle">No worries! Enter your account email address below and we'll send you a
                        password reset link.</p>
                </div>

                @if (session('status'))
                    <div class="success-banner">
                        <i class="bi bi-check-circle-fill"
                            style="font-size: 1.1rem; color: #16A34A; flex-shrink: 0;"></i>
                        <div>{{ session('status') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="error-banner">
                        <i class="bi bi-exclamation-circle-fill" style="font-size: 1.1rem; flex-shrink: 0;"></i>
                        <div>{{ $errors->first() }}</div>
                    </div>
                @endif

                <form action="{{ route('password.email') }}" method="POST">
                    @csrf

                    <div class="custom-input-group">
                        <label class="custom-label" for="email">Registered Email Address</label>
                        <input type="email" id="email" name="email" class="custom-input"
                            placeholder="e.g. admin@bagsakan.com" value="{{ old('email') }}" required autofocus>
                    </div>

                    <button type="submit" class="btn-submit-action">
                        <i class="bi bi-envelope-paper-fill"></i>
                        <span>Send Reset Link</span>
                    </button>

                    <a href="{{ route('login') }}" class="btn-back-login">
                        <i class="bi bi-arrow-left"></i>
                        <span>Back to Log In</span>
                    </a>
                </form>
            </div>
        </div>
    </div>

    <!-- Global Toast Container -->
    <div id="globalToastContainer" class="toast-container"></div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/bagsakan.js') }}"></script>
    <script>
        @if (session('status'))
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof showToast === 'function') {
                    showToast('success', 'Reset Link Sent', @json(session('status')));
                }
            });
        @endif
    </script>
</body>

</html>
