<?php 
// new_prescription.php
include './config/connection.php';
include './common_service/common_functions.php';

// Start session only once at the beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!(isset($_SESSION['user_id']))) {
    header("location:index.php");
    exit;
}

$message = '';

if(isset($_POST['submit'])) {

  $patientId = $_POST['patient'];
  $visitDate = $_POST['visit_date'];
  $nextVisitDate = $_POST['next_visit_date'];
  $bp = $_POST['bp'];
  $weight = $_POST['weight'];
  $disease = $_POST['disease'];

  $medicineDetailIds = $_POST['medicineDetailIds'] ?? [];
  $quantities = $_POST['quantities'] ?? [];
  $dosages = $_POST['dosages'] ?? [];

  // VALIDATION: Check if at least one medicine is added
  if(empty($medicineDetailIds) || count($medicineDetailIds) == 0) {
      $error_message = "Please add at least one medicine to the prescription before saving.";
      // Store error in session to display on page (session already started)
      $_SESSION['prescription_error'] = $error_message;
      header("location:new_prescription.php");
      exit;
  }

  $visitDateArr = explode("/", $visitDate);
  $visitDate = $visitDateArr[2].'-'.$visitDateArr[0].'-'.$visitDateArr[1];

  if($nextVisitDate != '') {
    $nextVisitDateArr = explode("/", $nextVisitDate);
    $nextVisitDate = $nextVisitDateArr[2].'-'.$nextVisitDateArr[0].'-'.$nextVisitDateArr[1];
  }

  try {
    $con->beginTransaction();

    $queryVisit = "INSERT INTO `patient_visits`(`visit_date`, `next_visit_date`, `bp`, `weight`, `disease`, `patient_id`) 
                   VALUES(:visit_date, :next_visit_date, :bp, :weight, :disease, :patient_id);";
    $stmtVisit = $con->prepare($queryVisit);
    $stmtVisit->execute([
        ':visit_date' => $visitDate,
        ':next_visit_date' => $nextVisitDate ?: null,
        ':bp' => $bp,
        ':weight' => $weight,
        ':disease' => $disease,
        ':patient_id' => $patientId
    ]);

    $lastInsertId = $con->lastInsertId();
    $size = count($medicineDetailIds);

    if($size > 0) {
      for($i = 0; $i < $size; $i++) {
        $curMedicineDetailId = $medicineDetailIds[$i];
        $curQuantity = $quantities[$i];
        $curDosage = $dosages[$i];

        $qeuryMedicationHistory = "INSERT INTO `patient_medication_history`(`patient_visit_id`, `medicine_details_id`, `quantity`, `dosage`)
                                    VALUES(:visit_id, :medicine_detail_id, :quantity, :dosage);";
        $stmtDetails = $con->prepare($qeuryMedicationHistory);
        $stmtDetails->execute([
            ':visit_id' => $lastInsertId,
            ':medicine_detail_id' => $curMedicineDetailId,
            ':quantity' => $curQuantity,
            ':dosage' => $curDosage
        ]);
      }
    }

    $con->commit();
    $message = 'Patient Medication stored successfully.';
    header("location:congratulation.php?goto_page=new_prescription.php&message=$message");
    exit;

  } catch(PDOException $ex) {
    $con->rollback();
    $error_message = "Database Error: " . $ex->getMessage();
    // Session already started, just store the error
    $_SESSION['prescription_error'] = $error_message;
    header("location:new_prescription.php");
    exit;
  }
}

$patients = getPatients($con);
$medicines = getMedicines($con);

// Get message from URL if returning from congratulation page
$msg = $_GET['message'] ?? '';

