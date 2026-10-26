
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create an Account | TechConnect</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .register-section {
            min-height: 75vh;
            padding: 60px 20px;
            background: #f4f8ff;
        }

        .register-header {
            text-align: center;
            max-width: 650px;
            margin: 0 auto 40px;
        }

        .register-header h1 {
            color: #123b70;
            font-size: 36px;
            margin-bottom: 12px;
        }

        .register-header p {
            color: #64748b;
            font-size: 16px;
            line-height: 1.7;
        }

        .register-cards {
            display: flex;
            justify-content: center;
            align-items: stretch;
            flex-wrap: wrap;
            gap: 25px;
            max-width: 950px;
            margin: auto;
        }

        .register-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 35px 30px;
            flex: 1;
            min-width: 260px;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(18, 59, 112, 0.07);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .register-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 35px rgba(18, 59, 112, 0.12);
        }

        .register-icon {
            width: 75px;
            height: 75px;
            margin: 0 auto 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #e8f1ff;
            color: #1769d2;
            font-size: 34px;
        }

        .register-card h2 {
            color: #123b70;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .register-card p {
            color: #64748b;
            line-height: 1.8;
            margin-bottom: 25px;
        }

        .register-button {
            display: inline-block;
            width: 100%;
            box-sizing: border-box;
            padding: 14px 18px;
            background: #1769d2;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .register-button:hover {
            background: #0f4fa8;
        }

        .register-login {
            text-align: center;
            margin-top: 35px;
            color: #64748b;
        }

        .register-login a {
            color: #1769d2;
            font-weight: 600;
            text-decoration: none;
        }

        .register-login a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .register-section {
                padding: 40px 15px;
            }

            .register-header h1 {
                font-size: 29px;
            }

            .register-card {
                padding: 28px 22px;
            }
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <header class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">TechConnect</a>

            <nav>
                <a href="index.php">Home</a>
                <a href="technicians.php">Find Technicians</a>
                <a href="login.php">Login</a>
            </nav>
        </div>
    </header>

    <!-- Registration Selection -->
    <main class="register-section">

        <div class="register-header">
            <h1>Join TechConnect</h1>

            <p>
                Connect with skilled professionals or grow your
                business. Choose the account type that best
                describes you to get started.
            </p>
        </div>

        <div class="register-cards">

            <!-- Customer Card -->
            <div class="register-card">

                <div class="register-icon" aria-hidden="true">
                    &#128100;
                </div>

                <h2>I'm a Customer</h2>

                <p>
                    Find trusted technicians, explore their skills,
                    view previous work, and request services
                    for your home or business.
                </p>

                <a href="customer-register.php"
                   class="register-button">
                    Register as Customer
                </a>

            </div>

            <!-- Technician Card -->
            <div class="register-card">

                <div class="register-icon" aria-hidden="true">
                    &#128736;
                </div>

                <h2>I'm a Technician</h2>

                <p>
                    Create your professional profile, showcase
                    your experience and previous work, and
                    connect with customers looking for your skills.
                </p>

                <a href="technician-register.php"
                   class="register-button">
                    Register as Technician
                </a>

            </div>

        </div>

        <div class="register-login">
            Already have an account?
            <a href="login.php">Log in here</a>
        </div>

    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>
                &copy; <?php echo date("Y"); ?>
                TechConnect. All rights reserved.
            </p>
        </div>
    </footer>

</body>
</html>
