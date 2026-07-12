<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/connection.php';

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $name    = trim($_POST['patient_name']);
    $cnic    = trim($_POST['cnic']);
    $phone   = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $dob     = trim($_POST['date_of_birth']);
    $gender  = $_POST['gender'];
    $password = $_POST['password'];

    if (empty($name) || empty($cnic) || empty($password)) {
        $error = "Name, CNIC, and Password are required.";
    } else {
        // Check duplicate CNIC
        $check = $con->prepare("SELECT id FROM patients WHERE cnic = ?");
        $check->execute([$cnic]);
        if ($check->rowCount() > 0) {
            $error = "A patient with this CNIC already exists.";
        } else {
            $encPassword = md5($password);
            $stmt = $con->prepare("INSERT INTO patients (patient_name, cnic, phone_number, address, date_of_birth, gender, password) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $cnic, $phone, $address, $dob, $gender, $encPassword]);
            $success = "Account created! You can now <a href='../index.php' style='color:var(--accent-cyan)'>log in</a>.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes">
    <title>Patient Registration | Aroroy PIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ── SAME DESIGN TOKENS AS INDEX.PHP ── */
        :root {
            --bg-deep:      #0a1628;
            --bg-mid:       #0d1f35;
            --bg-panel:     #0f2540;
            --bg-card:      #132d4a;
            --accent-cyan:  #00d4b8;
            --accent-teal:  #00b89c;
            --accent-dark:  #007a68;
            --accent-glow:  rgba(0, 212, 184, 0.25);
            --accent-glow2: rgba(0, 212, 184, 0.08);
            --text-bright:  #e8f4f2;
            --text-soft:    #8db5b0;
            --text-muted:   #4a7a74;
            --border-line:  rgba(0, 212, 184, 0.15);
            --error-bg:     rgba(255, 82, 82, 0.12);
            --error-border: rgba(255, 82, 82, 0.4);
            --error-text:   #ff8080;
            --input-bg:     rgba(0, 212, 184, 0.05);
            --input-border: rgba(0, 212, 184, 0.2);
            --input-focus:  rgba(0, 212, 184, 0.4);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background: var(--bg-deep);
            font-family: 'DM Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }

        /* ── ANIMATED BACKGROUND (same as login) ── */
        .bg-canvas {
            position: fixed; inset: 0; z-index: 0; overflow: hidden;
        }
        .orb {
            position: absolute; border-radius: 50%;
            filter: blur(80px); opacity: 0.35;
            animation: drift 18s ease-in-out infinite;
        }
        .orb-1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #00d4b8 0%, transparent 70%);
            top: -150px; left: -150px; animation-delay: 0s;
        }
        .orb-2 {
            width: 400px; height: 400px;
            background: radial-gradient(circle, #0066aa 0%, transparent 70%);
            bottom: -100px; right: -100px; animation-delay: -6s;
        }
        .orb-3 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, #00d4b8 0%, transparent 70%);
            top: 50%; right: 25%; animation-delay: -12s; opacity: 0.15;
        }
        @keyframes drift {
            0%,100% { transform: translate(0,0) scale(1); }
            33%      { transform: translate(40px,-30px) scale(1.05); }
            66%      { transform: translate(-25px,20px) scale(0.97); }
        }
        .bg-grid {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(var(--border-line) 1px, transparent 1px),
                linear-gradient(90deg, var(--border-line) 1px, transparent 1px);
            background-size: 60px 60px; opacity: 0.3;
        }
        .particle {
            position: absolute; width: 3px; height: 3px;
            background: var(--accent-cyan); border-radius: 50%;
            opacity: 0;
            animation: float-up var(--dur, 8s) var(--delay, 0s) ease-in infinite;
        }
        @keyframes float-up {
            0%   { opacity: 0;   transform: translateY(0) scale(0); }
            10%  { opacity: 0.8; transform: translateY(-20px) scale(1); }
            90%  { opacity: 0.3; }
            100% { opacity: 0;   transform: translateY(-400px) scale(0.5); }
        }

        /* ── MAIN CARD ── */
        .register-wrapper {
            position: relative; z-index: 10;
            width: min(560px, 92vw);
            background: var(--bg-card);
            border-radius: 28px;
            padding: 50px 44px;
            box-shadow:
                0 0 0 1px var(--border-line),
                0 30px 80px rgba(0, 0, 0, 0.6),
                0 0 100px rgba(0, 212, 184, 0.06);
            animation: card-in 0.9s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        @keyframes card-in {
            from { opacity: 0; transform: translateY(40px) scale(0.96); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Logo */
        .logo-area {
            display: flex; align-items: center; gap: 14px;
            margin-bottom: 36px; justify-content: center;
        }
        .logo-ring {
            width: 64px; height: 64px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-teal), #004f60);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 32px rgba(0,212,184,0.3), inset 0 1px 0 rgba(255,255,255,0.1);
        }
        .logo-ring img { width: 54px; height: auto; display: block; }
        .logo-text h2 {
            font-family: 'Syne', sans-serif;
            font-size: 23px; font-weight: 800; color: var(--text-bright);
            letter-spacing: -0.5px;
        }
        .logo-text span { color: var(--accent-cyan); }

        /* Headings */
        .form-eyebrow {
            font-size: 11px; font-weight: 500; letter-spacing: 3px;
            text-transform: uppercase; color: var(--accent-cyan);
            margin-bottom: 8px; text-align: center;
        }
        .form-subtitle {
            margin-bottom: 28px; font-size: 14px; color: var(--text-soft); text-align: center;
        }

        /* Status bar */
        .status-bar {
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 28px; padding: 10px 16px;
            background: rgba(0,212,184,0.04);
            border: 1px solid var(--border-line); border-radius: 30px;
            width: fit-content; margin-left: auto; margin-right: auto;
        }
        .status-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--accent-cyan);
            box-shadow: 0 0 8px var(--accent-cyan);
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%,100% { box-shadow: 0 0 6px var(--accent-cyan); opacity: 1; }
            50%      { box-shadow: 0 0 14px var(--accent-cyan); opacity: 0.7; }
        }
        .status-text { font-size: 12px; color: var(--text-soft); letter-spacing: 0.5px; }

        /* Error / Success */
        .alert-msg {
            padding: 12px 16px; border-radius: 10px; margin-bottom: 24px;
            font-size: 13.5px; display: flex; align-items: center; gap: 10px;
        }
        .alert-error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
        }
        .alert-success {
            background: rgba(0,212,184,0.12);
            border: 1px solid rgba(0,212,184,0.3);
            color: var(--accent-cyan);
        }

        /* Form inputs */
        .input-group {
            margin-bottom: 18px; position: relative;
            animation: fade-up 0.7s calc(0.5s + var(--i, 0) * 0.08s) cubic-bezier(0.16,1,0.3,1) both;
        }
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .input-label {
            display: block; font-size: 12px; font-weight: 500;
            letter-spacing: 1px; text-transform: uppercase;
            color: var(--text-soft); margin-bottom: 8px;
        }
        .input-wrap { position: relative; }
        .input-wrap i.icon-left {
            position: absolute; left: 18px; top: 50%;
            transform: translateY(-50%); color: var(--text-muted);
            font-size: 14px; transition: color 0.3s; pointer-events: none;
        }
        .input-wrap input,
        .input-wrap select {
            width: 100%; padding: 15px 18px 15px 48px;
            background: var(--input-bg); border: 1px solid var(--input-border);
            border-radius: 12px; color: var(--text-bright);
            font-family: 'DM Sans', sans-serif; font-size: 14.5px;
            outline: none; transition: border-color 0.3s, background 0.3s, box-shadow 0.3s;
        }
        .input-wrap input::placeholder { color: var(--text-muted); }
        .input-wrap input:focus,
        .input-wrap select:focus {
            border-color: var(--accent-teal);
            background: rgba(0,212,184,0.07);
            box-shadow: 0 0 0 3px var(--accent-glow2), 0 0 20px rgba(0,212,184,0.08);
        }
        .input-wrap input:focus ~ i.icon-left { color: var(--accent-cyan); }
        .form-row { display: flex; gap: 14px; }
        .form-row .input-group { flex: 1; }

        /* Submit button */
        .btn-login {
            width: 100%; padding: 16px; margin-top: 8px;
            background: linear-gradient(135deg, var(--accent-teal) 0%, #009985 100%);
            color: #fff; border: none; border-radius: 12px;
            font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase; cursor: pointer;
            position: relative; overflow: hidden;
            transition: transform 0.2s, box-shadow 0.3s;
            box-shadow: 0 8px 24px rgba(0,184,156,0.3);
            animation: fade-up 0.7s 0.65s cubic-bezier(0.16,1,0.3,1) both;
        }
        .btn-login::before {
            content: ''; position: absolute; top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,184,156,0.4); }
        .btn-login:hover::before { left: 100%; }
        .btn-login:active { transform: translateY(0); }
        .btn-login .btn-inner { display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-login .btn-arrow {
            width: 28px; height: 28px; border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; transition: transform 0.3s;
        }
        .btn-login:hover .btn-arrow { transform: translateX(4px); }

        /* Footer link */
        .login-link {
            text-align: center; margin-top: 24px;
            font-size: 13px; color: var(--text-muted);
        }
        .login-link a {
            color: var(--accent-teal); text-decoration: none; font-weight: 500; transition: 0.2s;
        }
        .login-link a:hover { color: var(--accent-cyan); }

        /* Responsive */
        @media (max-width: 480px) {
            .register-wrapper { padding: 36px 20px; }
            .form-row { flex-direction: column; gap: 0; }
        }
    </style>
</head>
<body>

<!-- Background (identical to login page) -->
<div class="bg-canvas">
    <div class="bg-grid"></div>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <?php for($i=0;$i<18;$i++): 
        $left  = rand(5,95); $dur   = rand(7,16); $delay = rand(0,12); $top   = rand(10,90);
    ?>
    <div class="particle" style="left:<?=$left?>%;top:<?=$top?>%;--dur:<?=$dur?>s;--delay:-<?=$delay?>s;"></div>
    <?php endfor; ?>
</div>

<!-- Registration Card -->
<div class="register-wrapper">
    <div class="logo-area">
        <div class="logo-ring"><img src="../images/aroroy_logo.png" alt="Aroroy Logo"></div>
        <div class="logo-text"><h2>Aroroy <span>PMS</span></h2></div>
    </div>

    <div class="status-bar">
        <div class="status-dot"></div>
        <span class="status-text">Patient Registration</span>
    </div>

    <div class="form-eyebrow">Create Account</div>
    <p class="form-subtitle">Fill in your details to access the patient portal</p>

    <?php if ($error): ?>
        <div class="alert-msg alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($success): ?>
        <div class="alert-msg alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group" style="--i:0">
            <label class="input-label">Full Name</label>
            <div class="input-wrap">
                <input type="text" name="patient_name" required placeholder="e.g. Juan Dela Cruz">
                <i class="fas fa-user icon-left"></i>
            </div>
        </div>
        <div class="input-group" style="--i:1">
            <label class="input-label">ID Number</label>
            <div class="input-wrap">
                <input type="text" name="cnic" required placeholder="e.g. 12-3456789-0">
                <i class="fas fa-id-card icon-left"></i>
            </div>
        </div>
        <div class="form-row">
            <div class="input-group" style="--i:2">
                <label class="input-label">Phone</label>
                <div class="input-wrap">
                    <input type="text" name="phone_number" placeholder="09xxxxxxxxx">
                    <i class="fas fa-phone icon-left"></i>
                </div>
            </div>
            <div class="input-group" style="--i:3">
                <label class="input-label">Age</label>
                <div class="input-wrap">
                    <input type="number" name="age" placeholder="e.g. 25">
                    <i class="fas fa-calendar icon-left"></i>
                </div>
            </div>
        </div>
        <div class="input-group" style="--i:4">
            <label class="input-label">Address</label>
            <div class="input-wrap">
                <input type="text" name="address" placeholder="Street, Barangay, City">
                <i class="fas fa-map-marker-alt icon-left"></i>
            </div>
        </div>
        <div class="form-row">
            <div class="input-group" style="--i:5">
                <label class="input-label">Date of Birth</label>
                <div class="input-wrap">
                    <input type="date" name="date_of_birth">
                    <i class="fas fa-calendar-alt icon-left"></i>
                </div>
            </div>
            <div class="input-group" style="--i:6">
                <label class="input-label">Gender</label>
                <div class="input-wrap">
                    <select name="gender">
                        <option value="" disabled selected>Select...</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                    <i class="fas fa-venus-mars icon-left"></i>
                </div>
            </div>
        </div>
        <div class="input-group" style="--i:7">
            <label class="input-label">Password</label>
            <div class="input-wrap">
                <input type="password" name="password" required placeholder="Create a password" id="password">
                <i class="fas fa-lock icon-left"></i>
                <button type="button" class="toggle-pw" onclick="togglePw()" title="Show/hide" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);color:var(--text-muted);background:none;border:none;cursor:pointer;font-size:14px;">
                    <i class="fas fa-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>
        <button type="submit" name="register" class="btn-login">
            <span class="btn-inner">
                Register
                <span class="btn-arrow"><i class="fas fa-arrow-right"></i></span>
            </span>
        </button>
    </form>

    <div class="login-link">
        Already have an account? <a href="../index.php">Sign in</a>
    </div>
</div>

<script>
    function togglePw() {
        const pw  = document.getElementById('password');
        const ico = document.getElementById('eyeIcon');
        const show = pw.type === 'password';
        pw.type  = show ? 'text' : 'password';
        ico.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
    }
    function setVH() {
        let vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
    window.addEventListener('resize', setVH);
    setVH();
</script>
</body>
</html>