// Check for error message from session (session already started)
$error_msg = $_SESSION['prescription_error'] ?? '';
unset($_SESSION['prescription_error']); // Clear after reading
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aroroy PIS — New Prescription</title>
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

        /* New Prescription specific styles */
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
        }

        .form-group input, .form-group select {
            padding: 12px 16px;
            border: 1.5px solid rgba(0,0,0,0.08);
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.85rem;
            transition: all 0.2s;
            background: white;
        }

        .form-group input:focus, .form-group select:focus {
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
        
        .medication-section {
            background: var(--off-white);
            margin: 0 28px 24px 28px;
            padding: 24px;
            border-radius: 16px;
        }

        .medication-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .btn-teal {
            background: linear-gradient(135deg, var(--teal), #00a085);
            border: none;
            color: white;
            padding: 12px 24px;
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

        .btn-teal:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,212,170,0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #00a085, #008f6e);
            border: none;
            color: white;
            padding: 14px 36px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.25s;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,160,133,0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b8a, #e5484d);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-danger:hover {
            transform: scale(1.05);
        }

        .table-wrapper {
            padding: 0 28px 20px 28px;
            overflow-x: auto;
        }

        .med-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .med-table th {
            text-align: left;
            padding: 14px 16px;
            background: var(--navy);
            color: white;
            font-weight: 600;
        }

        .med-table th.text-center {
            text-align: center;
        }

        .med-table td {
            padding: 12px 16px;
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

        .action-buttons {
            padding: 20px 28px 28px 28px;
            text-align: right;
            border-top: 1px solid rgba(0,0,0,0.05);
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

        /* ============================================
           MODAL STYLES
        ============================================ */
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

        .modal-btn-danger {
            background: linear-gradient(135deg, #ff6b8a, #e5484d);
            color: white;
        }

        .modal-btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(229, 72, 77, 0.3);
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
            .form-grid { grid-template-columns: 1fr; }
            .medication-grid { grid-template-columns: 1fr; }
            .table-wrapper { padding: 0 16px 16px 16px; }
            .med-table th, .med-table td { padding: 8px 10px; font-size: 0.75rem; }
            .header-profile-name { display: none; }
            .alert-message { margin: 0 16px 16px 16px; }
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

                <li class="nav-item has-sub open">
                    <a href="#" class="nav-link active">
                        <span class="nav-icon"><i class="fas fa-user-injured"></i></span>
                        <span>Patients</span>
                    </a>
                    <ul class="sub-menu">
                        <li><a href="new_prescription.php" class="nav-link active">New Prescription</a></li>
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

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="top-header">
        <div class="page-breadcrumb">
            <h1>New Prescription</h1>
            <p>Create a new patient prescription and visit record</p>
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
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-prescription-bottle-medical"></i> Prescription Details
                </div>
            </div>
            
            <!-- Display error message if exists -->
            <?php if($error_msg): ?>
            <div class="alert-message alert-error" id="errorAlert">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo htmlspecialchars($error_msg); ?></span>
                <i class="fas fa-times close-alert" onclick="this.parentElement.remove()"></i>
            </div>
            <?php endif; ?>
            
            <!-- Display success message if exists -->
            <?php if($msg): ?>
            <div class="alert-message alert-success" id="successAlert">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($msg); ?></span>
                <i class="fas fa-times close-alert" onclick="this.parentElement.remove()"></i>
            </div>
            <?php endif; ?>

            <form method="post" id="prescriptionForm">
                <div class="form-section">
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Select Patient <span style="color:#e5484d">*</span></label>
                            <select id="patient" name="patient" class="form-control" required><?php echo $patients;?></select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-day"></i> Visit Date <span style="color:#e5484d">*</span></label>
                            <div class="input-group date" id="visit_date" data-target-input="nearest">
                                <input type="text" class="form-control datetimepicker-input" data-target="#visit_date" name="visit_date" required data-toggle="datetimepicker"/>
                                <div class="input-group-append" data-target="#visit_date" data-toggle="datetimepicker">
                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-week"></i> Next Visit Date</label>
                            <div class="input-group date" id="next_visit_date" data-target-input="nearest">
                                <input type="text" class="form-control datetimepicker-input" data-target="#next_visit_date" name="next_visit_date" data-toggle="datetimepicker"/>
                                <div class="input-group-append" data-target="#next_visit_date" data-toggle="datetimepicker">
                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-heartbeat"></i> BP <span style="color:#e5484d">*</span></label>
                            <input name="bp" class="form-control" required placeholder="e.g., 120/80" />
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-weight-scale"></i> Weight (kg) <span style="color:#e5484d">*</span></label>
                            <input name="weight" class="form-control" required placeholder="e.g., 65" />
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-stethoscope"></i> Diagnosis <span style="color:#e5484d">*</span></label>
                            <input name="disease" class="form-control" required placeholder="Enter diagnosis" />
                        </div>
                    </div>
                </div>

                <div class="medication-section">
                    <div class="medication-grid">
                        <div class="form-group">
                            <label><i class="fas fa-capsules"></i> Medicine <span style="color:#e5484d">*</span></label>
                            <select id="medicine" class="form-control"><?php echo $medicines;?></select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-box"></i> Packing <span style="color:#e5484d">*</span></label>
                            <select id="packing" class="form-control"></select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-hashtag"></i> Quantity <span style="color:#e5484d">*</span></label>
                            <input id="quantity" type="number" class="form-control" placeholder="QTY" />
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Dosage <span style="color:#e5484d">*</span></label>
                            <input id="dosage" class="form-control" placeholder="e.g., 1x per day" />
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button id="add_to_list" type="button" class="btn-teal"><i class="fas fa-plus-circle"></i> Add Medicine</button>
                        </div>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="med-table" id="medication_list">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">#</th>
                                <th width="40%">Medicine Name</th>
                                <th width="15%">Packing</th>
                                <th width="10%" class="text-center">QTY</th>
                                <th width="20%">Dosage</th>
                                <th width="10%" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="current_medicines_list">
                            <tr id="emptyMedicineRow">
                                <td colspan="6" class="text-center" style="padding: 40px; color: var(--text-muted);">
                                    <i class="fas fa-capsules" style="font-size: 2rem; opacity: 0.5; display: block; margin-bottom: 10px;"></i>
                                    No medicines added yet. Please add at least one medicine.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="action-buttons">
                    <button type="submit" name="submit" class="btn-success" id="savePrescriptionBtn"><i class="fas fa-save"></i> Save Prescription</button>
                </div>
            </form>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/js/tempusdominus-bootstrap-4.min.js"></script>

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

    var serial = 1;

    function updateEmptyMessage() {
        var medicineRows = $('#current_medicines_list tr:not(#emptyMedicineRow)').length;
        if(medicineRows === 0) {
            if($('#emptyMedicineRow').length === 0) {
                $('#current_medicines_list').html(`
                    <tr id="emptyMedicineRow">
                        <td colspan="6" class="text-center" style="padding: 40px; color: var(--text-muted);">
                            <i class="fas fa-capsules" style="font-size: 2rem; opacity: 0.5; display: block; margin-bottom: 10px;"></i>
                            No medicines added yet. Please add at least one medicine.
                        </td>
                    </table>
                `);
            }
        } else {
            $('#emptyMedicineRow').remove();
        }
    }

    function removeRow(btn) {
        $(btn).closest('tr').remove();
        serial = 1;
        $('.serial_num').each(function() {
            $(this).text(serial++);
        });
        updateEmptyMessage();
    }

    $(document).ready(function() {
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert-message').fadeOut(500, function() { $(this).remove(); });
        }, 5000);

        // Init Datepicker
        $('#visit_date, #next_visit_date').datetimepicker({ format: 'L' });

        // Load Packings
        $("#medicine").change(function() {
            var medicineId = $(this).val();
            if(medicineId !== '') {
                $.ajax({
                    url: "ajax/get_packings.php",
                    type: 'GET', 
                    data: { 'medicine_id': medicineId },
                    success: function (data) { 
                        $("#packing").html(data); 
                    }
                });
            }
        });

        // ADD TO LIST FUNCTION with custom modal
        $("#add_to_list").click(function() {
            var medicineName = $("#medicine option:selected").text();
            var medicineDetailId = $("#packing").val();
            var packing = $("#packing option:selected").text();
            var quantity = $("#quantity").val();
            var dosage = $("#dosage").val();

            if(medicineDetailId === '' || medicineDetailId === null) {
                showCustomAlert("Please select a valid medicine packing.", "Missing Information", "warning");
                return;
            }
            if(quantity === '' || quantity <= 0) {
                showCustomAlert("Please enter a valid quantity.", "Invalid Quantity", "warning");
                return;
            }
            if(dosage === '') {
                showCustomAlert("Please enter the dosage information.", "Missing Dosage", "warning");
                return;
            }

            $('#emptyMedicineRow').remove();
            
            var rowHtml = `<tr>
                <td class="text-center serial_num">${serial}</td>
                <td>${medicineName}</td>
                <td>${packing}</td>
                <td class="text-center">${quantity}</td>
                <td>
                    ${dosage}
                    <input type="hidden" name="medicineDetailIds[]" value="${medicineDetailId}" />
                    <input type="hidden" name="quantities[]" value="${quantity}" />
                    <input type="hidden" name="dosages[]" value="${dosage}" />
                </td>
                <td class="text-center">
                    <button type="button" class="btn-danger" onclick="removeRow(this)">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            </tr>`;

            $("#current_medicines_list").append(rowHtml);
            serial++;
            $("#quantity").val('');
            $("#dosage").val('');
        });
        
        // Form submission validation with custom modal
        $('#prescriptionForm').on('submit', function(e) {
            var medicineRows = $('#current_medicines_list tr:not(#emptyMedicineRow)').length;
            if(medicineRows === 0) {
                e.preventDefault();
                showCustomAlert(
                    'Please add at least one medicine to the prescription before saving.', 
                    'Cannot Save Prescription', 
                    'error'
                );
                return false;
            }
            return true;
        });
    });
</script>
</body>
</html>