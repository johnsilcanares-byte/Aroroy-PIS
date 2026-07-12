<?php 
// medicines.php
include './config/connection.php';
include './common_service/common_functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!(isset($_SESSION['user_id']))) {
    header("location:index.php");
    exit;
}

$message = '';

// --- INSERT LOGIC ---
if(isset($_POST['save_medicine'])) {
    $medicineName = trim($_POST['medicine_name']);
    $medicineName = ucwords(strtolower($medicineName));
    
    if($medicineName != '') {
        try {
            $con->beginTransaction();

            // Server-side check to prevent duplicates if AJAX fails
            $stmtCheck = $con->prepare("SELECT COUNT(*) FROM `medicines` WHERE `medicine_name` = :name");
            $stmtCheck->execute([':name' => $medicineName]);
            if($stmtCheck->fetchColumn() > 0) {
                $message = 'Error: This medicine already exists in the system.';
                $con->rollBack();
            } else {
                $query = "INSERT INTO `medicines`(`medicine_name`) VALUES(:name);";
                $stmtMedicine = $con->prepare($query);
                $stmtMedicine->execute([':name' => $medicineName]);
                $con->commit();
                $message = 'Medicine added successfully.';
                header("Location:congratulation.php?goto_page=medicines.php&message=" . urlencode($message));
                exit;
            }
        } catch(PDOException $ex) {
            $con->rollback();
            $message = "Error: Could not save medicine."; 
        }
    } else {
        $message = 'Empty form cannot be submitted.';
    }
}

try {
    $query = "SELECT `id`, `medicine_name` FROM `medicines` ORDER BY `medicine_name` ASC;";
    $stmt = $con->prepare($query);
    $stmt->execute();
} catch(PDOException $ex) {
    echo "Query failed: " . $ex->getMessage();
    exit;   
}

