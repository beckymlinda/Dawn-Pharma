<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EDUC Pharmacy - Professional Pharmacy Management System</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>

<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <!-- Left Column - Content -->
                <div class="col-lg-6 col-md-12 hero-content" data-aos="fade-right">
                    <div class="logo-container mb-4">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="EDUC Pharmacy" class="main-logo">
                    </div>
                    
                    <h1 class="hero-title">
                        Welcome to <span class="brand-highlight">EDUC Pharmacy</span>
                    </h1>
                    
                    <p class="hero-subtitle">
                        A modern, intelligent pharmacy management system designed to streamline your operations. 
                        Manage medicines, sales, inventory, and comprehensive reports with cutting-edge technology.
                    </p>
                    
                    <div class="hero-features mb-5">
                        <div class="feature-item">
                            <i class="fas fa-pills"></i>
                            <span>Smart Medicine Management</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-chart-line"></i>
                            <span>Real-time Analytics</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-warehouse"></i>
                            <span>Inventory Control</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-file-medical"></i>
                            <span>Digital Reports</span>
                        </div>
                    </div>
                    
                    <div class="hero-buttons">
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Login to System
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>
                            Get Started
                        </a>
                    </div>
                    
                    <div class="trust-badges mt-4">
                        <span class="badge-item">
                            <i class="fas fa-shield-alt"></i>
                            Secure & Reliable
                        </span>
                        <span class="badge-item">
                            <i class="fas fa-user-md"></i>
                            Healthcare Compliant
                        </span>
                        <span class="badge-item">
                            <i class="fas fa-mobile-alt"></i>
                            Mobile Friendly
                        </span>
                    </div>
                </div>
                
                <!-- Right Column - AI Generated Image -->
                <div class="col-lg-6 col-md-12 hero-image" data-aos="fade-left">
                    <div class="image-container">
                        <img src="https://images.unsplash.com/photo-1559757148-5c350d0d3c56?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80" 
                             alt="Modern Pharmacy Management" 
                             class="hero-img">
                        <div class="floating-cards">
                            <div class="floating-card card-1" data-aos="zoom-in" data-aos-delay="200">
                                <i class="fas fa-capsules"></i>
                                <span>Medicine Tracking</span>
                            </div>
                            <div class="floating-card card-2" data-aos="zoom-in" data-aos-delay="400">
                                <i class="fas fa-cash-register"></i>
                                <span>POS System</span>
                            </div>
                            <div class="floating-card card-3" data-aos="zoom-in" data-aos-delay="600">
                                <i class="fas fa-chart-bar"></i>
                                <span>Analytics</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features-section py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4" data-aos="fade-up">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h3>Fast & Efficient</h3>
                        <p>Streamlined workflows designed for pharmacy professionals to save time and increase productivity.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h3>Secure & Compliant</h3>
                        <p>Healthcare-grade security with HIPAA compliance and encrypted data storage.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3>24/7 Support</h3>
                        <p>Round-the-clock technical support and training for your pharmacy team.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,96C1248,75,1344,53,1392,42.7L1440,32L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .logo-container {
            text-align: center;
        }

        .main-logo {
            height: 80px;
            width: auto;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .brand-highlight {
            background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            line-height: 1.6;
            font-weight: 300;
        }

        .hero-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
        }

        .feature-item i {
            margin-right: 0.75rem;
            font-size: 1.2rem;
            color: #f5576c;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 87, 108, 0.4);
            color: white;
        }

        .btn-outline-primary {
            background: transparent;
            border: 2px solid white;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: white;
            color: #764ba2;
            transform: translateY(-2px);
        }

        .trust-badges {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .badge-item {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: white;
            font-size: 0.85rem;
            backdrop-filter: blur(10px);
        }

        .badge-item i {
            margin-right: 0.5rem;
            color: #f5576c;
        }

        /* Hero Image */
        .hero-image {
            position: relative;
        }

        .image-container {
            position: relative;
            text-align: center;
        }

        .hero-img {
            width: 100%;
            max-width: 500px;
            height: auto;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: float 4s ease-in-out infinite;
        }

        .floating-cards {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }

        .floating-card {
            position: absolute;
            background: white;
            padding: 1rem;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: #333;
            animation: float 3s ease-in-out infinite;
        }

        .floating-card i {
            color: #f5576c;
            font-size: 1.2rem;
        }

        .card-1 {
            top: 10%;
            right: -10%;
            animation-delay: 0s;
        }

        .card-2 {
            top: 50%;
            left: -15%;
            animation-delay: 1s;
        }

        .card-3 {
            bottom: 15%;
            right: -5%;
            animation-delay: 2s;
        }

        /* Features Section */
        .features-section {
            background: white;
            position: relative;
        }

        .feature-card {
            text-align: center;
            padding: 2rem;
            border-radius: 15px;
            background: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .feature-card h3 {
            color: #333;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .hero-features {
                grid-template-columns: 1fr;
            }

            .hero-buttons {
                justify-content: center;
            }

            .btn-lg {
                padding: 0.8rem 1.5rem;
                font-size: 0.9rem;
            }

            .floating-card {
                display: none;
            }

            .trust-badges {
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .hero-title {
                font-size: 2rem;
            }

            .hero-content {
                text-align: center;
            }

            .logo-container {
                margin-bottom: 2rem;
            }
        }
    </style>

    <!-- AOS Animation Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });
    </script>
</body>
</html>