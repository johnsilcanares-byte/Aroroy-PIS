<?php
include './config/connection.php';
include './common_service/common_functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

$message = '';

// ---------- FETCH PATIENT DATA ----------
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: patients.php");
    exit;
}
$id = (int)$_GET['id'];

try {
    $stmt = $con->prepare("SELECT `id`, `patient_name`, `address`, `cnic`, 
                            `date_of_birth`, `phone_number`, `gender`, `age`
                            FROM `patients` WHERE `id` = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        header("Location: patients.php");
        exit;
    }

    // Calculate current age for display
    $currentAge = 'N/A';
    if (!empty($row['date_of_birth']) && $row['date_of_birth'] !== '0000-00-00') {
        $dobDate = new DateTime($row['date_of_birth']);
        $now     = new DateTime();
        $currentAge = $dobDate->diff($now)->y;
    }

    // Format for date picker (mm/dd/yyyy)
    $dobFormatted = '';
    if (!empty($row['date_of_birth']) && $row['date_of_birth'] !== '0000-00-00') {
        $dobFormatted = date('m/d/Y', strtotime($row['date_of_birth']));
    }

} catch (PDOException $ex) {
    error_log($ex->getMessage());
    $message = "Database error loading patient.";
    $row = null;
}

// ---------- HANDLE FORM SUBMISSION ----------
if (isset($_POST['save_Patient']) && $row) {
    $hiddenId    = (int)$_POST['hidden_id'];
    $patientName = trim($_POST['patient_name']);
    $address     = trim($_POST['address']);
    $cnic        = trim($_POST['cnic']);

    // date_of_birth from picker (mm/dd/yyyy)
    $dateBirthRaw = trim($_POST['date_of_birth']);
    $dateArr      = explode("/", $dateBirthRaw);
    if (count($dateArr) === 3) {
        $dateBirth = $dateArr[2] . '-' . $dateArr[0] . '-' . $dateArr[1];
    } else {
        $dateBirth = '';
    }

    $phoneNumber = trim($_POST['phone_number']);
    $gender      = $_POST['gender'] ?? '';

    $patientName = ucwords(strtolower($patientName));
    $address     = ucwords(strtolower($address));

    // 🔐 Auto‑update password whenever phone changes (MD5 hash of phone number)
    $newPassword = md5($phoneNumber);

    if ($patientName && $address && $cnic && $dateBirth && $phoneNumber && $gender) {
        // Recalculate age from new date of birth
        try {
            $dobObj  = new DateTime($dateBirth);
            $nowObj  = new DateTime();
            $newAge  = $dobObj->diff($nowObj)->y;
        } catch (Exception $e) {
            $newAge = 0;
        }

        $sql = "UPDATE `patients` 
                SET `patient_name` = ?, `address` = ?, `cnic` = ?, 
                    `date_of_birth` = ?, `phone_number` = ?, `gender` = ?,
                    `age` = ?, `password` = ?
                WHERE `id` = ?";
        try {
            $stmtUpdate = $con->prepare($sql);
            $stmtUpdate->execute([
                $patientName, $address, $cnic, $dateBirth, $phoneNumber,
                $gender, $newAge, $newPassword, $hiddenId
            ]);

            $message = 'Patient updated successfully.';
            header("Location: congratulation.php?goto_page=patients.php&message=" . urlencode($message));
            exit;
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            $message = "Database error. Please try again.";
        }
    } else {
        $message = "All fields are required.";
    }
}

// ---------- STATS (unchanged) ----------
$dateStr = date('Y-m-d');
$year    = date('Y');
$month   = date('m');
$todaysCount = $currentWeekCount = $currentMonthCount = $currentYearCount = 0;

