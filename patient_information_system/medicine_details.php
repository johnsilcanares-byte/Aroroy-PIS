<?php
// Start buffering to prevent "Headers already sent" errors
ob_start(); 

include './config/connection.php';
include './common_service/common_functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Read session values BEFORE releasing the lock
$display_name = $_SESSION['display_name'] ?? 'Admin';
$profile_picture = $_SESSION['profile_picture'] ?? 'default.png';

// Release session lock so other pages don't freeze
session_write_close();

$message = '';

// --- DELETE HANDLING ---
if (isset($_GET['delete']) && isset($_GET['medicine_detail_id'])) {
    $deleteId = (int)$_GET['medicine_detail_id'];
    try {
        $stmt = $con->prepare("DELETE FROM medicine_details WHERE id = ?");
        $stmt->execute([$deleteId]);
        $_SESSION['message'] = 'Packing detail deleted successfully.';
        header("Location: medicine_details.php");
        exit;
    } catch (PDOException $ex) {
        $_SESSION['message'] = 'Error deleting: ' . $ex->getMessage();
        header("Location: medicine_details.php");
        exit;
    }
}

// --- INSERT LOGIC ---
if (isset($_POST['submit'])) {
    $medicineId = (int)$_POST['medicine'];
    $packing = trim($_POST['packing']);

    if ($medicineId && $packing !== '') {
        try {
            $con->beginTransaction();

            // Check for duplicates
            $checkQuery = "SELECT COUNT(*) FROM `medicine_details` WHERE `medicine_id` = :m_id AND `packing` = :pack";
            $checkStmt = $con->prepare($checkQuery);
            $checkStmt->execute([':m_id' => $medicineId, ':pack' => $packing]);

            if ($checkStmt->fetchColumn() > 0) {
                $message = 'Error: This packing detail already exists for the selected medicine.';
                $con->rollBack();
            } else {
                $insertQuery = "INSERT INTO `medicine_details` (`medicine_id`, `packing`) VALUES (:m_id, :pack)";
                $insertStmt = $con->prepare($insertQuery);
                $insertStmt->execute([':m_id' => $medicineId, ':pack' => $packing]);
                $con->commit();
                $_SESSION['message'] = 'Packing saved successfully.';
                header("Location: medicine_details.php");
                exit;
            }
        } catch (PDOException $ex) {
            $con->rollBack();
            $message = 'Database error: ' . $ex->getMessage();
        }
    } else {
        $message = 'Please fill in all fields.';
    }
}

// --- FETCH DATA ---
$medicines = getMedicines($con);
$query = "SELECT m.medicine_name, md.id, md.packing, md.medicine_id 
          FROM medicines AS m 
          JOIN medicine_details AS md ON m.id = md.medicine_id 
          ORDER BY m.medicine_name ASC, md.packing ASC";

try {
    $stmtDetails = $con->prepare($query);
    $stmtDetails->execute();
} catch (PDOException $ex) {
    die("Query Failed: " . $ex->getMessage());
}

