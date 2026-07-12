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

$message = '';

if (isset($_POST['save_Patient'])) {
    $patientName = trim($_POST['patient_name']);
    $address     = trim($_POST['address']);
    $cnic        = trim($_POST['cnic']);
    $dateBirth   = trim($_POST['date_of_birth']);
    $phoneNumber = trim($_POST['phone_number']);
    $gender      = $_POST['gender'];

    $dateArr   = explode("/", $dateBirth);
    $dateBirth = $dateArr[2].'-'.$dateArr[0].'-'.$dateArr[1];

    $patientName = ucwords(strtolower($patientName));
    $address     = ucwords(strtolower($address));

    // 🔐 Automatically set password = MD5 of phone number
    $password = md5($phoneNumber);

    if ($patientName != '' && $address != '' && $cnic != '' && $dateBirth != '' && $phoneNumber != '' && $gender != '') {
        try {
            $con->beginTransaction();
            $query = "INSERT INTO `patients`(`patient_name`, `address`, `cnic`, `date_of_birth`, `phone_number`, `gender`, `password`)
                      VALUES(:name, :address, :cnic, :dob, :phone, :gender, :pass)";
            $stmtPatient = $con->prepare($query);
            $stmtPatient->execute([
                ':name'    => $patientName,
                ':address' => $address,
                ':cnic'    => $cnic,
                ':dob'     => $dateBirth,
                ':phone'   => $phoneNumber,
                ':gender'  => $gender,
                ':pass'    => $password
            ]);
            $con->commit();
            $message = 'Patient added successfully.';
        } catch(PDOException $ex) {
            $con->rollback();
            error_log($ex->getMessage());
            $message = 'Error adding patient.';
        }
    } else {
        $message = 'All fields required.';
    }
    header("Location:congratulation.php?goto_page=patients.php&message=$message");
    exit;
}

// Fetch patients with age
try {
    $query = "SELECT `id`, `patient_name`, `address`, `cnic`, 
              date_format(`date_of_birth`, '%d %b %Y') as `date_of_birth`, 
              `phone_number`, `gender`,
              CASE 
                WHEN `date_of_birth` = '0000-00-00' OR `date_of_birth` IS NULL THEN 'N/A'
                ELSE TIMESTAMPDIFF(YEAR, `date_of_birth`, CURDATE())
              END as age
              FROM `patients` ORDER BY `patient_name` ASC;";
    $stmtPatient1 = $con->prepare($query);
    $stmtPatient1->execute();
    $patients = $stmtPatient1->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $ex) {
    error_log($ex->getMessage());
    $patients = [];
}

