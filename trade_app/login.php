<?php
require_once 'session.php';

// Already logged in? Go to main app
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// Generate CSRF token if not present
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – NAVTTC Trade Assessment System</title>
    <meta name="description" content="Sign in to the NAVTTC Trade Assessment System.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 1.5rem;
            animation: fadeIn 0.6s ease forwards;
        }

        .login-card {
            padding: 2.5rem;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 8px 32px rgba(99, 102, 241, 0.35);
        }

        .login-logo .icon-circle i {
            font-size: 2rem;
            color: white;
        }

        .login-logo h1 {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.25rem;
        }

        .login-logo p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .login-card .form-group {
            position: relative;
        }

        .login-card .form-group .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            transition: color 0.3s ease;
        }

        .login-card .form-group label + .input-icon {
            top: calc(50% + 12px);
        }

        .login-card .form-control {
            padding-left: 3rem;
        }

        .login-card .form-control:focus + .input-icon,
        .login-card .form-control:focus ~ .input-icon {
            color: var(--primary-color);
        }

        .error-alert {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--danger);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: fadeIn 0.3s ease forwards;
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            font-size: 1.05rem;
            margin-top: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .login-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s ease;
        }

        .login-btn:hover::after {
            left: 100%;
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .login-footer i {
            color: var(--success);
            margin-right: 0.25rem;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card glass">
            <div class="login-logo">
                <img src="assets/logo/navttc.png" alt="NAVTTC Logo" class="login-logo-img" style="height: 70px; width: auto; object-fit: contain; margin-bottom: 1rem;">
                <h1>NAVTTC Institute Assessment</h1>
                <p>Trade Assessment System</p>
            </div>

            <?php if ($error): ?>
                <div class="error-alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login_process.php" id="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" required autocomplete="username">
                    <i class="fa-solid fa-user input-icon"></i>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required autocomplete="current-password">
                    <i class="fa-solid fa-lock input-icon"></i>
                </div>
                <button type="submit" class="btn btn-primary login-btn">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In
                </button>
            </form>

            <div class="login-footer" style="margin-top: 1.5rem; text-align: center; font-size: 0.85rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.5rem; justify-content: center; align-items: center; border-top: 1px solid var(--surface-border); padding-top: 1rem;">
                <div><i class="fa-solid fa-shield-halved" style="color: var(--success); margin-right: 0.25rem;"></i> Secured session-based authentication</div>
                <!-- <div style="font-size: 0.8rem; opacity: 0.8;">Developed with <i class="fa-solid fa-heart" style="color: var(--danger); margin: 0 0.15rem;"></i> by <strong style="color: var(--primary-color);">Ahmed Ali</strong></div> -->
            </div>
        </div>
    </div>

</body>
</html>
