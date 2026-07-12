<?php 
include './config/connection.php';

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
    <title>Aroroy PIS — Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Subtle animated mesh background */
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

        /* Logo */
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
            width: 50px; height: 50px;
            background: linear-gradient(135deg, var(--teal), #00a085);
            border-radius: 40%;
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

        /* User Profile in Sidebar */
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

        /* Nav */
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

        /* Submenu */
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

        /* Sidebar bottom */
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

        /* ============================================
           TOP HEADER
        ============================================ */
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

        /* Burger — ONLY visible on small screens */
        .menu-toggle {
            display: none; /* hidden by default, shown via media query */
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

        /* ============================================
           PAGE BODY
        ============================================ */
        .page-body {
            padding: 32px 36px;
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        /* ============================================
           STATS GRID
        ============================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 22px 24px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            border: 1.5px solid transparent;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--card-shadow);
            animation: card-in 0.6s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.12s; }
        .stat-card:nth-child(3) { animation-delay: 0.19s; }
        .stat-card:nth-child(4) { animation-delay: 0.26s; }

        @keyframes card-in {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--card-hover-shadow);
        }

        /* Glint animation */
        .stat-card::after {
            content: '';
            position: absolute;
            top: -50%; left: -60%;
            width: 50%; height: 200%;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.5), transparent);
            transform: skewX(-20deg);
            transition: left 0.6s ease;
        }
        .stat-card:hover::after { left: 140%; }

        .stat-card-inner {
            display: flex;
            flex-direction: column;
            gap: 14px;
            position: relative;
            z-index: 1;
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            letter-spacing: 0.3px;
        }

        .stat-icon-box {
            width: 46px; height: 46px;
            border-radius: 13px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
            transition: transform 0.3s ease;
        }

        .stat-card:hover .stat-icon-box { transform: scale(1.1) rotate(-5deg); }

        .stat-number {
            font-size: 2.6rem;
            font-weight: 900;
            letter-spacing: -2px;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-weight: 500;
            margin-top: 2px;
        }

        .stat-bar {
            height: 3px;
            border-radius: 3px;
            background: rgba(0,0,0,0.06);
            overflow: hidden;
        }

        .stat-bar-fill {
            height: 100%;
            border-radius: 3px;
            width: 0%;
            transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Color variants */
        .stat-card.teal:hover { border-color: rgba(0,212,170,0.25); }
        .stat-card.teal .stat-icon-box { background: rgba(0,212,170,0.1); color: #00b891; }
        .stat-card.teal .stat-badge { background: rgba(0,212,170,0.1); color: #00996e; }
        .stat-card.teal .stat-number { color: #008f68; }
        .stat-card.teal .stat-bar-fill { background: linear-gradient(90deg, #00d4aa, #00b891); }

        .stat-card.amber:hover { border-color: rgba(255,179,64,0.25); }
        .stat-card.amber .stat-icon-box { background: rgba(255,179,64,0.1); color: #e69500; }
        .stat-card.amber .stat-badge { background: rgba(255,179,64,0.1); color: #c47e00; }
        .stat-card.amber .stat-number { color: #c47e00; }
        .stat-card.amber .stat-bar-fill { background: linear-gradient(90deg, #ffb340, #f59e0b); }

        .stat-card.violet:hover { border-color: rgba(167,139,250,0.25); }
        .stat-card.violet .stat-icon-box { background: rgba(167,139,250,0.1); color: #8b5cf6; }
        .stat-card.violet .stat-badge { background: rgba(167,139,250,0.1); color: #7c3aed; }
        .stat-card.violet .stat-number { color: #7c3aed; }
        .stat-card.violet .stat-bar-fill { background: linear-gradient(90deg, #a78bfa, #8b5cf6); }

        .stat-card.sky:hover { border-color: rgba(56,189,248,0.25); }
        .stat-card.sky .stat-icon-box { background: rgba(56,189,248,0.1); color: #0ea5e9; }
        .stat-card.sky .stat-badge { background: rgba(56,189,248,0.1); color: #0284c7; }
        .stat-card.sky .stat-number { color: #0284c7; }
        .stat-card.sky .stat-bar-fill { background: linear-gradient(90deg, #38bdf8, #0ea5e9); }

        /* ============================================
           QUICK ACTIONS
        ============================================ */
        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .section-header-icon {
            width: 34px; height: 34px;
            border-radius: 9px;
            background: var(--navy);
            display: flex; align-items: center; justify-content: center;
            color: var(--teal);
            font-size: 0.9rem;
        }

        .section-header h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .action-card {
            background: white;
            border-radius: 18px;
            padding: 24px 20px;
            text-align: center;
            text-decoration: none;
            color: var(--text-primary);
            border: 1.5px solid rgba(0,0,0,0.06);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            animation: card-in 0.6s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        .action-card:nth-child(1) { animation-delay: 0.1s; }
        .action-card:nth-child(2) { animation-delay: 0.17s; }
        .action-card:nth-child(3) { animation-delay: 0.24s; }
        .action-card:nth-child(4) { animation-delay: 0.31s; }

        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: rgba(0,212,170,0.3);
        }

        .action-bg {
            position: absolute;
            bottom: -20px; right: -20px;
            width: 80px; height: 80px;
            border-radius: 50%;
            opacity: 0.04;
            transition: all 0.4s ease;
        }

        .action-card:hover .action-bg {
            opacity: 0.08;
            transform: scale(1.5);
        }

        .action-icon-wrap {
            width: 62px; height: 62px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
            font-size: 1.7rem;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .action-card:hover .action-icon-wrap {
            transform: scale(1.08);
            border-radius: 14px;
        }

        .action-card h4 {
            font-size: 0.93rem;
            font-weight: 700;
            margin-bottom: 5px;
            position: relative; z-index: 1;
        }

        .action-card p {
            font-size: 0.74rem;
            color: var(--text-secondary);
            position: relative; z-index: 1;
        }

        /* Action card variants */
        .action-card.a1 .action-icon-wrap { background: linear-gradient(135deg, #00d4aa, #00a085); color: white; box-shadow: 0 8px 20px rgba(0,212,170,0.3); }
        .action-card.a1 .action-bg { background: var(--teal); }
        .action-card.a2 .action-icon-wrap { background: linear-gradient(135deg, #38bdf8, #0284c7); color: white; box-shadow: 0 8px 20px rgba(56,189,248,0.3); }
        .action-card.a2 .action-bg { background: #38bdf8; }
        .action-card.a3 .action-icon-wrap { background: linear-gradient(135deg, #a78bfa, #7c3aed); color: white; box-shadow: 0 8px 20px rgba(167,139,250,0.3); }
        .action-card.a3 .action-bg { background: #a78bfa; }
        .action-card.a4 .action-icon-wrap { background: linear-gradient(135deg, #ffb340, #e69500); color: white; box-shadow: 0 8px 20px rgba(255,179,64,0.3); }
        .action-card.a4 .action-bg { background: #ffb340; }

        /* ============================================
           ACTIVITY
        ============================================ */
        .activity-wrap {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1.5px solid rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .activity-header {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-header h3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .activity-all-btn {
            font-size: 0.75rem;
            color: var(--teal);
            text-decoration: none;
            font-weight: 600;
            padding: 5px 12px;
            background: rgba(0,212,170,0.08);
            border-radius: 20px;
            transition: all 0.2s;
        }

        .activity-all-btn:hover { background: rgba(0,212,170,0.15); }

        .activity-list { padding: 8px 0; }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 24px;
            transition: all 0.25s ease;
            border-left: 3px solid transparent;
        }

        .activity-item:hover {
            background: var(--off-white);
            border-left-color: var(--teal);
        }

        .activity-dot {
            width: 42px; height: 42px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .activity-dot.d-teal { background: rgba(0,212,170,0.1); color: var(--teal-dark); }
        .activity-dot.d-violet { background: rgba(167,139,250,0.1); color: #8b5cf6; }
        .activity-dot.d-amber { background: rgba(255,179,64,0.1); color: #e69500; }

        .activity-text { flex: 1; min-width: 0; }

        .activity-text h4 {
            font-size: 0.87rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 3px;
        }

        .activity-text p {
            font-size: 0.75rem;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .activity-time {
            font-size: 0.7rem;
            color: var(--text-muted);
            font-weight: 500;
            white-space: nowrap;
        }

        /* ============================================
           FOOTER
        ============================================ */
        .footer {
            padding: 20px 36px;
            text-align: center;
            font-size: 0.77rem;
            color: var(--text-muted);
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        /* ============================================
           OVERLAY
        ============================================ */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(11,20,38,0.5);
            backdrop-filter: blur(4px);
            z-index: 999;
        }

        /* ============================================
           MODAL — completely redesigned
        ============================================ */
        .modal-backdrop {
            display: none;
            position: fixed; inset: 0;
            background: rgba(11,20,38,0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 1200;
            align-items: center;
            justify-content: center;
        }

        .modal-backdrop.show { display: flex; }

        .modal-box {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 480px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(11,20,38,0.3);
            animation: modal-pop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both;
            position: relative;
        }

        @keyframes modal-pop {
            from { opacity: 0; transform: scale(0.85) translateY(30px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-top {
            background: var(--navy);
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .modal-top::before {
            content: '';
            position: absolute;
            width: 200px; height: 200px;
            background: radial-gradient(circle, rgba(0,212,170,0.15), transparent);
            top: -60px; right: -40px;
            border-radius: 50%;
        }

        .modal-top::after {
            content: '';
            position: absolute;
            width: 120px; height: 120px;
            background: radial-gradient(circle, rgba(167,139,250,0.1), transparent);
            bottom: -30px; left: 40px;
            border-radius: 50%;
        }

        .modal-top-inner { position: relative; z-index: 1; }

        .modal-close {
            position: absolute;
            top: 18px; right: 18px;
            width: 32px; height: 32px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 9px;
            color: white;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.25s;
            z-index: 2;
        }

        .modal-close:hover {
            background: rgba(255,255,255,0.2);
            transform: rotate(90deg) scale(1.1);
        }

        .modal-icon-big {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, var(--teal), #00a085);
            border-radius: 40%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; color: white;
            box-shadow: 0 10px 25px rgba(0,212,170,0.35);
            margin-bottom: 16px;
        }

        .modal-top h3 {
            font-size: 1.3rem;
            font-weight: 800;
            color: white;
            letter-spacing: -0.3px;
            margin-bottom: 4px;
        }

        .modal-top p {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.55);
        }

        .modal-mid {
            padding: 26px 30px;
        }

        .modal-stat-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .modal-stat-item {
            background: var(--off-white);
            border-radius: 14px;
            padding: 14px;
            border: 1.5px solid rgba(0,0,0,0.05);
        }

        .modal-stat-item .ms-label {
            font-size: 0.7rem;
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 4px;
        }

        .modal-stat-item .ms-value {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }

        .modal-stat-item .ms-icon {
            font-size: 0.75rem;
            margin-right: 4px;
        }

        .modal-info-list { display: flex; flex-direction: column; gap: 10px; }

        .modal-info-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 14px;
            background: var(--off-white);
            border-radius: 12px;
        }

        .modal-info-item .mii-icon {
            width: 30px; height: 30px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .modal-info-item .mii-text strong {
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--text-primary);
            display: block;
        }

        .modal-info-item .mii-text span {
            font-size: 0.74rem;
            color: var(--text-secondary);
        }

        .modal-foot {
            padding: 20px 30px;
            border-top: 1px solid rgba(0,0,0,0.06);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-outline {
            padding: 10px 22px;
            border: 1.5px solid rgba(0,0,0,0.12);
            border-radius: 11px;
            background: white;
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Outfit', sans-serif;
            transition: all 0.25s;
        }

        .btn-outline:hover { border-color: var(--teal); color: var(--teal); }

        .btn-solid {
            padding: 10px 26px;
            border: none;
            border-radius: 11px;
            background: linear-gradient(135deg, var(--navy), var(--navy-mid));
            color: white;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Outfit', sans-serif;
            display: flex; align-items: center; gap: 8px;
            transition: all 0.25s;
        }

        .btn-solid:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(11,20,38,0.25);
        }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .actions-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 900px) {
            .actions-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1001;
            }
            .sidebar.show { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }

            /* Burger only on mobile */
            .menu-toggle {
                display: flex !important;
            }

            .top-header { padding: 0 20px; }
            .page-body { padding: 20px; gap: 24px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
            .actions-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }

            .header-profile-name { display: none; }
            .header-profile i.fa-chevron-down { display: none; }

            .modal-stat-row { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .actions-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .stat-number { font-size: 2rem; }
            .page-breadcrumb h1 { font-size: 1.15rem; }
        }

    </style>
</head>
<body>

<!-- Overlay for mobile sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-scroll">
        <div class="sidebar-logo">
            <div class="logo-mark">
                <div class="logo-icon"> <img src="images/aroroy_logo.png" alt="Aroroy Logo" style="width: 50px;"></div>
                <div class="logo-text">
                    <h3>Aroroy PIS</h3>
                
                </div>
            </div>
        </div>

        <div class="sidebar-user">
            <div class="user-card">
                <div class="user-avatar">
                    <img src="user_images/<?php echo $_SESSION['profile_picture'];?>" alt="User">
                </div>
                <div class="user-card-info">
                    <h4><?php echo $_SESSION['display_name'];?></h4>
                    <p>System Administrator</p>
                </div>
                <div class="user-status"></div>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-label">Main</div>
            <ul style="list-style:none">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
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
                         <li><a href="patients_records.php" class="nav-link">View All Patients</a></li>
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
                    <a href="#" class="nav-link">
                        <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
                        <span>Reports</span>
                    </a>
                    <ul class="sub-menu">
                        <li><a href="reports.php" class="nav-link">Reports</a></li>
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

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">

    <!-- Top Header -->
    <div class="top-header">
        <div class="page-breadcrumb">
            <h1>Dashboard</h1>
            <p>Welcome back, <?php echo $_SESSION['display_name']; ?>! Here's your overview</p>
        </div>
        <div class="header-right">
            <div class="header-profile" id="headerProfile">
                <div class="header-avatar"><i class="fas fa-user" style="font-size:0.8rem"></i></div>
                <span class="header-profile-name"><?php echo $_SESSION['display_name']; ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <!-- BURGER — only visible on small screens via CSS -->
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>

    <!-- Page Body -->
    <div class="page-body">

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card teal" onclick="openModal()" style=" border-left: 8px solid #00a085;">
                <div class="stat-card-inner">
                    <div class="stat-top" >
                        <span class="stat-badge">Today</span>
                        <div class="stat-icon-box"><i class="fas fa-calendar-day"></i></div>
                    </div>
                    <div>
                        <div class="stat-number" data-target="<?php echo $todaysCount; ?>">0</div>
                        <div class="stat-label">Patient Visits Today</div>
                    </div>
                    <div class="stat-bar"><div class="stat-bar-fill" data-width="72"></div></div>
                </div>
            </div>

            <div class="stat-card sky" onclick="openModal()"style=" border-left: 8px solid #518eff;">
                <div class="stat-card-inner">
                    <div class="stat-top">
                        <span class="stat-badge">This Week</span>
                        <div class="stat-icon-box"><i class="fas fa-calendar-week"></i></div>
                    </div>
                    <div>
                        <div class="stat-number" data-target="<?php echo $currentWeekCount; ?>">0</div>
                        <div class="stat-label">Weekly Visits</div>
                    </div>
                    <div class="stat-bar"><div class="stat-bar-fill" data-width="58"></div></div>
                </div>
            </div>

            <div class="stat-card amber" onclick="openModal()" style=" border-left: 8px solid #b07a1d;">
                <div class="stat-card-inner">
                    <div class="stat-top">
                        <span class="stat-badge">This Month</span>
                        <div class="stat-icon-box"><i class="fas fa-calendar-alt"></i></div>
                    </div>
                    <div>
                        <div class="stat-number" data-target="<?php echo $currentMonthCount; ?>">0</div>
                        <div class="stat-label">Monthly Visits</div>
                    </div>
                    <div class="stat-bar"><div class="stat-bar-fill" data-width="84"></div></div>
                </div>
            </div>

            <div class="stat-card violet" onclick="openModal()" style=" border-left: 8px solid #8935c1;">
                <div class="stat-card-inner">
                    <div class="stat-top">
                        <span class="stat-badge"><?php echo date('Y'); ?></span>
                        <div class="stat-icon-box"><i class="fas fa-chart-line"></i></div>
                    </div>
                    <div>
                        <div class="stat-number" data-target="<?php echo $currentYearCount; ?>">0</div>
                        <div class="stat-label">Yearly Visits</div>
                    </div>
                    <div class="stat-bar"><div class="stat-bar-fill" data-width="45"></div></div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div>
            <div class="section-header">
                <div class="section-header-icon"><i class="fas fa-bolt"></i></div>
                <h2>Quick Actions</h2>
            </div>
            <div class="actions-grid">
                <a href="patients_records.php" class="action-card a1">
                    <div class="action-bg"></div>
                    <div class="action-icon-wrap"><i class="fas fa-eye"></i></div>
                    <h4>View Patients</h4>
                    <p>View registered patients</p>
                </a>
                <a href="patients.php" class="action-card a2">
                    <div class="action-bg"></div>
                    <div class="action-icon-wrap"><i class="fas fa-user-plus"></i></div>
                    <h4>Add Patient</h4>
                    <p>Register new patient</p>
                </a>
                <a href="patients_history.php" class="action-card a1">
                    <div class="action-bg"></div>
                    <div class="action-icon-wrap"><i class="fas fa-history"></i></div>
                    <h4>Patient History</h4>
                    <p>View patient history</p>
                </a>
                <a href="patients_records.php" class="action-card a2">
                    <div class="action-bg"></div>
                    <div class="action-icon-wrap"><i class="fas fa-eye"></i></div>
                    <h4>View All Patients</h4>
                    <p>View registered patients</p>
                </a>
                <a href="new_prescription.php" class="action-card a3">
                    <div class="action-bg"></div>
                    <div class="action-icon-wrap"><i class="fas fa-prescription-bottle-medical"></i></div>
                    <h4>New Prescription</h4>
                    <p>Create prescription</p>
                </a>
                <a href="reports.php" class="action-card a4">
                    <div class="action-bg"></div>
                    <div class="action-icon-wrap"><i class="fas fa-chart-bar"></i></div>
                    <h4>Generate Report</h4>
                    <p>View analytics</p>
                </a>
                <a href="appointments.php" class="action-card a3">
                    <div class="action-bg"></div>
                    <div class="action-icon-wrap"><i class="fas fa-calendar-check"></i></div>
                    <h4>Appointments</h4>
                    <p>View and manage appointments</p>
                </a>
                <a href="users.php" class="action-card a4">
                    <div class="action-bg"></div>
                    <div class="action-icon-wrap"><i class="fas fa-users"></i></div>
                    <h4>Users</h4>
                    <p>View and manage user accounts</p>
                </a>
            </div>
        </div>

        
        </div>

    </div><!-- /page-body -->

    <div class="footer">
        &copy; <?php echo date('Y'); ?> Aroroy Patient Management System &nbsp;&middot;&nbsp; All rights reserved &nbsp;&middot;&nbsp;
    </div>
</div>

<!-- ===== MODAL ===== -->
<div class="modal-backdrop" id="infoModal">
    <div class="modal-box">
        <div class="modal-top">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <div class="modal-top-inner">

             <div class="modal-icon-big"> <img src="images/aroroy_logo.png" alt="Aroroy Logo" style="width: 50px;"></div>
               
                <h3>Dashboard Overview</h3>
                <p>Real-time practice insights for <?php echo date('F j, Y'); ?></p>
            </div>
        </div>
        <div class="modal-mid">
            <div class="modal-stat-row">
                <div class="modal-stat-item">
                    <div class="ms-label"><span class="ms-icon" style="color:#00b891">●</span> Today</div>
                    <div class="ms-value"><?php echo number_format($todaysCount); ?></div>
                </div>
                <div class="modal-stat-item">
                    <div class="ms-label"><span class="ms-icon" style="color:#0ea5e9">●</span> This Week</div>
                    <div class="ms-value"><?php echo number_format($currentWeekCount); ?></div>
                </div>
                <div class="modal-stat-item">
                    <div class="ms-label"><span class="ms-icon" style="color:#e69500">●</span> This Month</div>
                    <div class="ms-value"><?php echo number_format($currentMonthCount); ?></div>
                </div>
                <div class="modal-stat-item">
                    <div class="ms-label"><span class="ms-icon" style="color:#7c3aed">●</span> This Year</div>
                    <div class="ms-value"><?php echo number_format($currentYearCount); ?></div>
                </div>
            </div>
            <div class="modal-info-list">
                <div class="modal-info-item">
                    <div class="mii-icon" style="background:rgba(0,212,170,0.1);color:#00b891"><i class="fas fa-calendar-day"></i></div>
                    <div class="mii-text"><strong>Today's Visits</strong><span>Total patients recorded for today</span></div>
                </div>
                <div class="modal-info-item">
                    <div class="mii-icon" style="background:rgba(167,139,250,0.1);color:#7c3aed"><i class="fas fa-bolt"></i></div>
                    <div class="mii-text"><strong>Quick Actions</strong><span>Use shortcuts to navigate efficiently</span></div>
                </div>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn-outline" onclick="closeModal()">Dismiss</button>
            <button class="btn-solid" onclick="closeModal()"><i class="fas fa-rocket" style="font-size:0.8rem"></i> Get Started</button>
        </div>
    </div>
</div>

<script>
    // ── Sidebar Toggle (burger only on mobile) ──
    const menuToggle = document.getElementById('menuToggle');
    const sidebar    = document.getElementById('sidebar');
    const overlay    = document.getElementById('sidebarOverlay');

    function openSidebar() {
        sidebar.classList.add('show');
        overlay.classList.add('show');
        menuToggle.classList.add('open');
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        menuToggle.classList.remove('open');
    }

    menuToggle.addEventListener('click', () => {
        sidebar.classList.contains('show') ? closeSidebar() : openSidebar();
    });

    overlay.addEventListener('click', closeSidebar);

    // ── Sub-menu Toggle ──
    document.querySelectorAll('.has-sub > .nav-link').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const parent = link.parentElement;
            document.querySelectorAll('.has-sub.open').forEach(el => {
                if (el !== parent) el.classList.remove('open');
            });
            parent.classList.toggle('open');
        });
    });

    // ── Modal ──
    const modal = document.getElementById('infoModal');

    function openModal()  { modal.classList.add('show'); }
    function closeModal() { modal.classList.remove('show'); }

    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    // Show on page load
    setTimeout(openModal, 800);

    // ── Animated number counters ──
    function animateCounters() {
        document.querySelectorAll('.stat-number[data-target]').forEach(el => {
            const target = parseInt(el.dataset.target) || 0;
            if (target === 0) { el.textContent = '0'; return; }
            let start = 0;
            const duration = 1400;
            const step = (timestamp) => {
                if (!start) start = timestamp;
                const progress = Math.min((timestamp - start) / duration, 1);
                const ease = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.floor(ease * target).toLocaleString();
                if (progress < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        });
    }

    // ── Animated stat bars ──
    function animateBars() {
        document.querySelectorAll('.stat-bar-fill[data-width]').forEach(el => {
            setTimeout(() => {
                el.style.width = el.dataset.width + '%';
            }, 300);
        });
    }

    window.addEventListener('load', () => {
        animateCounters();
        animateBars();
    });
</script>
</body>
</html>