// Get message from session if available
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
} elseif (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// --- OPTIONAL STATS (preserved from original) ---
$date = date('Y-m-d');
$year = date('Y');
$month = date('m');
$todaysCount = $currentWeekCount = $currentMonthCount = $currentYearCount = 0;
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
} catch (PDOException $ex) {
     error_log($ex->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Details | Aroroy PIS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap4.min.css">
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
           CUSTOM MEDICINE DETAILS STYLES
        ============================================ */
        .custom-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            border: 1.5px solid rgba(0,0,0,0.04);
            box-shadow: var(--card-shadow);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .custom-card:hover {
            box-shadow: var(--card-hover-shadow);
        }

        .custom-card-header {
            padding: 20px 28px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .custom-card-header-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--teal), #00a085);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        .custom-card-header h3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.2px;
        }

        .custom-card-body {
            padding: 28px;
        }

        /* Form Styles */
        .form-group-custom {
            margin-bottom: 1.25rem;
        }

        .form-label-custom {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control-custom {
            width: 100%;
            padding: 12px 16px;
            font-size: 0.88rem;
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
            background-position: right 16px center;
        }

        .btn-custom {
            padding: 12px 24px;
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

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--navy), var(--navy-mid));
            color: white;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(11,20,38,0.25);
        }

        /* Alert Styles */
        .alert-custom {
            padding: 16px 20px;
            border-radius: 14px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.85rem;
        }

        .alert-success-custom {
            background: rgba(0,212,170,0.08);
            border-left: 4px solid var(--teal);
            color: #008f68;
        }

        .alert-error-custom {
            background: rgba(255,107,138,0.08);
            border-left: 4px solid var(--rose);
            color: #e04e6e;
        }

        /* Table Styles */
        .data-table-wrapper {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .data-table thead th {
            text-align: left;
            padding: 16px 16px;
            background: var(--off-white);
            color: var(--text-primary);
            font-weight: 600;
            border-bottom: 1.5px solid rgba(0,0,0,0.06);
        }

        .data-table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(0,0,0,0.04);
            color: var(--text-secondary);
        }

        .data-table tbody tr:hover {
            background: rgba(0,212,170,0.03);
        }

        .btn-edit {
            background: rgba(56,189,248,0.1);
            color: #0ea5e9;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-edit:hover {
            background: rgba(56,189,248,0.2);
            transform: translateY(-1px);
        }

        /* New Delete button style */
        .btn-delete {
            background: rgba(255,107,138,0.1);
            color: #e04e6e;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            margin-left: 6px;
        }

        .btn-delete:hover {
            background: rgba(255,107,138,0.2);
            transform: translateY(-1px);
        }

        .footer {
            padding: 20px 36px;
            text-align: center;
            font-size: 0.77rem;
            color: var(--text-muted);
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        /* ============================================
           CUSTOM DATATABLES BUTTONS STYLES
        ============================================ */
        .dt-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .dt-buttons .btn {
            background: white;
            border: 1.5px solid rgba(0,0,0,0.08);
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 0.75rem;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            transition: all 0.25s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
        }

        .dt-buttons .btn:hover {
            border-color: var(--teal);
            color: var(--teal);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,212,170,0.15);
        }

        .dt-buttons .btn i {
            font-size: 0.85rem;
        }

        /* Hide default pagination */
        .dataTables_paginate {
            display: none !important;
        }

        /* Hide default pagination info if needed */
        .dataTables_info {
            display: none !important;
        }

        /* Style the length menu */
        .dataTables_length {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .dataTables_length label {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dataTables_length select {
            padding: 6px 12px;
            border: 1.5px solid rgba(0,0,0,0.08);
            border-radius: 8px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.8rem;
            background: white;
            cursor: pointer;
        }

        /* Style the search box */
        .dataTables_filter {
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .dataTables_filter label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .dataTables_filter input {
            padding: 8px 14px;
            border: 1.5px solid rgba(0,0,0,0.08);
            border-radius: 10px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.8rem;
            width: 250px;
            transition: all 0.25s ease;
        }

        .dataTables_filter input:focus {
            outline: none;
            border-color: var(--teal);
            box-shadow: 0 0 0 3px rgba(0,212,170,0.1);
        }

        /* Flex layout for table controls */
        .table-controls-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
            gap: 15px;
        }

        .buttons-wrapper {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Responsive */
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
            
            .custom-card-header { padding: 16px 20px; }
            .custom-card-body { padding: 20px; }
            
            .table-controls-wrapper {
                flex-direction: column;
                align-items: stretch;
            }
            
            .dataTables_filter input {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .page-breadcrumb h1 { font-size: 1.15rem; }
            .dt-buttons .btn span {
                display: none;
            }
            .dt-buttons .btn i {
                margin: 0;
            }
            .dt-buttons .btn {
                padding: 8px 12px;
            }
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(11,20,38,0.5);
            backdrop-filter: blur(4px);
            z-index: 999;
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
                    <img src="user_images/<?php echo $profile_picture; ?>" alt="User">
                </div>
                <div class="user-card-info">
                    <h4><?php echo htmlspecialchars($display_name); ?></h4>
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
                    <a href="#" class="nav-link active">
                        <span class="nav-icon"><i class="fas fa-pills"></i></span>
                        <span>Medicines</span>
                    </a>
                    <ul class="sub-menu">
                        <li><a href="medicines.php" class="nav-link">Add Medicine</a></li>
                         <li><a href="medicine_details.php" class="nav-link active">Medicine Details</a></li>
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
            <h1>Medicine Details</h1>
            <p>Manage medicine packing and inventory details</p>
        </div>
        <div class="header-right">
            <div class="header-profile">
                <div class="header-avatar"><i class="fas fa-user" style="font-size:0.8rem"></i></div>
                <span class="header-profile-name"><?php echo htmlspecialchars($display_name); ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <button class="menu-toggle" id="menuToggle"><span></span><span></span><span></span></button>
        </div>
    </div>

    <!-- Page Body -->
    <div class="page-body">

        <!-- Alert Message -->
        <?php if (!empty($message)): ?>
        <div class="alert-custom <?php echo (stripos($message, 'error') !== false) ? 'alert-error-custom' : 'alert-success-custom'; ?>">
            <i class="fas <?php echo (stripos($message, 'error') !== false) ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Add Medicine Details Form Card -->
        <div class="custom-card">
            <div class="custom-card-header">
                <div class="custom-card-header-icon"><i class="fas fa-plus-circle"></i></div>
                <h3>Add Medicine Details</h3>
            </div>
            <div class="custom-card-body">
                <form method="post">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Select Medicine</label>
                            <select name="medicine" class="form-control-custom" required>
                                <?php echo $medicines;?>
                            </select>
                        </div>
                        <div class="form-group-custom">
                            <label class="form-label-custom">Packing</label>
                            <input name="packing" class="form-control-custom" required placeholder="e.g., 10 tablets per strip" autocomplete="off" />
                        </div>
                        <div class="form-group-custom" style="display: flex; align-items: flex-end;">
                            <button type="submit" name="submit" class="btn-custom btn-primary-custom">
                                <i class="fas fa-save"></i> Save Packing Details
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Medicine Inventory Details Table Card -->
        <div class="custom-card">
            <div class="custom-card-header">
                <div class="custom-card-header-icon"><i class="fas fa-table-list"></i></div>
                <h3>Medicine Inventory Details</h3>
            </div>
            <div class="custom-card-body">
                <div class="data-table-wrapper">
                    <table id="medicine_details" class="data-table" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width: 60px;">S.No</th>
                                <th>Medicine Name</th>
                                <th>Packing</th>
                                <th style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $serial = 0;
                            while ($row = $stmtDetails->fetch(PDO::FETCH_ASSOC)){
                                $serial++;
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $serial; ?></td>
                                <td class="font-weight-bold" style="color: var(--text-primary);"><?php echo htmlspecialchars($row['medicine_name']);?></td>
                                <td><?php echo htmlspecialchars($row['packing']);?></td>
                                <td>
                                    <a href="update_medicine_details.php?medicine_id=<?php echo $row['medicine_id'];?>&medicine_detail_id=<?php echo $row['id'];?>&packing=<?php echo urlencode($row['packing']);?>" 
                                       class="btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?delete=1&medicine_detail_id=<?php echo $row['id'];?>" class="btn-delete" onclick="return confirm('Delete this packing detail?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /page-body -->

    <div class="footer">
        &copy; <?php echo date('Y'); ?> Aroroy Patient Management System &nbsp;&middot;&nbsp; All rights reserved
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<script>
    // Sidebar Toggle (burger only on mobile)
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

    if(menuToggle) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.contains('show') ? closeSidebar() : openSidebar();
        });
    }

    if(overlay) {
        overlay.addEventListener('click', closeSidebar);
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

    // Initialize DataTable
    $(function () {
        var table = $("#medicine_details").DataTable({
            "responsive": true, 
            "lengthChange": true, 
            "autoWidth": false,
            "pageLength": -1, // Show all entries
            "paging": false, // Disable pagination
            "info": false, // Hide info text
            "language": {
                "search": "<i class='fas fa-search' style='color: #5e7494;'></i>",
                "searchPlaceholder": "Search records...",
                "lengthMenu": "Show _MENU_ entries"
            },
            "dom": '<"table-controls-wrapper"<"buttons-wrapper"B><"dataTables_length"l><"dataTables_filter"f>>rt<"bottom">',
            "buttons": [
                {
                    extend: 'copy',
                    text: '<i class="fas fa-copy"></i> <span>Copy</span>',
                    className: 'btn-copy',
                    exportOptions: { columns: [0, 1, 2] }
                },
                {
                    extend: 'csv',
                    text: '<i class="fas fa-file-csv"></i> <span>CSV</span>',
                    className: 'btn-csv',
                    exportOptions: { columns: [0, 1, 2] }
                },
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> <span>Excel</span>',
                    className: 'btn-excel',
                    exportOptions: { columns: [0, 1, 2] }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> <span>PDF</span>',
                    className: 'btn-pdf',
                    exportOptions: { columns: [0, 1, 2] }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> <span>Print</span>',
                    className: 'btn-print',
                    exportOptions: { columns: [0, 1, 2] }
                }
            ]
        });

        // Custom button styling (if needed)
        $('.dt-buttons .btn').each(function() {
            var $btn = $(this);
            if($btn.hasClass('buttons-copy')) {
                $btn.html('<i class="fas fa-copy"></i> <span>Copy</span>');
            } else if($btn.hasClass('buttons-csv')) {
                $btn.html('<i class="fas fa-file-csv"></i> <span>CSV</span>');
            } else if($btn.hasClass('buttons-excel')) {
                $btn.html('<i class="fas fa-file-excel"></i> <span>Excel</span>');
            } else if($btn.hasClass('buttons-pdf')) {
                $btn.html('<i class="fas fa-file-pdf"></i> <span>PDF</span>');
            } else if($btn.hasClass('buttons-print')) {
                $btn.html('<i class="fas fa-print"></i> <span>Print</span>');
            }
        });
    });
</script>
</body>
</html>
<?php 
// End buffering and send to browser
ob_end_flush(); 
?>