$msg = $_GET['message'] ?? $message;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aroroy PIS — Medicines</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
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

        /* Medicines specific styles */
        .page-body {
            padding: 32px 36px;
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        .card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 28px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--teal);
            font-size: 1.2rem;
        }

        .form-section {
            padding: 24px 28px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 20px;
        }

        .form-group {
            flex: 2;
            min-width: 250px;
        }

        .form-group label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            display: block;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid rgba(0,0,0,0.08);
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.85rem;
            transition: all 0.2s;
            background: white;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--teal);
            box-shadow: 0 0 0 3px rgba(0,212,170,0.1);
        }

        .btn-teal {
            background: linear-gradient(135deg, var(--teal), #00a085);
            border: none;
            color: white;
            padding: 12px 28px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Outfit', sans-serif;
        }

        .btn-teal:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,212,170,0.3);
        }

        .btn-teal:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--sky), #0ea5e9);
            border: none;
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(56,189,248,0.3);
            color: white;
        }

        .table-wrapper {
            padding: 0 0 20px 0;
            overflow-x: auto;
        }

        .med-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .med-table th {
            text-align: left;
            padding: 14px 20px;
            background: var(--navy);
            color: white;
            font-weight: 600;
        }

        .med-table th.text-center {
            text-align: center;
        }

        .med-table td {
            padding: 12px 20px;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            color: var(--text-secondary);
            vertical-align: middle;
        }

        .med-table td.text-center {
            text-align: center;
        }

        .med-table tr:hover td {
            background: rgba(0,212,170,0.03);
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

        /* Alert message styles */
        .alert-message {
            margin: 0 28px 20px 28px;
            padding: 14px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        .alert-error {
            background: rgba(255,107,138,0.12);
            border: 1px solid rgba(255,107,138,0.25);
            color: #e5484d;
        }

        .alert-success {
            background: rgba(0,212,170,0.12);
            border: 1px solid rgba(0,212,170,0.25);
            color: #00a085;
        }

        .alert-message i {
            font-size: 1.1rem;
        }

        .alert-message span {
            flex: 1;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .alert-message .close-alert {
            cursor: pointer;
            font-size: 1rem;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .alert-message .close-alert:hover {
            opacity: 1;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* DataTables customization - Hide pagination buttons */
        .dataTables_wrapper .dataTables_paginate {
            display: none !important;
        }
        
        .dataTables_wrapper .dataTables_info {
            display: none !important;
        }
        
        .dataTables_wrapper .dataTables_length {
            display: none !important;
        }
        
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 20px;
            float: right;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border: 1.5px solid rgba(0,0,0,0.08);
            border-radius: 12px;
            padding: 8px 16px;
            font-family: 'Outfit', sans-serif;
            width: 250px;
        }
        
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--teal);
            outline: none;
        }
        
        .dataTables_wrapper .dataTables_filter label {
            color: var(--text-secondary);
            font-weight: 500;
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
            .page-body { padding: 20px; }
            .form-row { flex-direction: column; align-items: stretch; }
            .form-group { width: 100%; }
            .btn-teal { width: 100%; justify-content: center; }
            .header-profile-name { display: none; }
            .med-table th, .med-table td { padding: 10px 12px; font-size: 0.75rem; }
            .alert-message { margin: 0 16px 16px 16px; }
            .dataTables_wrapper .dataTables_filter {
                float: none;
                margin-bottom: 15px;
            }
            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
            }
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
                         <li><a href="patients_records.php" class="nav-link">View All Patients</a></li>
                    </ul>
                </li>

                <li class="nav-item has-sub open">
                    <a href="#" class="nav-link active">
                        <span class="nav-icon"><i class="fas fa-pills"></i></span>
                        <span>Medicines</span>
                    </a>
                    <ul class="sub-menu">
                        <li><a href="medicines.php" class="nav-link active">Add Medicine</a></li>
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

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="top-header">
        <div class="page-breadcrumb">
            <h1>Medicine Management</h1>
            <p>Add, manage, and organize medicine inventory</p>
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
        <!-- Add Medicine Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-plus-circle"></i> Add New Medicine
                </div>
            </div>
            <div class="form-section">
                <form method="post" id="medicineForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-capsules"></i> Medicine Name</label>
                            <input type="text" id="medicine_name" name="medicine_name" required 
                                   placeholder="Enter medicine name (e.g., Paracetamol, Amoxicillin)" autocomplete="off"/>
                        </div>
                        <div class="form-group" style="flex: 0 0 auto;">
                            <button type="submit" id="save_medicine" name="save_medicine" class="btn-teal">
                                <i class="fas fa-save"></i> Save Medicine
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Available Medicines Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-list-alt"></i> Available Medicines
                </div>
            </div>
            <div class="table-wrapper">
                <table id="all_medicines" class="med-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th width="8%" class="text-center">#</th>
                            <th width="77%">Medicine Name</th>
                            <th width="15%" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $serial = 0;
                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $serial++;
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $serial;?></td>
                            <td class="font-weight-bold" style="color: var(--text-primary);"><?php echo htmlspecialchars($row['medicine_name']);?></td>
                            <td class="text-center">
                                <a href="update_medicine.php?id=<?php echo $row['id'];?>" class="btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> Aroroy Patient Management System &nbsp;&middot;&nbsp; All rights reserved
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>

<script>
    // Sidebar toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar    = document.getElementById('sidebar');
    const overlay    = document.getElementById('sidebarOverlay');

    function openSidebar() { sidebar.classList.add('show'); overlay.classList.add('show'); menuToggle.classList.add('open'); }
    function closeSidebar() { sidebar.classList.remove('show'); overlay.classList.remove('show'); menuToggle.classList.remove('open'); }
    menuToggle.addEventListener('click', () => sidebar.classList.contains('show') ? closeSidebar() : openSidebar());
    overlay.addEventListener('click', closeSidebar);

    // Submenu toggle
    document.querySelectorAll('.has-sub > .nav-link').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const parent = link.parentElement;
            document.querySelectorAll('.has-sub.open').forEach(el => { if (el !== parent) el.classList.remove('open'); });
            parent.classList.toggle('open');
        });
    });

    $(document).ready(function() {
        // Alert messages - display if exists
        var message = "<?php echo addslashes($msg); ?>";
        if(message !== '' && message !== 'Array') {
            // Create and show alert
            var alertClass = message.toLowerCase().includes('error') ? 'alert-error' : 'alert-success';
            var alertIcon = message.toLowerCase().includes('error') ? 'fa-exclamation-triangle' : 'fa-check-circle';
            var alertHtml = `
                <div class="alert-message ${alertClass}" id="autoAlert">
                    <i class="fas ${alertIcon}"></i>
                    <span>${message}</span>
                    <i class="fas fa-times close-alert" onclick="this.parentElement.remove()"></i>
                </div>
            `;
            $('.page-body').prepend(alertHtml);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $('#autoAlert').fadeOut(500, function() { $(this).remove(); });
            }, 5000);
        }

        // Initialize DataTable with pagination disabled
        $("#all_medicines").DataTable({
            "responsive": true, 
            "autoWidth": false,
            "order": [[1, "asc"]],
            "paging": false,  // Disable pagination (removes Previous/Next buttons)
            "info": false,    // Disable info text (Showing X of Y entries)
            "lengthChange": false, // Disable entries per page dropdown
            "language": {
                "search": "Search medicines:",
                "zeroRecords": "No medicines found"
            }
        });

        // AJAX Duplicate Check
        $("#medicine_name").blur(function() {
            var medicineName = $(this).val().trim();
            if(medicineName !== '') {
                $.ajax({
                    url: "ajax/check_medicine_name.php",
                    type: 'GET', 
                    data: { 'medicine_name': medicineName },
                    success: function (count) {
                        if(parseInt(count) > 0) {
                            // Show error alert
                            var duplicateHtml = `
                                <div class="alert-message alert-error" id="duplicateAlert">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>This medicine is already in the records.</span>
                                    <i class="fas fa-times close-alert" onclick="this.parentElement.remove()"></i>
                                </div>
                            `;
                            // Remove existing duplicate alert if any
                            $('#duplicateAlert').remove();
                            $('.page-body').prepend(duplicateHtml);
                            
                            $("#save_medicine").attr("disabled", "disabled");
                            
                            // Auto-hide after 3 seconds
                            setTimeout(function() {
                                $('#duplicateAlert').fadeOut(500, function() { $(this).remove(); });
                            }, 3000);
                        } else {
                            $("#save_medicine").removeAttr("disabled");
                            $('#duplicateAlert').remove();
                        }
                    }
                });
            }
        });
        
        // Clear duplicate alert when user starts typing again
        $("#medicine_name").focus(function() {
            $('#duplicateAlert').remove();
            $("#save_medicine").removeAttr("disabled");
        });
    });
</script>
</body>
</html>