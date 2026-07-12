<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['patient_id'])) {
    header("Location: ../index.php"); 
    exit;
}
include '../config/connection.php';
$patient_id   = $_SESSION['patient_id'];
$patient_name = $_SESSION['patient_name'] ?? 'Patient';

$stmt = $con->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

$msg = '';
if (isset($_POST['book_appointment'])) {
    $date   = $_POST['appointment_date'];
    $time   = $_POST['appointment_time'];
    $reason = trim($_POST['reason']);
    if ($date && $time && $reason) {
        $stmt = $con->prepare("INSERT INTO appointments (patient_id, appointment_date, appointment_time, reason) VALUES (?,?,?,?)");
        $stmt->execute([$patient_id, $date, $time, $reason]);
        $msg = '<div class="alert success">Appointment request submitted. Awaiting approval.</div>';
    } else {
        $msg = '<div class="alert error">Please fill all fields.</div>';
    }
}

$appts = $con->prepare("SELECT * FROM appointments WHERE patient_id = ? ORDER BY id DESC");
$appts->execute([$patient_id]);
$appointments = $appts->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard | Aroroy PIS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========== DESIGN TOKENS & SIDEBAR STYLES (unchanged) ========== */
        :root {
            --navy: #0b1426; --navy-light: #152039; --navy-mid: #1e2d4a;
            --teal: #00d4aa; --teal-dark: #00b891; --teal-glow: rgba(0, 212, 170, 0.15);
            --amber: #ffb340; --rose: #ff6b8a; --violet: #a78bfa; --sky: #38bdf8;
            --white: #ffffff; --off-white: #f8fafd;
            --text-primary: #1a2642; --text-secondary: #5e7494; --text-muted: #9bb0c9;
            --border: rgba(255,255,255,0.07);
            --card-shadow: 0 4px 24px rgba(11, 20, 38, 0.08);
            --card-hover-shadow: 0 16px 48px rgba(11, 20, 38, 0.16);
            --sidebar-width: 272px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: var(--off-white); color: var(--text-primary); overflow-x: hidden; }

        /* ===== SIDEBAR ===== */
        .sidebar { position: fixed; left: 0; top: 0; width: var(--sidebar-width); height: 100%; background: var(--navy); z-index: 1000; display: flex; flex-direction: column; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; }
        .sidebar::before { content: ''; position: absolute; width: 350px; height: 350px; background: radial-gradient(circle, rgba(24,222,182,0.45) 0%, transparent 70%); top: -80px; left: -80px; border-radius: 50%; pointer-events: none; }
        .sidebar::after { content: ''; position: absolute; width: 250px; height: 250px; background: radial-gradient(circle, rgba(159,147,192,0.18) 0%, transparent 70%); bottom: 80px; right: -60px; border-radius: 50%; pointer-events: none; }
        .sidebar-scroll { overflow-y: auto; flex: 1; position: relative; z-index: 1; }
        .sidebar-scroll::-webkit-scrollbar { width: 3px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(0,212,170,0.3); border-radius: 10px; }
        .sidebar-logo { padding: 28px 24px 20px; border-bottom: 1px solid var(--border); }
        .logo-mark { display: flex; align-items: center; gap: 12px; }
        .logo-icon { width: 50px; height: 50px; background: linear-gradient(135deg, var(--teal), #00a085); border-radius: 40%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: white; box-shadow: 0 8px 20px rgba(0,212,170,0.3); flex-shrink: 0; }
        .logo-icon img { width: 50px; height: 50px; object-fit: cover; border-radius: 40%; }
        .logo-text h3 { font-size: 1.15rem; font-weight: 800; color: white; letter-spacing: -0.3px; }
        .sidebar-user { padding: 20px 24px; border-bottom: 1px solid var(--border); }
        .user-card { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: var(--navy-light); border-radius: 14px; border: 1px solid var(--border); }
        .user-avatar { width: 42px; height: 42px; border-radius: 50%; overflow: hidden; border: 2px solid var(--teal); flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--navy-mid); color: white; font-size: 1.2rem; }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-card-info h4 { font-size: 0.85rem; font-weight: 600; color: white; line-height: 1.2; }
        .user-card-info p { font-size: 0.7rem; color: var(--text-muted); margin-top: 2px; }
        .user-status { width: 8px; height: 8px; background: var(--teal); border-radius: 50%; margin-left: auto; box-shadow: 0 0 8px var(--teal); animation: pulse-dot 2s infinite; }
        @keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.6;transform:scale(0.85)} }
        .nav-section { padding: 16px 16px 0; }
        .nav-label { font-size: 0.65rem; font-weight: 600; color: rgba(255,255,255,0.25); letter-spacing: 1.8px; text-transform: uppercase; padding: 0 8px 8px; margin-top: 8px; }
        .nav-item { list-style: none; margin: 2px 0; }
        .nav-link { display: flex; align-items: center; padding: 10px 12px; color: rgba(255,255,255,0.55); text-decoration: none; border-radius: 10px; gap: 10px; font-size: 0.88rem; font-weight: 500; transition: all 0.25s; }
        .nav-link .nav-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem; background: rgba(255,255,255,0.05); flex-shrink: 0; transition: all 0.25s; }
        .nav-link:hover { background: rgba(255,255,255,0.06); color: white; }
        .nav-link:hover .nav-icon { background: rgba(0,212,170,0.15); color: var(--teal); }
        .nav-link.active { background: linear-gradient(90deg, rgba(0,212,170,0.15), rgba(0,212,170,0.05)); color: white; border: 1px solid rgba(0,212,170,0.2); }
        .nav-link.active .nav-icon { background: var(--teal); color: var(--navy); }
        .sidebar-bottom { padding: 16px; border-top: 1px solid var(--border); position: relative; z-index: 1; }
        .logout-btn { display: flex; align-items: center; gap: 10px; padding: 11px 14px; background: rgba(255,107,138,0.08); border-radius: 10px; color: rgba(255,107,138,0.8); text-decoration: none; font-size: 0.88rem; font-weight: 500; transition: all 0.25s; border: 1px solid rgba(255,107,138,0.12); }
        .logout-btn:hover { background: rgba(255,107,138,0.15); color: var(--rose); transform: translateX(3px); }

        /* ===== MAIN CONTENT ===== */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; transition: margin-left 0.4s; }
        .top-header { background: rgba(248,250,253,0.92); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); padding: 0 36px; height: 70px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .page-breadcrumb h1 { font-size: 1.45rem; font-weight: 800; }
        .page-breadcrumb p { font-size: 0.78rem; color: var(--text-secondary); }
        .menu-toggle { display: none; width: 38px; height: 38px; background: var(--navy); border: none; border-radius: 10px; cursor: pointer; flex-direction: column; gap: 4px; align-items: center; justify-content: center; }
        .menu-toggle span { width: 16px; height: 2px; background: white; border-radius: 2px; transition: all 0.3s; }
        .menu-toggle.open span:nth-child(1) { transform: translateY(6px) rotate(45deg); }
        .menu-toggle.open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
        .menu-toggle.open span:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }
        .header-profile { display: flex; align-items: center; gap: 10px; padding: 6px 14px 6px 8px; background: white; border-radius: 50px; border: 1.5px solid rgba(0,0,0,0.07); cursor: pointer; transition: all 0.25s; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .header-profile:hover { border-color: rgba(0,212,170,0.4); box-shadow: 0 4px 16px rgba(0,212,170,0.12); }
        .header-avatar { width: 30px; height: 30px; background: linear-gradient(135deg, var(--teal), #00a085); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; }
        .header-profile-name { font-size: 0.84rem; font-weight: 600; color: var(--text-primary); }

        /* ===== PAGE BODY ===== */
        .page-body { padding: 32px 36px; display: flex; flex-direction: column; gap: 28px; }
        .card { background: white; border-radius: 20px; padding: 24px 28px; box-shadow: var(--card-shadow); border: 1px solid rgba(0,0,0,0.04); }
        .card h3 i { vertical-align: middle; margin-right: 6px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
        .form-group label { font-size: 0.78rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input, .form-group textarea { padding: 11px 16px; border: 1.5px solid rgba(0,0,0,0.08); border-radius: 12px; font-family: 'Outfit', sans-serif; font-size: 0.9rem; transition: 0.2s; resize: vertical; }
        .form-group input:focus, .form-group textarea:focus { border-color: var(--teal); outline: none; box-shadow: 0 0 0 3px rgba(0,212,170,0.1); }
        .btn { background: linear-gradient(135deg, var(--teal), #00a085); color: white; padding: 12px 28px; border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; font-size: 0.9rem; box-shadow: 0 6px 18px rgba(0,212,170,0.3); }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,212,170,0.4); }

        /* ===== APPOINTMENTS TABLE ===== */
        .appt-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 10px; }
        .appt-table th { padding: 14px 16px; background: var(--off-white); font-weight: 700; text-align: left; border-bottom: 1.5px solid rgba(0,0,0,0.06); }
        .appt-table td { padding: 14px 16px; border-bottom: 1px solid rgba(0,0,0,0.04); vertical-align: middle; }
        .appt-table tr:last-child td { border-bottom: none; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; display: inline-block; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fecdd5; color: #9b1c2c; }
        .alert { padding: 12px 18px; border-radius: 12px; margin-bottom: 16px; font-size: 0.9rem; }
        .alert.success { background: #d1fae5; color: #065f46; }
        .alert.error { background: #fecdd5; color: #9b1c2c; }

        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(11,20,38,0.5); backdrop-filter: blur(4px); z-index: 999; }
        .footer { padding: 20px 36px; text-align: center; font-size: 0.77rem; color: var(--text-muted); border-top: 1px solid rgba(0,0,0,0.05); }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); z-index: 1001; }
            .sidebar.show { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .menu-toggle { display: flex; }
            .top-header { padding: 0 20px; }
            .page-body { padding: 20px; }
            .header-profile-name { display: none; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-scroll">
        <div class="sidebar-logo">
            <div class="logo-mark">
                <div class="logo-icon"><img src="../images/aroroy_logo.png" alt="Aroroy Logo"></div>
                <div class="logo-text"><h3>Aroroy PIS</h3></div>
            </div>
        </div>
        <div class="sidebar-user">
            <div class="user-card">
                <div class="user-avatar"><i class="fas fa-user"></i></div>
                <div class="user-card-info">
                    <h4><?php echo htmlspecialchars($patient_name); ?></h4>
                    <p>Patient Account</p>
                </div>
                <div class="user-status"></div>
            </div>
        </div>
        <div class="nav-section">
            <div class="nav-label">Menu</div>
            <ul style="list-style:none">
                <li class="nav-item">
                    <a href="patient_dashboard.php" class="nav-link active">
                        <span class="nav-icon"><i class="fas fa-calendar-alt"></i></span>
                        <span>My Appointments</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="sidebar-bottom">
        <a href="patient_logout.php" class="logout-btn">
            <i class="fas fa-arrow-right-from-bracket"></i>
            <span>Sign Out</span>
        </a>
    </div>
</aside>

<!-- Main Content -->
<div class="main-content">
    <div class="top-header">
        <div class="page-breadcrumb">
            <h1>Patient Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($patient_name); ?></p>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <div class="header-profile">
                <div class="header-avatar"><i class="fas fa-user"></i></div>
                <span class="header-profile-name"><?php echo htmlspecialchars($patient_name); ?></span>
            </div>
            <button class="menu-toggle" id="menuToggle"><span></span><span></span><span></span></button>
        </div>
    </div>

    <div class="page-body">
        <!-- Book Appointment -->
        <div class="card">
            <h3><i class="fas fa-calendar-plus" style="color:var(--teal)"></i> Book Appointment</h3>
            <?php echo $msg; ?>
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label>Preferred Date</label>
                        <input type="date" name="appointment_date" required>
                    </div>
                    <div class="form-group">
                        <label>Preferred Time</label>
                        <input type="time" name="appointment_time" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Reason for Visit</label>
                    <textarea name="reason" rows="2" placeholder="Brief description..." required></textarea>
                </div>
                <button type="submit" name="book_appointment" class="btn"><i class="fas fa-paper-plane"></i> Submit Request</button>
            </form>
        </div>

        <!-- My Appointments as Table -->
        <div class="card">
            <h3><i class="fas fa-list-check" style="color:var(--teal)"></i> My Appointments</h3>
            <?php if (empty($appointments)): ?>
                <p style="text-align:center; color:var(--text-secondary); padding:20px">No appointments yet.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="appt-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Admin Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $a): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($a['appointment_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($a['appointment_time'])); ?></td>
                                <td><?php echo htmlspecialchars($a['reason']); ?></td>
                                <td><span class="status-badge status-<?php echo $a['status']; ?>"><?php echo ucfirst($a['status']); ?></span></td>
                                <td><?php echo !empty($a['admin_note']) ? htmlspecialchars($a['admin_note']) : '—'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> Aroroy Patient Management System &nbsp;&middot;&nbsp; All rights reserved
    </div>
</div>

<script>
    const menuToggle = document.getElementById('menuToggle');
    const sidebar    = document.getElementById('sidebar');
    const overlay    = document.getElementById('sidebarOverlay');
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        menuToggle.classList.toggle('open');
    });
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        menuToggle.classList.remove('open');
    });
</script>
</body>
</html>