<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include './config/connection.php';

$message = '';
$activeTab = 'admin'; // default

if (isset($_POST['login'])) {
    $tab = $_POST['tab'] ?? 'admin';
    $activeTab = $tab;

    if ($tab === 'admin') {
        // --- Admin Login ---
        $userName = trim($_POST['user_name']);
        $password = $_POST['password'];
        $encryptedPassword = md5($password);

        $query = "SELECT `id`, `display_name`, `user_name`, `profile_picture` 
                  FROM `users` 
                  WHERE `user_name` = :user AND `password` = :pass";

        try {
            $stmtLogin = $con->prepare($query);
            $stmtLogin->bindParam(':user', $userName);
            $stmtLogin->bindParam(':pass', $encryptedPassword);
            $stmtLogin->execute();

            if ($stmtLogin->rowCount() == 1) {
                $row = $stmtLogin->fetch(PDO::FETCH_ASSOC);
                $_SESSION['user_id']        = $row['id'];
                $_SESSION['display_name']   = $row['display_name'];
                $_SESSION['user_name']      = $row['user_name'];
                $_SESSION['profile_picture']= $row['profile_picture'];
                header("Location: dashboard.php");
                exit;
            } else {
                $message = 'Invalid Username or Password.';
            }
        } catch (PDOException $ex) {
            $message = "Database Error: " . $ex->getMessage();
        }
    } elseif ($tab === 'patient') {
        // --- Patient Login ---
        $cnic = trim($_POST['cnic']);
        $password = $_POST['password'];
        $encryptedPassword = md5($password);

        $query = "SELECT `id`, `patient_name` FROM `patients` WHERE `cnic` = :cnic AND `password` = :pass";
        try {
            $stmtPatient = $con->prepare($query);
            $stmtPatient->execute([':cnic' => $cnic, ':pass' => $encryptedPassword]);

            if ($stmtPatient->rowCount() == 1) {
                $row = $stmtPatient->fetch(PDO::FETCH_ASSOC);
                $_SESSION['patient_id']   = $row['id'];
                $_SESSION['patient_name'] = $row['patient_name'];
                header("Location: patient/patient_dashboard.php");
                exit;
            } else {
                $message = 'Invalid ID or Password.';
            }
        } catch (PDOException $ex) {
            $message = "Database Error: " . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes, viewport-fit=cover">
    <title>Login | Aroroy PIS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ── DESIGN TOKENS ── */
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

        /* ── ANIMATED BACKGROUND ── */
        .bg-canvas { position: fixed; inset: 0; z-index: 0; overflow: hidden; }
        .orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.35; animation: drift 18s ease-in-out infinite; }
        .orb-1 { width: 500px; height: 500px; background: radial-gradient(circle, #00d4b8 0%, transparent 70%); top: -150px; left: -150px; animation-delay: 0s; }
        .orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, #0066aa 0%, transparent 70%); bottom: -100px; right: -100px; animation-delay: -6s; }
        .orb-3 { width: 300px; height: 300px; background: radial-gradient(circle, #00d4b8 0%, transparent 70%); top: 50%; right: 25%; animation-delay: -12s; opacity: 0.15; }
        @keyframes drift { 0%,100% { transform: translate(0, 0) scale(1); } 33% { transform: translate(40px, -30px) scale(1.05); } 66% { transform: translate(-25px, 20px) scale(0.97); } }
        .bg-grid { position: absolute; inset: 0; background-image: linear-gradient(var(--border-line) 1px, transparent 1px), linear-gradient(90deg, var(--border-line) 1px, transparent 1px); background-size: 60px 60px; opacity: 0.3; }
        .particle { position: absolute; width: 3px; height: 3px; background: var(--accent-cyan); border-radius: 50%; opacity: 0; animation: float-up var(--dur, 8s) var(--delay, 0s) ease-in infinite; }
        @keyframes float-up { 0% { opacity: 0; transform: translateY(0) scale(0); } 10% { opacity: 0.8; transform: translateY(-20px) scale(1); } 90% { opacity: 0.3; } 100% { opacity: 0; transform: translateY(-400px) scale(0.5); } }

        /* ── MAIN LAYOUT ── */
        .login-wrapper {
            position: relative; z-index: 10;
            display: flex; width: min(920px, 96vw); min-height: 560px;
            border-radius: 24px; overflow: hidden;
            box-shadow: 0 0 0 1px var(--border-line), 0 30px 80px rgba(0,0,0,0.6), 0 0 100px rgba(0,212,184,0.06);
            animation: card-in 0.9s cubic-bezier(0.16,1,0.3,1) both;
        }
        @keyframes card-in { from { opacity: 0; transform: translateY(40px) scale(0.96); } to { opacity: 1; transform: translateY(0) scale(1); } }

        /* ── LEFT PANEL ── */
        .panel-left {
            flex: 1.1;
            background: linear-gradient(145deg, #0d2540 0%, #0a1e35 50%, #071628 100%);
            padding: 60px 50px;
            display: flex; flex-direction: column; justify-content: space-between;
            position: relative; overflow: hidden; border-right: 1px solid var(--border-line);
        }
        .panel-left::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(ellipse 60% 50% at 20% 80%, rgba(0,212,184,0.08) 0%, transparent 60%),
                        radial-gradient(ellipse 40% 40% at 80% 20%, rgba(0,100,180,0.1) 0%, transparent 60%);
        }
        .brand-block { position: relative; z-index: 2; animation: fade-up 0.7s 0.3s cubic-bezier(0.16,1,0.3,1) both; }
        @keyframes fade-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .logo-ring {
            width: 80px; height: 80px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-teal), #004f60);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0,212,184,0.3), inset 0 1px 0 rgba(255,255,255,0.1);
            position: relative;
        }
        .logo-ring::after { content: ''; position: absolute; inset: -2px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-cyan), transparent 60%); z-index: -1; opacity: 0.6; }
        .logo-ring i { font-size: 32px; color: #fff; }
        .logo-ring img { max-width: 100%; height: auto; display: block; }
        .brand-name { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 800; color: var(--text-bright); letter-spacing: -0.5px; line-height: 1.1; }
        .brand-name span { color: var(--accent-cyan); }
        .brand-desc { margin-top: 14px; font-size: 14px; color: var(--text-soft); line-height: 1.7; max-width: 240px; }
        .feature-list { position: relative; z-index: 2; display: flex; flex-direction: column; gap: 14px; animation: fade-up 0.7s 0.5s cubic-bezier(0.16,1,0.3,1) both; }
        .feature-item {
            display: flex; align-items: center; gap: 14px; padding: 14px 18px;
            background: rgba(0,212,184,0.04); border: 1px solid var(--border-line); border-radius: 12px;
            transition: border-color 0.3s, background 0.3s;
        }
        .feature-item:hover { border-color: rgba(0,212,184,0.35); background: rgba(0,212,184,0.07); }
        .feature-icon { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, rgba(0,212,184,0.2), rgba(0,212,184,0.05)); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .feature-icon i { font-size: 14px; color: var(--accent-cyan); }
        .feature-text strong { display: block; font-size: 13px; font-weight: 600; color: var(--text-bright); font-family: 'Syne', sans-serif; }
        .feature-text span { font-size: 12px; color: var(--text-soft); }
        .deco-ring { position: absolute; bottom: -80px; right: -80px; width: 280px; height: 280px; border-radius: 50%; border: 1px solid var(--border-line); z-index: 1; }
        .deco-ring::before { content: ''; position: absolute; inset: 30px; border-radius: 50%; border: 1px solid rgba(0,212,184,0.1); }
        .deco-ring::after { content: ''; position: absolute; inset: 60px; border-radius: 50%; border: 1px solid rgba(0,212,184,0.06); }

        /* ── RIGHT PANEL ── */
        .panel-right {
            flex: 1; background: var(--bg-card); padding: 55px 50px;
            display: flex; flex-direction: column; justify-content: center;
            position: relative; overflow: hidden;
        }
        .panel-right::before { content: ''; position: absolute; top: -60px; right: -60px; width: 220px; height: 220px; border-radius: 50%; background: radial-gradient(circle, rgba(0,212,184,0.06) 0%, transparent 70%); }
        .status-bar {
            display: flex; align-items: center; gap: 8px; margin-bottom: 32px;
            padding: 10px 16px; background: rgba(0,212,184,0.04);
            border: 1px solid var(--border-line); border-radius: 30px; width: fit-content;
            animation: fade-up 0.7s 0.35s cubic-bezier(0.16,1,0.3,1) both;
        }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent-cyan); box-shadow: 0 0 8px var(--accent-cyan); animation: pulse-dot 2s ease-in-out infinite; }
        @keyframes pulse-dot { 0%,100% { box-shadow: 0 0 6px var(--accent-cyan); opacity: 1; } 50% { box-shadow: 0 0 14px var(--accent-cyan); opacity: 0.7; } }
        .status-text { font-size: 12px; color: var(--text-soft); letter-spacing: 0.5px; }

        /* ── TABS ── */
        .login-tabs {
            display: flex; gap: 0; margin-bottom: 28px;
            border-bottom: 1px solid var(--border-line);
        }
        .login-tab {
            flex: 1; text-align: center; padding: 10px; cursor: pointer;
            font-weight: 600; font-size: 0.85rem; color: var(--text-soft);
            border-bottom: 2px solid transparent; transition: all 0.3s;
            font-family: 'Syne', sans-serif; letter-spacing: 0.5px;
        }
        .login-tab.active { color: var(--accent-cyan); border-bottom-color: var(--accent-cyan); }
        .admin-form, .patient-form { display: none; }
        .admin-form.active, .patient-form.active { display: block; }

        /* ── FORMS ── */
        .form-header { margin-bottom: 36px; animation: fade-up 0.7s 0.4s cubic-bezier(0.16,1,0.3,1) both; }
        .form-eyebrow { font-size: 11px; font-weight: 500; letter-spacing: 3px; text-transform: uppercase; color: var(--accent-cyan); margin-bottom: 8px; }
        .form-subtitle { margin-top: 8px; font-size: 14px; color: var(--text-soft); }
        .error-msg {
            display: flex; align-items: center; gap: 10px; background: var(--error-bg);
            border: 1px solid var(--error-border); color: var(--error-text);
            padding: 12px 16px; border-radius: 10px; margin-bottom: 24px; font-size: 13.5px;
            animation: shake 0.4s ease, fade-up 0.3s ease;
        }
        @keyframes shake { 0%,100% { transform: translateX(0); } 25% { transform: translateX(-6px); } 75% { transform: translateX(6px); } }
        .input-group { margin-bottom: 18px; position: relative; animation: fade-up 0.7s calc(0.5s + var(--i, 0) * 0.08s) cubic-bezier(0.16,1,0.3,1) both; }
        .input-label { display: block; font-size: 12px; font-weight: 500; letter-spacing: 1px; text-transform: uppercase; color: var(--text-soft); margin-bottom: 8px; }
        .input-wrap { position: relative; }
        .input-wrap i.icon-left { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px; transition: color 0.3s; pointer-events: none; }
        .input-wrap input {
            width: 100%; padding: 15px 18px 15px 48px;
            background: var(--input-bg); border: 1px solid var(--input-border);
            border-radius: 12px; color: var(--text-bright);
            font-family: 'DM Sans', sans-serif; font-size: 14.5px; outline: none;
            transition: border-color 0.3s, background 0.3s, box-shadow 0.3s;
        }
        .input-wrap input::placeholder { color: var(--text-muted); }
        .input-wrap input:focus { border-color: var(--accent-teal); background: rgba(0,212,184,0.07); box-shadow: 0 0 0 3px var(--accent-glow2), 0 0 20px rgba(0,212,184,0.08); }
        .input-wrap input:focus ~ i.icon-left { color: var(--accent-cyan); }
        .toggle-pw { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); cursor: pointer; font-size: 14px; transition: color 0.2s; background: none; border: none; padding: 4px; }
        .toggle-pw:hover { color: var(--accent-cyan); }

        .btn-login {
            width: 100%; padding: 16px; margin-top: 8px;
            background: linear-gradient(135deg, var(--accent-teal) 0%, #009985 100%);
            color: #fff; border: none; border-radius: 12px;
            font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase; cursor: pointer;
            position: relative; overflow: hidden; transition: transform 0.2s, box-shadow 0.3s;
            box-shadow: 0 8px 24px rgba(0,184,156,0.3);
            animation: fade-up 0.7s 0.65s cubic-bezier(0.16,1,0.3,1) both;
        }
        .btn-login::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent); transition: left 0.5s; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,184,156,0.4); }
        .btn-login:hover::before { left: 100%; }
        .btn-login:active { transform: translateY(0); }
        .btn-login .btn-inner { display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn-login .btn-arrow { width: 28px; height: 28px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 12px; transition: transform 0.3s; }
        .btn-login:hover .btn-arrow { transform: translateX(4px); }

        .form-footer { margin-top: 28px; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 13px; color: var(--text-muted); animation: fade-up 0.7s 0.7s cubic-bezier(0.16,1,0.3,1) both; }
        .form-footer a { color: var(--accent-teal); text-decoration: none; font-weight: 500; transition: color 0.2s; }
        .form-footer a:hover { color: var(--accent-cyan); }
        .dot-sep { color: var(--text-muted); }

        /* Mobile styles (unchanged, only adjusted tab layout) */
        @media (max-width: 700px) {
            body { align-items: center; justify-content: center; min-height: 100vh; padding: 16px; overflow-y: auto; }
            .login-wrapper { flex-direction: column; width: 92vw; max-width: 420px; min-height: auto; margin: 0 auto; border-radius: 28px; background: transparent; box-shadow: 0 0 0 1px var(--border-line), 0 20px 50px rgba(0,0,0,0.5); }
            .panel-left { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 32px 24px 20px 24px; background: linear-gradient(145deg, #0a1e35 0%, #071628 100%); border-right: none; border-bottom: 1px solid var(--border-line); flex: auto; }
            .panel-left::before { opacity: 0.5; }
            .feature-list, .deco-ring, .brand-desc { display: none; }
            .brand-block { display: flex; flex-direction: column; align-items: center; width: 100%; animation: none; }
            .logo-ring { width: 90px; height: 90px; margin-bottom: 16px; box-shadow: 0 8px 28px rgba(0,212,184,0.4); }
            .logo-ring img { width: 80px; height: auto; }
            .brand-name { font-size: 28px; text-align: center; margin-bottom: 4px; }
            .panel-right { padding: 36px 24px 40px 24px; background: var(--bg-card); border-radius: 0 0 28px 28px; display: flex; flex-direction: column; justify-content: center; }
            .status-bar { margin-bottom: 24px; width: 100%; justify-content: center; }
            .form-header { margin-bottom: 28px; text-align: center; }
            .form-eyebrow { font-size: 32px !important; letter-spacing: 1px; line-height: 1.2; margin-bottom: 6px; text-transform: none; font-weight: 700; color: var(--text-bright); }
            .login-tabs { margin-bottom: 20px; }
            .login-tab { font-size: 0.9rem; }
        }
    </style>
</head>
<body>

<!-- Background (unchanged) -->
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

<!-- Login Card -->
<div class="login-wrapper">
    <!-- LEFT PANEL (identical to original) -->
    <div class="panel-left">
        <div class="deco-ring"></div>
        <div class="brand-block">
            <div class="logo-ring"><img src="images/aroroy_logo.png" alt="Aroroy Logo"></div>
            <div class="brand-name">Aroroy <span>PIS</span></div>
            <p class="brand-desc">A secure and modern patient management platform built for healthcare professionals.</p>
        </div>
        <div class="feature-list">
            <div class="feature-item"><div class="feature-icon"><i class="fas fa-user-injured"></i></div><div class="feature-text"><strong>Patient Records</strong><span>Manage and track patient data</span></div></div>
            <div class="feature-item"><div class="feature-icon"><i class="fas fa-calendar-check"></i></div><div class="feature-text"><strong>Appointments</strong><span>Manage and track patient appointments</span></div></div>
            <div class="feature-item"><div class="feature-icon"><i class="fas fa-pills"></i></div><div class="feature-text"><strong>Medicine Inventory</strong><span>Track stock and prescriptions</span></div></div>
            <div class="feature-item"><div class="feature-icon"><i class="fas fa-chart-line"></i></div><div class="feature-text"><strong>Reports</strong><span>Insights at a glance</span></div></div>
        </div>
    </div>

    <!-- RIGHT PANEL with tabs -->
    <div class="panel-right">
        <div class="status-bar">
            <div class="status-dot"></div>
            <span class="status-text">System Online &nbsp;·&nbsp; Secure Connection</span>
        </div>

        <!-- Tabs -->
        <div class="login-tabs">
            <div class="login-tab active" onclick="switchTab('admin')">Administrator</div>
            <div class="login-tab" onclick="switchTab('patient')">Patient Portal</div>
        </div>

        <!-- Administrator Form -->
        <div class="admin-form active" id="adminForm">
            <div class="form-header">
                <div class="form-eyebrow">Administrator Portal</div>
                <p class="form-subtitle">Sign in to access your dashboard</p>
            </div>
            <?php if($message && $activeTab == 'admin'): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="tab" value="admin">
                <div class="input-group" style="--i:0">
                    <label class="input-label">Username</label>
                    <div class="input-wrap">
                        <input type="text" name="user_name" placeholder="Enter your username" required>
                        <i class="fas fa-user icon-left"></i>
                    </div>
                </div>
                <div class="input-group" style="--i:1">
                    <label class="input-label">Password</label>
                    <div class="input-wrap">
                        <input type="password" name="password" id="adminPassword" placeholder="Enter your password" required>
                        <i class="fas fa-lock icon-left"></i>
                        <button type="button" class="toggle-pw" onclick="toggleAdminPw()" title="Show/hide"><i class="fas fa-eye" id="adminEyeIcon"></i></button>
                    </div>
                </div>
                <button type="submit" name="login" class="btn-login">
                    <span class="btn-inner">Log In <span class="btn-arrow"><i class="fas fa-arrow-right"></i></span></span>
                </button>
            </form>
            <div class="form-footer">
                <span>Secure Connection</span>
            </div>
        </div>

        <!-- Patient Form -->
        <div class="patient-form" id="patientForm">
            <div class="form-header">
                <div class="form-eyebrow">Patient Portal</div>
                <p class="form-subtitle">Access your appointments</p>
            </div>
            <?php if($message && $activeTab == 'patient'): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="tab" value="patient">
                <div class="input-group" style="--i:0">
                    <label class="input-label">ID Number</label>
                    <div class="input-wrap">
                        <input type="text" name="cnic" placeholder="Enter your ID number" required>
                        <i class="fas fa-id-card icon-left"></i>
                    </div>
                </div>
                <div class="input-group" style="--i:1">
                    <label class="input-label">Password</label>
                    <div class="input-wrap">
                        <input type="password" name="password" id="patientPassword" placeholder="Enter your password" required>
                        <i class="fas fa-lock icon-left"></i>
                        <button type="button" class="toggle-pw" onclick="togglePatientPw()" title="Show/hide"><i class="fas fa-eye" id="patientEyeIcon"></i></button>
                    </div>
                </div>
                <button type="submit" name="login" class="btn-login">
                    <span class="btn-inner">Log In <span class="btn-arrow"><i class="fas fa-arrow-right"></i></span></span>
                </button>
            </form>
            <div class="form-footer">
                <span>New patient?</span> <a href="./patient/patient_register.php">Create an account</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Tab switching
    function switchTab(tab) {
        document.querySelectorAll('.login-tab').forEach(el => el.classList.remove('active'));
        document.querySelector(`.login-tab[onclick="switchTab('${tab}')"]`).classList.add('active');
        document.getElementById('adminForm').classList.toggle('active', tab === 'admin');
        document.getElementById('patientForm').classList.toggle('active', tab === 'patient');
    }

    // Toggle admin password
    function toggleAdminPw() {
        const pw = document.getElementById('adminPassword');
        const ico = document.getElementById('adminEyeIcon');
        const show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        ico.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
    }

    // Toggle patient password
    function togglePatientPw() {
        const pw = document.getElementById('patientPassword');
        const ico = document.getElementById('patientEyeIcon');
        const show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
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