$totalPatients = count($patients);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Aroroy PIS — Patients</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tempus Dominus (date picker) -->
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

        /* SIDEBAR */
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

        .logo-mark { display: flex; align-items: center; gap: 12px; }

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

        .nav-section { padding: 16px 16px 0; }

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
        .sub-menu .nav-link.active { color: var(--teal); background: rgba(0,212,170,0.08); border: none; }

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

        /* MAIN CONTENT */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* TOP HEADER */
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

        .page-breadcrumb { display: flex; flex-direction: column; gap: 2px; }

        .page-breadcrumb h1 {
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.4px;
        }

        .page-breadcrumb p { font-size: 0.78rem; color: var(--text-secondary); }

        .header-right { display: flex; align-items: center; gap: 10px; }

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

        .menu-toggle span { width: 16px; height: 2px; background: white; border-radius: 2px; transition: all 0.3s; }
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

        .header-profile-name { font-size: 0.84rem; font-weight: 600; color: var(--text-primary); }
        .header-profile i { font-size: 0.7rem; color: var(--text-muted); }

        /* PAGE BODY */
        .page-body {
            padding: 32px 36px;
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        /* STAT BANNER */
        .stat-banner {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .stat-mini {
            background: white;
            border-radius: 18px;
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: var(--card-shadow);
            border: 1.5px solid transparent;
            transition: all 0.3s ease;
            animation: card-in 0.6s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        .stat-mini:nth-child(1) { animation-delay: 0.05s; }
        .stat-mini:nth-child(2) { animation-delay: 0.12s; }
        .stat-mini:nth-child(3) { animation-delay: 0.19s; }

        .stat-mini:hover { transform: translateY(-4px); box-shadow: var(--card-hover-shadow); }
        .stat-mini.teal:hover { border-color: rgba(0,212,170,0.25); }
        .stat-mini.sky:hover  { border-color: rgba(56,189,248,0.25); }
        .stat-mini.violet:hover { border-color: rgba(167,139,250,0.25); }

        .stat-mini-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .teal  .stat-mini-icon { background: rgba(0,212,170,0.1);  color: #00b891; }
        .sky   .stat-mini-icon { background: rgba(56,189,248,0.1); color: #0ea5e9; }
        .violet .stat-mini-icon { background: rgba(167,139,250,0.1); color: #8b5cf6; }

        .stat-mini-info h4 { font-size: 1.6rem; font-weight: 900; letter-spacing: -1px; }
        .teal   .stat-mini-info h4 { color: #00996e; }
        .sky    .stat-mini-info h4 { color: #0284c7; }
        .violet .stat-mini-info h4 { color: #7c3aed; }

        .stat-mini-info p { font-size: 0.77rem; color: var(--text-secondary); font-weight: 500; }

        @keyframes card-in {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* PMS CARD */
        .pms-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1.5px solid rgba(0,0,0,0.04);
            overflow: hidden;
            animation: card-in 0.6s cubic-bezier(0.4, 0, 0.2, 1) both;
            animation-delay: 0.1s;
        }

        .pms-card-header {
            padding: 20px 28px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .pms-card-header-left { display: flex; align-items: center; gap: 12px; }

        .pms-card-header-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
        }

        .pms-card-header h3 { font-size: 0.98rem; font-weight: 700; color: var(--text-primary); }
        .pms-card-header p  { font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px; }
        .pms-card-body { padding: 28px; }

        /* FORM STYLES */
        .form-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .form-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }

        .form-group { display: flex; flex-direction: column; gap: 7px; }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            letter-spacing: 0.3px;
        }

        .form-control-pms {
            padding: 11px 16px;
            border: 1.5px solid rgba(0,0,0,0.09);
            border-radius: 11px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.88rem;
            color: var(--text-primary);
            background: var(--off-white);
            transition: all 0.25s ease;
            outline: none;
            width: 100%;
        }

        .form-control-pms:focus {
            border-color: var(--teal);
            background: white;
            box-shadow: 0 0 0 4px rgba(0,212,170,0.08);
        }

        .form-control-pms::placeholder { color: var(--text-muted); font-size: 0.83rem; }

        .input-with-icon { position: relative; }
        .input-with-icon .form-control-pms { padding-right: 42px; }
        .input-icon {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.85rem;
            pointer-events: none;
        }

        select.form-control-pms {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%239bb0c9' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 38px;
        }

        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 10px;
            margin-top: 8px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .btn-ghost {
            padding: 10px 22px;
            border: 1.5px solid rgba(0,0,0,0.1);
            border-radius: 11px;
            background: white;
            color: var(--text-secondary);
            font-family: 'Outfit', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s;
        }

        .btn-ghost:hover { border-color: var(--teal); color: var(--teal); }

        .btn-primary-pms {
            padding: 11px 28px;
            border: none;
            border-radius: 11px;
            background: linear-gradient(135deg, var(--teal), #00a085);
            color: white;
            font-family: 'Outfit', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            display: flex; align-items: center; gap: 8px;
            transition: all 0.25s;
            box-shadow: 0 6px 18px rgba(0,212,170,0.28);
        }

        .btn-primary-pms:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(0,212,170,0.38); }

        /* TABLE STYLES */
        .table-search-bar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 18px; gap: 14px; flex-wrap: wrap;
        }

        .search-input-wrap { position: relative; flex: 1; min-width: 220px; max-width: 360px; }

        .search-input-wrap input {
            width: 100%; padding: 10px 16px 10px 40px;
            border: 1.5px solid rgba(0,0,0,0.08); border-radius: 11px;
            font-family: 'Outfit', sans-serif; font-size: 0.85rem;
            background: var(--off-white); color: var(--text-primary); outline: none;
            transition: all 0.25s;
        }

        .search-input-wrap input:focus { border-color: var(--teal); background: white; box-shadow: 0 0 0 4px rgba(0,212,170,0.08); }
        .search-input-wrap .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem; }

        .table-count-badge {
            font-size: 0.75rem; font-weight: 600; padding: 5px 13px;
            border-radius: 20px; background: rgba(0,212,170,0.1); color: #00996e; white-space: nowrap;
        }

        .pms-table-wrap { overflow-x: auto; border-radius: 14px; border: 1.5px solid rgba(0,0,0,0.05); }

        .pms-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }

        .pms-table thead tr { background: var(--navy); }

        .pms-table thead th {
            padding: 14px 18px; font-size: 0.72rem; font-weight: 700;
            color: rgba(255,255,255,0.65); text-transform: uppercase;
            letter-spacing: 0.8px; white-space: nowrap; text-align: left;
        }

        .pms-table thead th:first-child { border-radius: 13px 0 0 0; }
        .pms-table thead th:last-child  { border-radius: 0 13px 0 0; text-align: center; }

        .pms-table tbody tr { border-bottom: 1px solid rgba(0,0,0,0.04); transition: all 0.2s ease; }
        .pms-table tbody tr:last-child { border-bottom: none; }
        .pms-table tbody tr:hover { background: rgba(0,212,170,0.03); }

        .pms-table tbody td { padding: 14px 18px; color: var(--text-primary); vertical-align: middle; }
        .pms-table tbody td:last-child { text-align: center; }

        .patient-name-cell { display: flex; align-items: center; gap: 10px; }

        .patient-avatar-sm {
            width: 34px; height: 34px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 700; color: white; flex-shrink: 0;
        }

        .patient-name-text { font-weight: 600; color: var(--text-primary); }
        .row-serial { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; }

        .gender-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 20px; font-size: 0.73rem; font-weight: 600;
        }

        .gender-badge.male   { background: rgba(56,189,248,0.1);  color: #0284c7; }
        .gender-badge.female { background: rgba(255,107,138,0.1); color: #e0466a; }
        .gender-badge.other  { background: rgba(167,139,250,0.1); color: #7c3aed; }

        .btn-edit {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 9px;
            background: rgba(0,212,170,0.08); color: #00996e;
            font-size: 0.78rem; font-weight: 700; text-decoration: none;
            border: 1.5px solid rgba(0,212,170,0.15); transition: all 0.25s;
            font-family: 'Outfit', sans-serif;
        }

        .btn-edit:hover { background: rgba(0,212,170,0.15); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,212,170,0.2); }

        .table-footer {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 0 0; font-size: 0.78rem; color: var(--text-secondary); flex-wrap: wrap; gap: 10px;
        }

        .no-patients { text-align: center; padding: 48px 20px; color: var(--text-muted); }
        .no-patients i { font-size: 2.5rem; margin-bottom: 12px; opacity: 0.3; display: block; }
        .no-patients p { font-size: 0.88rem; }

        /* AVATAR COLORS */
        .av-0 { background: linear-gradient(135deg, #00d4aa, #00a085); }
        .av-1 { background: linear-gradient(135deg, #38bdf8, #0284c7); }
        .av-2 { background: linear-gradient(135deg, #a78bfa, #7c3aed); }
        .av-3 { background: linear-gradient(135deg, #ffb340, #e69500); }
        .av-4 { background: linear-gradient(135deg, #ff6b8a, #e0466a); }

        /* TOAST NOTIFICATION */
        .toast-wrap {
            position: fixed;
            top: 24px; right: 24px;
            z-index: 9999;
        }
        .toast-item {
            background: white;
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.14);
            border-left: 4px solid var(--teal);
            min-width: 280px;
            animation: toast-in 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }
        @keyframes toast-in {
            from { opacity: 0; transform: translateX(40px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        .toast-icon { font-size: 1.1rem; color: var(--teal); }
        .toast-text strong { font-size: 0.85rem; font-weight: 700; color: var(--text-primary); display: block; }
        .toast-text span   { font-size: 0.75rem; color: var(--text-secondary); }

        .sidebar-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(11,20,38,0.5); backdrop-filter: blur(4px); z-index: 999;
        }

        .footer {
            padding: 20px 36px; text-align: center; font-size: 0.77rem;
            color: var(--text-muted); border-top: 1px solid rgba(0,0,0,0.05);
        }

        /* Responsive */
        @media (max-width: 1100px) {
            .stat-banner { grid-template-columns: repeat(3, 1fr); }
            .form-grid-3 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); z-index: 1001; }
            .sidebar.show { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .menu-toggle { display: flex !important; }
            .top-header { padding: 0 20px; }
            .page-body { padding: 20px; gap: 20px; }
            .stat-banner { grid-template-columns: 1fr 1fr; }
            .form-grid-3, .form-grid-2 { grid-template-columns: 1fr; }
            .header-profile-name { display: none; }
            .header-profile i.fa-chevron-down { display: none; }
        }
        @media (max-width: 480px) { .stat-banner { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php if(isset($_GET['message']) && $_GET['message'] != ''): ?>
<div class="toast-wrap" id="toastWrap">
    <div class="toast-item">
        <i class="fas fa-check-circle toast-icon"></i>
        <div class="toast-text">
            <strong>Success</strong>
            <span><?php echo htmlspecialchars($_GET['message']); ?></span>
        </div>
    </div>
</div>
<script>setTimeout(()=>{ const t=document.getElementById('toastWrap'); if(t){ t.style.transition='opacity 0.4s'; t.style.opacity='0'; setTimeout(()=>t.remove(), 400); } }, 3500);</script>
<?php endif; ?>

<!-- Overlay -->
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

                <li class="nav-item has-sub open">
                    <a href="#" class="nav-link active">
                        <span class="nav-icon"><i class="fas fa-user-injured"></i></span>
                        <span>Patients</span>
                    </a>
                    <ul class="sub-menu">
                        <li><a href="new_prescription.php" class="nav-link">New Prescription</a></li>
                        <li><a href="patients.php" class="nav-link active">Add Patients</a></li>
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
            <h1>Patient Management</h1>
            <p>Register and manage all patient records</p>
        </div>
        <div class="header-right">
            <div class="header-profile">
                <div class="header-avatar"><i class="fas fa-user" style="font-size:0.8rem"></i></div>
                <span class="header-profile-name"><?php echo $_SESSION['display_name'] ?? 'Admin'; ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <!-- Page Body -->
    <div class="page-body">

        <!-- Stats Banner -->
        <div class="stat-banner">
            <div class="stat-mini teal">
                <div class="stat-mini-icon"><i class="fas fa-users"></i></div>
                <div class="stat-mini-info">
                    <h4 id="totalCount"><?php echo $totalPatients; ?></h4>
                    <p>Total Patients</p>
                </div>
            </div>
            <div class="stat-mini sky">
                <div class="stat-mini-icon"><i class="fas fa-mars"></i></div>
                <div class="stat-mini-info">
                    <h4><?php echo count(array_filter($patients, fn($p) => strtolower($p['gender']) === 'male')); ?></h4>
                    <p>Male Patients</p>
                </div>
            </div>
            <div class="stat-mini violet">
                <div class="stat-mini-icon"><i class="fas fa-venus"></i></div>
                <div class="stat-mini-info">
                    <h4><?php echo count(array_filter($patients, fn($p) => strtolower($p['gender']) === 'female')); ?></h4>
                    <p>Female Patients</p>
                </div>
            </div>
        </div>

        <!-- Register New Patient -->
        <div class="pms-card">
            <div class="pms-card-header">
                <div class="pms-card-header-left">
                    <div class="pms-card-header-icon" style="background:rgba(0,212,170,0.1);color:#00b891">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h3>Register New Patient</h3>
                        <p>Fill in the details below to add a new patient record</p>
                    </div>
                </div>
            </div>
            <div class="pms-card-body">
                <form method="post" id="patientForm">
                    <div class="form-grid-3" style="margin-bottom:20px">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <div class="input-with-icon">
                                <input type="text" name="patient_name" required class="form-control-pms" placeholder="e.g. Juan Dela Cruz">
                                <i class="fas fa-user input-icon"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ID Number</label>
                            <div class="input-with-icon">
                                <input type="text" name="cnic" required class="form-control-pms" placeholder="e.g. 12-3456789-0">
                                <i class="fas fa-id-card input-icon"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <div class="input-with-icon">
                                <input type="text" name="phone_number" required class="form-control-pms" placeholder="09xxxxxxxxx">
                                <i class="fas fa-phone input-icon"></i>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <div class="input-with-icon">
                                <input type="text" name="address" required class="form-control-pms" placeholder="Street, Barangay, City">
                                <i class="fas fa-map-marker-alt input-icon"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <div class="input-with-icon" id="date_of_birth">
                                <input type="text" name="date_of_birth" required class="form-control-pms datetimepicker-input"
                                       data-target="#date_of_birth" data-toggle="datetimepicker" autocomplete="off"
                                       placeholder="MM/DD/YYYY">
                                <i class="fas fa-calendar input-icon" data-target="#date_of_birth" data-toggle="datetimepicker" style="cursor:pointer;pointer-events:all"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select name="gender" required class="form-control-pms">
                                <option value="" disabled selected>Select gender…</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-footer">
                        <button type="reset" class="btn-ghost">
                            <i class="fas fa-rotate-left"></i> Clear
                        </button>
                        <button type="submit" name="save_Patient" class="btn-primary-pms">
                            <i class="fas fa-save"></i> Save Patient
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Patient Records Table (Age included) -->
        <div class="pms-card">
            <div class="pms-card-header">
                <div class="pms-card-header-left">
                    <div class="pms-card-header-icon" style="background:rgba(56,189,248,0.1);color:#0ea5e9">
                        <i class="fas fa-table-list"></i>
                    </div>
                    <div>
                        <h3>Patient Records</h3>
                        <p>All registered patients sorted alphabetically</p>
                    </div>
                </div>
            </div>
            <div class="pms-card-body">
                <div class="table-search-bar">
                    <div class="search-input-wrap">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="tableSearch" placeholder="Search patients…" oninput="filterTable()">
                    </div>
                    <span class="table-count-badge" id="visibleCount">
                        <i class="fas fa-users" style="margin-right:5px"></i><?php echo $totalPatients; ?> records
                    </span>
                </div>

                <div class="pms-table-wrap">
                    <table class="pms-table" id="patientsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Patient Name</th>
                                <th>Address</th>
                                <th>ID Number</th>
                                <th>Birth Date</th>
                                <th>Age</th>
                                <th>Phone</th>
                                <th>Gender</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($patients)): ?>
                            <tr>
                                <td colspan="9">
                                    <div class="no-patients">
                                        <i class="fas fa-user-slash"></i>
                                        <p>No patients registered yet. Use the form above to add one.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($patients as $i => $row):
                                $initials  = strtoupper(substr($row['patient_name'], 0, 1));
                                $colorClass = 'av-' . ($i % 5);
                                $genderLower = strtolower($row['gender']);
                                $genderClass = in_array($genderLower, ['male','female']) ? $genderLower : 'other';
                                $genderIcon  = $genderLower === 'male' ? 'fa-mars' : ($genderLower === 'female' ? 'fa-venus' : 'fa-genderless');
                                $ageDisplay = $row['age'] ?? 'N/A';
                            ?>
                            <tr>
                                <td><span class="row-serial"><?php echo $i + 1; ?></span></td>
                                <td>
                                    <div class="patient-name-cell">
                                        <div class="patient-avatar-sm <?php echo $colorClass; ?>"><?php echo $initials; ?></div>
                                        <span class="patient-name-text"><?php echo htmlspecialchars($row['patient_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                <td style="font-family:monospace;font-size:0.82rem"><?php echo htmlspecialchars($row['cnic']); ?></td>
                                <td><?php echo $row['date_of_birth']; ?></td>
                                <td><strong><?php echo $ageDisplay; ?></strong></td>
                                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                <td>
                                    <span class="gender-badge <?php echo $genderClass; ?>">
                                        <i class="fas <?php echo $genderIcon; ?>"></i>
                                        <?php echo htmlspecialchars($row['gender']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="update_patient.php?id=<?php echo $row['id']; ?>" class="btn-edit">
                                        <i class="fas fa-pen"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <span id="footerCount">Showing <strong><?php echo $totalPatients; ?></strong> of <strong><?php echo $totalPatients; ?></strong> patients</span>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js"></script>

<script>
    // ── Sidebar Toggle ──
    const menuToggle = document.getElementById('menuToggle');
    const sidebar    = document.getElementById('sidebar');
    const overlay    = document.getElementById('sidebarOverlay');

    menuToggle.addEventListener('click', () => {
        const isOpen = sidebar.classList.contains('show');
        sidebar.classList.toggle('show', !isOpen);
        overlay.classList.toggle('show', !isOpen);
        menuToggle.classList.toggle('open', !isOpen);
    });

    overlay.addEventListener('click', () => {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        menuToggle.classList.remove('open');
    });

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

    // ── Date Picker ──
    $(document).ready(function () {
        $('#date_of_birth').datetimepicker({ format: 'L' });
    });

    // ── Live Table Search ──
    function filterTable() {
        const q     = document.getElementById('tableSearch').value.toLowerCase();
        const rows  = document.querySelectorAll('#patientsTable tbody tr');
        let visible = 0;

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const show = text.includes(q);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        const total = <?php echo $totalPatients; ?>;
        document.getElementById('visibleCount').innerHTML =
            '<i class="fas fa-users" style="margin-right:5px"></i>' + visible + ' records';
        document.getElementById('footerCount').innerHTML =
            'Showing <strong>' + visible + '</strong> of <strong>' + total + '</strong> patients';
    }
</script>
</body>
</html>