try {
    $stmt = $con->prepare("SELECT COUNT(*) FROM `patient_visits` WHERE `visit_date` = ?");
    $stmt->execute([$dateStr]);
    $todaysCount = $stmt->fetchColumn();

    $stmt = $con->prepare("SELECT COUNT(*) FROM `patient_visits` WHERE YEARWEEK(`visit_date`, 1) = YEARWEEK(?, 1)");
    $stmt->execute([$dateStr]);
    $currentWeekCount = $stmt->fetchColumn();

    $stmt = $con->prepare("SELECT COUNT(*) FROM `patient_visits` WHERE YEAR(`visit_date`) = ? AND MONTH(`visit_date`) = ?");
    $stmt->execute([$year, $month]);
    $currentMonthCount = $stmt->fetchColumn();

    $stmt = $con->prepare("SELECT COUNT(*) FROM `patient_visits` WHERE YEAR(`visit_date`) = ?");
    $stmt->execute([$year]);
    $currentYearCount = $stmt->fetchColumn();
} catch (PDOException $ex) {
    error_log($ex->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Patient | Aroroy PIS</title>
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

        .page-breadcrumb h1 {
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.4px;
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
            padding: 20px 24px;
            position: relative;
            overflow: hidden;
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

        /* ============================================
           UPDATE PATIENT CARD
        ============================================ */
        .update-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            border: 1.5px solid rgba(0,0,0,0.04);
            box-shadow: var(--card-shadow);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .update-card:hover {
            box-shadow: var(--card-hover-shadow);
        }

        .update-card-header {
            padding: 20px 28px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .update-card-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .update-card-header-icon {
            width: 40px; height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--teal), #00a085);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        .update-card-header h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.2px;
            margin: 0;
        }

        .update-card-body {
            padding: 28px;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
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

        select.form-control-custom {
            cursor: pointer;
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%235e7494" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>');
            background-repeat: no-repeat;
            background-position: right 14px center;
        }

        /* Date picker custom styling */
        .input-group-custom {
            display: flex;
            align-items: stretch;
            width: 100%;
        }

        .input-group-custom .form-control-custom {
            flex: 1;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group-append-custom {
            display: flex;
        }

        .input-group-text-custom {
            padding: 0 14px;
            background: white;
            border: 1.5px solid rgba(0,0,0,0.08);
            border-left: none;
            border-radius: 0 12px 12px 0;
            display: flex;
            align-items: center;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 28px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .btn-save {
            padding: 11px 28px;
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
            background: linear-gradient(135deg, var(--teal), #00a085);
            color: white;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,212,170,0.3);
        }

        .footer {
            padding: 20px 36px;
            text-align: center;
            font-size: 0.77rem;
            color: var(--text-muted);
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        /* Override datetimepicker styles */
        .bootstrap-datetimepicker-widget {
            font-family: 'Outfit', sans-serif;
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

            .menu-toggle {
                display: flex !important;
            }

            .top-header { padding: 0 20px; }
            .page-body { padding: 20px; gap: 24px; }

            .header-profile-name { display: none; }
            .header-profile i.fa-chevron-down { display: none; }
            
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
            .form-grid { grid-template-columns: 1fr; gap: 15px; }
            
            .update-card-header { padding: 16px 20px; }
            .update-card-body { padding: 20px; }
            
            .button-group {
                justify-content: stretch;
            }
            
            .btn-save {
                justify-content: center;
                flex: 1;
            }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .stat-number { font-size: 1.6rem; }
            .page-breadcrumb h1 { font-size: 1.15rem; }
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(11,20,38,0.5);
            backdrop-filter: blur(4px);
            z-index: 999;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .age-info {
            margin-top: 6px;
            font-weight: 600;
            color: #00996e;
            background: rgba(0,212,170,0.08);
            padding: 6px 14px;
            border-radius: 10px;
            display: inline-block;
            font-size: 0.9rem;
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
                        <span class="nav-icon"><i class="fas fa-squares-four" style="font-size:0.85rem"></i></span>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="nav-item has-sub">
                    <a href="#" class="nav-link active">
                        <span class="nav-icon"><i class="fas fa-user-injured"></i></span>
                        <span>Patients</span>
                    </a>
                    <ul class="sub-menu">
                        <li><a href="new_prescription.php" class="nav-link">New Prescription</a></li>
                        <li><a href="patients.php" class="nav-link">Add Patients</a></li>
                        <li><a href="patient_history.php" class="nav-link">Patient History</a></li>
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
            <h1><i class="fas fa-user-edit" style="color: var(--teal); margin-right: 12px;"></i>Update Patient Details</h1>
        </div>
        <div class="header-right">
            <div class="header-profile" id="headerProfile">
                <div class="header-avatar"><i class="fas fa-user" style="font-size:0.8rem"></i></div>
                <span class="header-profile-name"><?php echo $_SESSION['display_name'] ?? 'Admin'; ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>

    <!-- Page Body -->
    <div class="page-body">
        <?php if ($message): ?>
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Update Patient Card -->
        <div class="update-card">
            <div class="update-card-header">
                <div class="update-card-header-left">
                    <div class="update-card-header-icon"><i class="fas fa-user-edit"></i></div>
                    <h3>Update Patient Information</h3>
                </div>
            </div>
            <div class="update-card-body">
                <form method="post">
                    <input type="hidden" name="hidden_id" value="<?php echo $row['id'];?>">
                    
                    <div class="form-grid">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Patient Name</label>
                            <input type="text" name="patient_name" required class="form-control-custom" value="<?php echo htmlspecialchars($row['patient_name']);?>" />
                        </div>

                        <div class="form-group-custom">
                            <label class="form-label-custom">Address</label> 
                            <input type="text" name="address" required class="form-control-custom" value="<?php echo htmlspecialchars($row['address']);?>" />
                        </div>

                        <div class="form-group-custom">
                            <label class="form-label-custom">ID Number</label>
                            <input type="text" name="cnic" required class="form-control-custom" value="<?php echo htmlspecialchars($row['cnic']);?>" />
                        </div>

                        <!-- Date of Birth with age display -->
                        <div class="form-group-custom">
                            <label class="form-label-custom">Date of Birth</label>
                            <div class="input-group-custom" id="date_of_birth" data-target-input="nearest">
                                <input type="text" class="form-control-custom datetimepicker-input" 
                                    data-target="#date_of_birth" name="date_of_birth" 
                                    value="<?php echo $dobFormatted;?>" />
                                <div class="input-group-append-custom" data-target="#date_of_birth" data-toggle="datetimepicker">
                                    <div class="input-group-text-custom">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="age-info">Current Age: <?php echo $currentAge; ?> years</div>
                        </div>

                        <div class="form-group-custom">
                            <label class="form-label-custom">Phone Number</label>
                            <input type="text" name="phone_number" required class="form-control-custom" value="<?php echo htmlspecialchars($row['phone_number']);?>" />
                        </div>

                        <div class="form-group-custom">
                            <label class="form-label-custom">Gender</label>
                            <select class="form-control-custom" name="gender">
                                <?php if (function_exists('getGender')) {
                                    echo getGender($row['gender']);
                                } else { ?>
                                    <option value="Male"   <?php echo ($row['gender'] === 'Male')   ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($row['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other"  <?php echo ($row['gender'] === 'Other')  ? 'selected' : ''; ?>>Other</option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" name="save_Patient" class="btn-save">
                            <i class="fas fa-save"></i> Update Patient
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div><!-- /page-body -->

    <div class="footer">
        &copy; <?php echo date('Y'); ?> Aroroy Patient Information System &nbsp;&middot;&nbsp; All rights reserved
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js"></script>

<script>
    // Sidebar Toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar    = document.getElementById('sidebar');
    const overlay    = document.getElementById('sidebarOverlay');

    if(menuToggle) {
        menuToggle.addEventListener('click', () => {
            const isOpen = sidebar.classList.contains('show');
            sidebar.classList.toggle('show', !isOpen);
            overlay.classList.toggle('show', !isOpen);
            menuToggle.classList.toggle('open', !isOpen);
        });
    }
    if(overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            menuToggle.classList.remove('open');
        });
    }

    // Sub-menu Toggle
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

    // Date picker
    $('#date_of_birth').datetimepicker({
        format: 'L'
    });
</script>

</body>
</html>