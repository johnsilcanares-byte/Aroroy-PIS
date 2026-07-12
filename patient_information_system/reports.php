<?php 
include './config/connection.php';
include './common_service/common_functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!(isset($_SESSION['user_id']))) {
    header("location:index.php");
    exit;
}

$date = date('Y-m-d');
$year = date('Y'); 
$month = date('m');

$todaysCount = 0;
$currentWeekCount = 0;
$currentMonthCount = 0;
$currentYearCount = 0;

try {
    $stmtToday = $con->prepare("SELECT count(*) as `today` FROM `patient_visits` WHERE `visit_date` = :today");
    $stmtToday->execute([':today' => $date]);
    $todaysCount = $stmtToday->fetch(PDO::FETCH_ASSOC)['today'];

    $stmtWeek = $con->prepare("SELECT count(*) as `week` FROM `patient_visits` WHERE YEARWEEK(`visit_date`, 1) = YEARWEEK(:today, 1)");
    $stmtWeek->execute([':today' => $date]);
    $currentWeekCount = $stmtWeek->fetch(PDO::FETCH_ASSOC)['week'];

    $stmtMonth = $con->prepare("SELECT count(*) as `month` FROM `patient_visits` WHERE YEAR(`visit_date`) = :year AND MONTH(`visit_date`) = :month");
    $stmtMonth->execute([':year' => $year, ':month' => $month]);
    $currentMonthCount = $stmtMonth->fetch(PDO::FETCH_ASSOC)['month'];

    $stmtYear = $con->prepare("SELECT count(*) as `year` FROM `patient_visits` WHERE YEAR(`visit_date`) = :year");
    $stmtYear->execute([':year' => $year]);
    $currentYearCount = $stmtYear->fetch(PDO::FETCH_ASSOC)['year'];

} catch(PDOException $ex) {
     error_log($ex->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aroroy PIS — Reports</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css">
    <style>
        :root {
            --navy: #0b1426;
            --navy-light: #152039;
            --navy-mid: #1e2d4a;
            --teal: #00d4aa;
            --teal-dark: #00b891;
            --teal-glow: rgba(0, 212, 170, 0.15);
            --amber: #ffb340;
            --rose: #ff6b8a;
            --violet: #a78bfa;
            --sky: #38bdf8;
            --white: #ffffff;
            --off-white: #f8fafd;
            --text-primary: #1a2642;
            --text-secondary: #5e7494;
            --text-muted: #9bb0c9;
            --border: rgba(255,255,255,0.07);
            --card-shadow: 0 4px 24px rgba(11, 20, 38, 0.08);
            --card-hover-shadow: 0 16px 48px rgba(11, 20, 38, 0.16);
            --sidebar-width: 272px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--off-white);
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* ============================================
           SIDEBAR
        ============================================ */
        .sidebar {
            position: fixed;
            left: 0; top: 0;
            width: var(--sidebar-width);
            height: 100%;
            background: var(--navy);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(24, 222, 182, 0.45) 0%, transparent 70%);
            top: -80px; left: -80px;
            border-radius: 50%;
            pointer-events: none;
        }

        .sidebar::after {
            content: '';
            position: absolute;
            width: 250px; height: 250px;
            background: radial-gradient(circle, rgba(159, 147, 192, 0.18) 0%, transparent 70%);
            bottom: 80px; right: -60px;
            border-radius: 50%;
            pointer-events: none;
        }

        .sidebar-scroll {
            overflow-y: auto;
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .sidebar-scroll::-webkit-scrollbar { width: 3px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(0,212,170,0.3); border-radius: 10px; }

        .sidebar-logo {
            padding: 28px 24px 20px;
            border-bottom: 1px solid var(--border);
        }

        .logo-mark {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--teal), #00a085);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; color: white;
            box-shadow: 0 8px 20px rgba(0,212,170,0.3);
            flex-shrink: 0;
        }

        .logo-text h3 {
            font-size: 1.15rem;
            font-weight: 800;
            color: white;
            letter-spacing: -0.3px;
        }

        .logo-text span {
            font-size: 0.7rem;
            color: var(--teal);
            font-weight: 500;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .sidebar-user {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            background: var(--navy-light);
            border-radius: 14px;
            border: 1px solid var(--border);
        }

        .user-avatar {
            width: 42px; height: 42px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--teal);
            flex-shrink: 0;
        }

        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .user-card-info h4 {
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            line-height: 1.2;
        }

        .user-card-info p {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .user-status {
            width: 8px; height: 8px;
            background: var(--teal);
            border-radius: 50%;
            margin-left: auto;
            box-shadow: 0 0 8px var(--teal);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.85); }
        }

        .nav-section {
            padding: 16px 16px 0;
        }

        .nav-label {
            font-size: 0.65rem;
            font-weight: 600;
            color: rgba(255,255,255,0.25);
            letter-spacing: 1.8px;
            text-transform: uppercase;
            padding: 0 8px 8px;
            margin-top: 8px;
        }

        .nav-item { list-style: none; margin: 2px 0; }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            border-radius: 10px;
            gap: 10px;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.25s ease;
            position: relative;
        }

        .nav-link .nav-icon {
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px;
            font-size: 0.95rem;
            background: rgba(255,255,255,0.05);
            flex-shrink: 0;
            transition: all 0.25s ease;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.06);
            color: white;
        }

        .nav-link:hover .nav-icon {
            background: rgba(0,212,170,0.15);
            color: var(--teal);
        }

        .nav-link.active {
            background: linear-gradient(90deg, rgba(0,212,170,0.15), rgba(0,212,170,0.05));
            color: white;
            border: 1px solid rgba(0,212,170,0.2);
        }

        .nav-link.active .nav-icon {
            background: var(--teal);
            color: var(--navy);
        }

        .nav-item.has-sub > .nav-link::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 0.6rem;
            margin-left: auto;
            transition: transform 0.3s;
            opacity: 0.5;
        }

        .nav-item.has-sub.open > .nav-link::after { transform: rotate(180deg); }

        .sub-menu {
            overflow: hidden;
            max-height: 0;
            transition: max-height 0.35s ease;
        }

        .nav-item.has-sub.open .sub-menu { max-height: 200px; }

        .sub-menu li { list-style: none; }

        .sub-menu .nav-link {
            padding: 8px 12px 8px 54px;
            font-size: 0.82rem;
            color: rgba(255,255,255,0.45);
        }

        .sub-menu .nav-link:hover { color: var(--teal); background: rgba(0,212,170,0.06); }

        .sidebar-bottom {
            padding: 16px;
            border-top: 1px solid var(--border);
            position: relative; z-index: 1;
        }

        .logout-btn {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 14px;
            background: rgba(255, 107, 138, 0.08);
            border-radius: 10px;
            color: rgba(255,107,138,0.8);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.25s;
            border: 1px solid rgba(255,107,138,0.12);
        }

        .logout-btn:hover {
            background: rgba(255,107,138,0.15);
            color: var(--rose);
            transform: translateX(3px);
        }

        /* ============================================
           MAIN CONTENT
        ============================================ */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .top-header {
            background: rgba(248, 250, 253, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 0 36px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0; z-index: 900;
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }

        .page-breadcrumb {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .page-breadcrumb h1 {
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.4px;
        }

        .page-breadcrumb p {
            font-size: 0.78rem;
            color: var(--text-secondary);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .menu-toggle {
            display: none;
            width: 38px; height: 38px;
            background: var(--navy);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 4px;
            transition: all 0.3s;
        }

        .menu-toggle span {
            width: 16px; height: 2px;
            background: white;
            border-radius: 2px;
            transition: all 0.3s;
        }

        .menu-toggle.open span:nth-child(1) { transform: translateY(6px) rotate(45deg); }
        .menu-toggle.open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
        .menu-toggle.open span:nth-child(3) { transform: translateY(-6px) rotate(-45deg); }

        .header-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 14px 6px 8px;
            background: white;
            border-radius: 50px;
            border: 1.5px solid rgba(0,0,0,0.07);
            cursor: pointer;
            transition: all 0.25s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .header-profile:hover {
            border-color: rgba(0,212,170,0.4);
            box-shadow: 0 4px 16px rgba(0,212,170,0.12);
        }

        .header-avatar {
            width: 30px; height: 30px;
            background: linear-gradient(135deg, var(--teal), #00a085);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white;
            font-size: 0.75rem;
        }

        .header-profile-name {
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .header-profile i { font-size: 0.7rem; color: var(--text-muted); }

        /* Reports specific styles */
        .page-body {
            padding: 32px 36px;
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(0,0,0,0.04);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-hover-shadow);
        }

        .stat-card-inner {
            display: flex;
            flex-direction: column;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-badge {
            font-size: 0.65rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0.3px;
        }

        .stat-icon-box {
            width: 42px; height: 42px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .stat-card:hover .stat-icon-box { transform: scale(1.05) rotate(-3deg); }

        .stat-number {
            font-size: 2rem;
            font-weight: 900;
            letter-spacing: -2px;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Color variants */
        .stat-card.teal .stat-icon-box { background: rgba(0,212,170,0.1); color: #00b891; }
        .stat-card.teal .stat-badge { background: rgba(0,212,170,0.1); color: #00996e; }
        .stat-card.teal .stat-number { color: #008f68; }

        .stat-card.sky .stat-icon-box { background: rgba(56,189,248,0.1); color: #0ea5e9; }
        .stat-card.sky .stat-badge { background: rgba(56,189,248,0.1); color: #0284c7; }
        .stat-card.sky .stat-number { color: #0284c7; }

        .stat-card.amber .stat-icon-box { background: rgba(255,179,64,0.1); color: #e69500; }
        .stat-card.amber .stat-badge { background: rgba(255,179,64,0.1); color: #c47e00; }
        .stat-card.amber .stat-number { color: #c47e00; }

        .stat-card.violet .stat-icon-box { background: rgba(167,139,250,0.1); color: #8b5cf6; }
        .stat-card.violet .stat-badge { background: rgba(167,139,250,0.1); color: #7c3aed; }
        .stat-card.violet .stat-number { color: #7c3aed; }

        .report-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.04);
            box-shadow: var(--card-shadow);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .report-card:hover {
            box-shadow: var(--card-hover-shadow);
        }

        .report-card-header {
            padding: 20px 28px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .report-card-header-icon {
            width: 40px; height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--teal), #00a085);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .report-card-header h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.2px;
        }

        .report-card-body {
            padding: 28px;
        }

        .form-row-custom {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            align-items: flex-end;
        }

        .form-group-custom {
            margin-bottom: 0;
        }

        .form-label-custom {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control-custom {
            width: 100%;
            padding: 11px 14px;
            font-size: 0.85rem;
            font-family: 'Outfit', sans-serif;
            border: 1.5px solid rgba(0,0,0,0.08);
            border-radius: 12px;
            transition: all 0.25s ease;
            background: white;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: var(--teal);
            box-shadow: 0 0 0 3px rgba(0,212,170,0.1);
        }

        .input-group {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
        }

        .input-group > .form-control-custom {
            flex: 1 1 auto;
            width: 1%;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group-append {
            display: flex;
        }

        .input-group-text {
            display: flex;
            align-items: center;
            padding: 11px 14px;
            font-size: 0.85rem;
            font-weight: 400;
            line-height: 1.5;
            text-align: center;
            white-space: nowrap;
            background-color: white;
            border: 1.5px solid rgba(0,0,0,0.08);
            border-left: none;
            border-radius: 0 12px 12px 0;
            color: var(--text-secondary);
        }

        .btn-generate {
            padding: 11px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            font-family: 'Outfit', sans-serif;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--navy), var(--navy-mid));
            color: white;
            width: 100%;
            justify-content: center;
        }

        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(11,20,38,0.25);
        }

        .footer {
            padding: 20px 36px;
            text-align: center;
            font-size: 0.77rem;
            color: var(--text-muted);
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(11,20,38,0.5);
            backdrop-filter: blur(4px);
            z-index: 999;
        }

        /* Modal Styles */
        .modal-backdrop-custom {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(11, 20, 38, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .modal-backdrop-custom.show {
            display: flex;
        }

        .modal-container {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 420px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(11, 20, 38, 0.3);
            animation: modalSlideIn 0.4s cubic-bezier(0.34, 1.2, 0.64, 1);
        }

        .modal-header-custom {
            padding: 24px 28px 0 28px;
            position: relative;
        }

        .modal-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 16px;
        }

        .modal-icon.warning {
            background: rgba(255, 107, 138, 0.12);
            color: #ff6b8a;
        }

        .modal-icon.success {
            background: rgba(0, 212, 170, 0.12);
            color: #00d4aa;
        }

        .modal-icon.error {
            background: rgba(229, 72, 77, 0.12);
            color: #e5484d;
        }

        .modal-icon.info {
            background: rgba(56, 189, 248, 0.12);
            color: #38bdf8;
        }

        .modal-header-custom h3 {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.3px;
            margin-bottom: 8px;
        }

        .modal-header-custom p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .modal-body-custom {
            padding: 20px 28px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .modal-message {
            font-size: 0.9rem;
            color: var(--text-primary);
            line-height: 1.5;
        }

        .modal-footer-custom {
            padding: 20px 28px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .modal-btn {
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            font-family: 'Outfit', sans-serif;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .modal-btn-primary {
            background: linear-gradient(135deg, var(--navy), var(--navy-mid));
            color: white;
        }

        .modal-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(11, 20, 38, 0.25);
        }

        .modal-btn-secondary {
            background: var(--off-white);
            color: var(--text-secondary);
            border: 1.5px solid rgba(0, 0, 0, 0.08);
        }

        .modal-btn-secondary:hover {
            border-color: var(--teal);
            color: var(--teal);
            transform: translateY(-2px);
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 32px;
            height: 32px;
            background: var(--off-white);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            transition: all 0.25s ease;
        }

        .modal-close-btn:hover {
            background: rgba(229, 72, 77, 0.1);
            color: #e5484d;
            transform: rotate(90deg);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1001;
            }
            .sidebar.show { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .menu-toggle { display: flex !important; }
            .top-header { padding: 0 20px; }
            .page-body { padding: 20px; gap: 24px; }
            .header-profile-name { display: none; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
            .form-row-custom { grid-template-columns: 1fr; gap: 15px; }
            .report-card-header { padding: 16px 20px; }
            .report-card-body { padding: 20px; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .stat-number { font-size: 1.6rem; }
            .page-breadcrumb h1 { font-size: 1.15rem; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-scroll">
        <div class="sidebar-logo">
            <div class="logo-mark">
                <div class="logo-icon"><img src="images/aroroy_logo.png" alt="Aroroy Logo" style="width:50px; height:50px; object-fit:cover;"></div>
                <div class="logo-text">
                    <h3>Aroroy PIS</h3>
                </div>
            </div>
        </div>

        <div class="sidebar-user">
            <div class="user-card">
                <div class="user-avatar">
                    <img src="user_images/<?php echo $_SESSION['profile_picture'] ?? 'default.png';?>" alt="User">
                </div>
                <div class="user-card-info">
                    <h4><?php echo $_SESSION['display_name'] ?? 'Admin';?></h4>
                    <p>System Administrator</p>
                </div>
                <div class="user-status"></div>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-label">Main</div>
            <ul style="list-style:none">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <span class="nav-icon"><i class="fas fa-squares-four"></i></span>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="nav-item has-sub">
                    <a href="#" class="nav-link">
                        <span class="nav-icon"><i class="fas fa-user-injured"></i></span>
                        <span>Patients</span>
                    </a>
                    <ul class="sub-menu">
                        <li><a href="new_prescription.php" class="nav-link">New Prescription</a></li>
                        <li><a href="patients.php" class="nav-link">Add Patients</a></li>
                        <li><a href="patient_history.php" class="nav-link">Patient History</a></li>
                         <li><a href="patients_records.php" class="nav-link active">View All Patients</a></li>
                    </ul>
                </li>

                <li class="nav-item has-sub">
                    <a href="#" class="nav-link">
                        <span class="nav-icon"><i class="fas fa-pills"></i></span>
                        <span>Medicines</span>
                    </a>
                    <ul class="sub-menu">
                        <li><a href="medicines.php" class="nav-link">Add Medicine</a></li>
                        <li><a href="medicine_details.php" class="nav-link">Medicine Details</a></li>
                    </ul>
                </li>

                <li class="nav-item has-sub">
                    <a href="#" class="nav-link">
                        <span class="nav-icon"><i class="fa-solid fa-book-medical"></i></span>
                        <span>Appointments</span>
                    </a>
                    <ul class="sub-menu">
                        <li><a href="appointments.php" class="nav-link">Appointments</a></li>
                    </ul>
                </li>

                <li class="nav-item has-sub">
                    <a href="#" class="nav-link active">
                        <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
                        <span>Reports</span>
                    </a>
                    <ul class="sub-menu">
                        <li><a href="reports.php" class="nav-link active">Reports</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a href="users.php" class="nav-link">
                        <span class="nav-icon"><i class="fas fa-users"></i></span>
                        <span>Users</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-arrow-right-from-bracket"></i>
            <span>Sign Out</span>
        </a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="top-header">
        <div class="page-breadcrumb">
            <h1>System Reports</h1>
            <p>Generate and export patient visit analytics</p>
        </div>
        <div class="header-right">
            <div class="header-profile" id="headerProfile">
                <div class="header-avatar"><i class="fas fa-user"></i></div>
                <span class="header-profile-name"><?php echo $_SESSION['display_name'] ?? 'Admin'; ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>

    <div class="page-body">
       
        </div>

        <!-- Patient Visits Report Card -->
        <div class="report-card">
            <div class="report-card-header">
                <div class="report-card-header-icon"><i class="fas fa-users"></i></div>
                <h3>Patient Visits History Report</h3>
            </div>
            <div class="report-card-body">
                <div class="form-row-custom">
                    <?php 
                        echo getDateTextBox('From Date', 'patients_from');
                        echo getDateTextBox('To Date', 'patients_to');
                    ?>
                    <div>
                        <button type="button" id="print_visits" class="btn-generate">
                            <i class="fas fa-file-pdf"></i> Generate Visit Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disease Report Card -->
        <div class="report-card">
            <div class="report-card-header">
                <div class="report-card-header-icon"><i class="fas fa-procedures"></i></div>
                <h3>Disease-Specific Report</h3>
            </div>
            <div class="report-card-body">
                <div class="form-row-custom">
                    <div class="form-group-custom">
                        <label class="form-label-custom">Enter Disease</label>
                        <input id="disease" class="form-control-custom" placeholder="e.g., Hypertension, Diabetes">
                    </div>
                    <?php 
                        echo getDateTextBox('From Date', 'disease_from');
                        echo getDateTextBox('To Date', 'disease_to');
                    ?>
                    <div>
                        <button type="button" id="print_diseases" class="btn-generate">
                            <i class="fas fa-file-pdf"></i> Generate Disease Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> Aroroy Patient Management System &nbsp;&middot;&nbsp; All rights reserved
    </div>
</div>

<!-- ===== CUSTOM MODAL ===== -->
<div id="customModal" class="modal-backdrop-custom">
    <div class="modal-container">
        <div class="modal-header-custom">
            <button class="modal-close-btn" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-icon" id="modalIcon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 id="modalTitle">Warning</h3>
            <p id="modalSubtitle">Please review the following</p>
        </div>
        <div class="modal-body-custom">
            <div class="modal-message" id="modalMessage">
                This is a modal message.
            </div>
        </div>
        <div class="modal-footer-custom">
            <button class="modal-btn modal-btn-secondary" onclick="closeModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="modal-btn modal-btn-primary" id="modalConfirmBtn" onclick="confirmModalAction()">
                <i class="fas fa-check"></i> OK
            </button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js"></script>

<script>
    // Sidebar toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar    = document.getElementById('sidebar');
    const overlay    = document.getElementById('sidebarOverlay');

    function openSidebar() { sidebar.classList.add('show'); overlay.classList.add('show'); menuToggle.classList.add('open'); }
    function closeSidebar() { sidebar.classList.remove('show'); overlay.classList.remove('show'); menuToggle.classList.remove('open'); }
    
    if(menuToggle) {
        menuToggle.addEventListener('click', () => sidebar.classList.contains('show') ? closeSidebar() : openSidebar());
    }
    
    if(overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Submenu toggle
    document.querySelectorAll('.has-sub > .nav-link').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const parent = link.parentElement;
            document.querySelectorAll('.has-sub.open').forEach(el => { if (el !== parent) el.classList.remove('open'); });
            parent.classList.toggle('open');
        });
    });

    // ============================================
    // CUSTOM MODAL FUNCTIONS
    // ============================================
    let modalCallback = null;

    function showModal(options) {
        const modal = document.getElementById('customModal');
        const icon = document.getElementById('modalIcon');
        const title = document.getElementById('modalTitle');
        const subtitle = document.getElementById('modalSubtitle');
        const message = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('modalConfirmBtn');
        
        icon.className = 'modal-icon';
        if (options.type === 'warning') {
            icon.classList.add('warning');
            icon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
            confirmBtn.className = 'modal-btn modal-btn-primary';
            confirmBtn.innerHTML = '<i class="fas fa-check"></i> Got it';
        } else if (options.type === 'error') {
            icon.classList.add('error');
            icon.innerHTML = '<i class="fas fa-times-circle"></i>';
            confirmBtn.className = 'modal-btn modal-btn-danger';
            confirmBtn.innerHTML = '<i class="fas fa-check"></i> OK';
        } else if (options.type === 'success') {
            icon.classList.add('success');
            icon.innerHTML = '<i class="fas fa-check-circle"></i>';
            confirmBtn.className = 'modal-btn modal-btn-primary';
            confirmBtn.innerHTML = '<i class="fas fa-check"></i> Great';
        } else {
            icon.classList.add('info');
            icon.innerHTML = '<i class="fas fa-info-circle"></i>';
            confirmBtn.className = 'modal-btn modal-btn-primary';
            confirmBtn.innerHTML = '<i class="fas fa-check"></i> OK';
        }
        
        title.textContent = options.title || 'Notification';
        subtitle.textContent = options.subtitle || '';
        message.innerHTML = options.message || '';
        modalCallback = options.onConfirm || null;
        
        modal.classList.add('show');
        
        if (options.type === 'success' || options.type === 'info') {
            setTimeout(() => {
                if (modal.classList.contains('show')) {
                    closeModal();
                }
            }, 4000);
        }
    }

    function closeModal() {
        const modal = document.getElementById('customModal');
        modal.classList.remove('show');
        modalCallback = null;
    }

    function confirmModalAction() {
        if (modalCallback && typeof modalCallback === 'function') {
            modalCallback();
        }
        closeModal();
    }

    function showCustomAlert(message, title = 'Notice', type = 'warning') {
        showModal({
            type: type,
            title: title,
            subtitle: 'Please take a moment to review',
            message: message,
            onConfirm: () => {}
        });
    }

    // Initialize datetimepicker
    $(function () {
        $('#patients_from, #patients_to, #disease_from, #disease_to').datetimepicker({
            format: 'L'
        });

        // Default dates
        $('#patients_from').datetimepicker('date', moment().startOf('month'));
        $('#patients_to').datetimepicker('date', moment());
        $('#disease_from').datetimepicker('date', moment().startOf('month'));
        $('#disease_to').datetimepicker('date', moment());

        // Validate date range
        function isValid(from, to) {
            return moment(from, 'L', true).isValid() &&
                   moment(to, 'L', true).isValid() &&
                   moment(from).isSameOrBefore(moment(to));
        }

        // PATIENT REPORT
        $("#print_visits").click(function () {
            let from = $("#patients_from input").val();
            let to = $("#patients_to input").val();

            if (!from || !to) {
                showCustomAlert("Please select both dates to generate the report.", "Missing Dates", "warning");
                return;
            }

            if (!isValid(from, to)) {
                showCustomAlert("Invalid date range. From date must be on or before To date.", "Invalid Range", "warning");
                return;
            }

            window.open(`print_patients_visits.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`, "_blank");
        });

        // DISEASE REPORT
        $("#print_diseases").click(function () {
            let from = $("#disease_from input").val();
            let to = $("#disease_to input").val();
            let disease = $("#disease").val().trim();

            if (!from || !to || !disease) {
                showCustomAlert("Please fill all fields (Disease, From Date, To Date).", "Missing Information", "warning");
                return;
            }

            if (!isValid(from, to)) {
                showCustomAlert("Invalid date range. From date must be on or before To date.", "Invalid Range", "warning");
                return;
            }

            window.open(`print_diseases.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&disease=${encodeURIComponent(disease)}`, "_blank");
        });
    });
</script>
</body>
</html>