<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>MCRO · civil registry notification</title>
    @vite(['resources/css/welcome.css'])
</head>
<body>
    <div class="page">

        <!-- LOGO ROW: BIGGER, THREE LOGOS -->
        <div class="logo-row">
            <img src="{{ asset('logo/download.png') }}" alt="Tublay seal" class="logo">
            <img src="{{ asset('logo/MCR TUBLAY LOGO..png') }}" alt="MCRO Tublay" class="logo">
            <img src="{{ asset('logo/Bagong_Pilipinas_logo.png') }}" alt="Bagong Pilipinas" class="logo">
        </div>

        <!-- MAIN CARD: NARROWER WIDTH, COMPACT HEIGHT -->
        <div class="welcome-card">
            <div class="welcome-left">
                <div class="welcome-title">
                    MCRO SMS Notification
                </div>

                 <!-- CREATE ACCOUNT — CLEAN TEXT ONLY, REFINED HOVER -->
                <div class="welcome-subtitle">
                    <span class="subtitle-text">Don't have an account?</span>
                    <a href="{{ route('register') }}" class="create-link">
                        Create account <span class="link-arrow">→</span>
                    </a>
                </div>

                <!-- DESCRIPTION MOVED UP -->
                <div class="welcome-description">
                    <p>Official notification system of the Municipal Civil Registrar Office — real‑time updates on petitions, civil registry, and vital documents. Secure, timely, transparent.</p>
                </div>

                <!-- LOGIN BUTTON NOW AFTER DESCRIPTION -->
                <div class="welcome-actions">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary">
                            <span>📁 Dashboard</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">
                            <span>🔐 Log in</span>
                        </a>
                    @endauth
                </div>

               

            </div>

            <div class="welcome-right d-none d-lg-block">
                <img src="{{ asset('images/welcome-pic.jpg') }}" alt="MCRO service" class="welcome-image">
                <div class="soft-vignette"></div>
            </div>
        </div>

        <!-- FOOTER: GROUNDED, COMPACT -->
        <div class="footer-note">
            Municipal Civil Registrar Office – Tublay, Benguet
        </div>
    </div>
</body>
</html>