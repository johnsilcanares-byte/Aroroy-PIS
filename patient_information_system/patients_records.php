<?php
// patients_records.php – Polished & Consistent with sample design
include './config/connection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("location:index.php");
    exit;
}

// Pagination & Filters
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$gender_filter = isset($_GET['gender']) ? trim($_GET['gender']) : '';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(patient_name LIKE :search OR cnic LIKE :search OR phone_number LIKE :search OR address LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($gender_filter) && in_array($gender_filter, ['Male', 'Female'])) {
    $where_conditions[] = "gender = :gender";
    $params[':gender'] = $gender_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Total records count
try {
    $count_query = "SELECT COUNT(*) as total FROM patients $where_clause";
    $count_stmt = $con->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $records_per_page);
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    $total_records = 0;
    $total_pages = 0;
}

// Fetch patients with pagination & safe date handling
try {
    $query = "SELECT `id`, `patient_name`, `address`, `cnic`, 
              CASE 
                WHEN `date_of_birth` = '0000-00-00' OR `date_of_birth` IS NULL THEN NULL
                ELSE DATE_FORMAT(`date_of_birth`, '%d %b %Y')
              END as `date_of_birth`,
              `phone_number`, `gender`,
              CASE 
                WHEN `date_of_birth` = '0000-00-00' OR `date_of_birth` IS NULL THEN 'N/A'
                ELSE TIMESTAMPDIFF(YEAR, `date_of_birth`, CURDATE())
              END as age
              FROM `patients` 
              $where_clause
              ORDER BY `patient_name` ASC 
              LIMIT :offset, :records_per_page";

    $stmt = $con->prepare($query);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    $patients = [];
}

// Statistics
try {
    $stats_query = "SELECT 
                    COUNT(*) as total_patients,
                    SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as male_count,
                    SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female_count
                    FROM patients";
    $stats_stmt = $con->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    $stats = ['total_patients' => 0, 'male_count' => 0, 'female_count' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aroroy PIS — All Patient Records</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--off-white);
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed; left: 0; top: 0; width: var(--sidebar-width);
            height: 100%; background: var(--navy); z-index: 1000;
            display: flex; flex-direction: column;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar::before { content: ''; position: absolute; width: 350px; height: 350px; background: radial-gradient(circle, rgba(24,222,182,0.45) 0%,transparent 70%); top: -80px; left: -80px; border-radius: 50%; pointer-events: none; }
        .sidebar::after { content: ''; position: absolute; width: 250px; height: 250px; background: radial-gradient(circle, rgba(159,147,192,0.18) 0%,transparent 70%); bottom: 80px; right: -60px; border-radius: 50%; pointer-events: none; }
        .sidebar-scroll { overflow-y: auto; flex: 1; position: relative; z-index: 1; }
        .sidebar-scroll::-webkit-scrollbar { width: 3px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(0,212,170,0.3); border-radius: 10px; }

        .sidebar-logo { padding: 28px 24px 20px; border-bottom: 1px solid var(--border); }
        .logo-mark { display: flex; align-items: center; gap: 12px; }
        .logo-icon { width: 50px; height: 50px; background: linear-gradient(135deg, var(--teal), #00a085); border-radius: 40%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: white; box-shadow: 0 8px 20px rgba(0,212,170,0.3); flex-shrink: 0; }
        .logo-icon img { width: 100%; height: 100%; object-fit: cover; border-radius: 40%; }
        .logo-text h3 { font-size: 1.15rem; font-weight: 800; color: white; letter-spacing: -0.3px; }

        .sidebar-user { padding: 20px 24px; border-bottom: 1px solid var(--border); }
        .user-card { display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: var(--navy-light); border-radius: 14px; border: 1px solid var(--border); }
        .user-avatar { width: 42px; height: 42px; border-radius: 50%; overflow: hidden; border: 2px solid var(--teal); flex-shrink: 0; }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .user-card-info h4 { font-size: 0.85rem; font-weight: 600; color: white; line-height: 1.2; }
        .user-card-info p { font-size: 0.7rem; color: var(--text-muted); margin-top: 2px; }
        .user-status { width: 8px; height: 8px; background: var(--teal); border-radius: 50%; margin-left: auto; box-shadow: 0 0 8px var(--teal); animation: pulse-dot 2s infinite; }
        @keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.6;transform:scale(0.85)} }

        .nav-section { padding: 16px 16px 0; }
        .nav-label { font-size: 0.65rem; font-weight: 600; color: rgba(255,255,255,0.25); letter-spacing: 1.8px; text-transform: uppercase; padding: 0 8px 8px; }
        .nav-item { list-style: none; margin: 2px 0; }
        .nav-link { display: flex; align-items: center; padding: 10px 12px; color: rgba(255,255,255,0.55); text-decoration: none; border-radius: 10px; gap: 10px; font-size: 0.88rem; font-weight: 500; transition: all 0.25s; }
        .nav-link .nav-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem; background: rgba(255,255,255,0.05); flex-shrink: 0; transition: all 0.25s; }
        .nav-link:hover { background: rgba(255,255,255,0.06); color: white; }
        .nav-link:hover .nav-icon { background: rgba(0,212,170,0.15); color: var(--teal); }
        .nav-link.active { background: linear-gradient(90deg, rgba(0,212,170,0.15), rgba(0,212,170,0.05)); color: white; border: 1px solid rgba(0,212,170,0.2); }
        .nav-link.active .nav-icon { background: var(--teal); color: var(--navy); }
        .nav-item.has-sub > .nav-link::after { content: '\f078'; font-family: 'Font Awesome 6 Free'; font-weight: 900; font-size: 0.6rem; margin-left: auto; transition: transform 0.3s; opacity: 0.5; }
        .nav-item.has-sub.open > .nav-link::after { transform: rotate(180deg); }
        .sub-menu { overflow: hidden; max-height: 0; transition: max-height 0.35s; }
        .nav-item.has-sub.open .sub-menu { max-height: 200px; }
        .sub-menu li { list-style: none; }
        .sub-menu .nav-link { padding: 8px 12px 8px 54px; font-size: 0.82rem; color: rgba(255,255,255,0.45); }
        .sub-menu .nav-link:hover { color: var(--teal); background: rgba(0,212,170,0.06); }

        .sidebar-bottom { padding: 16px; border-top: 1px solid var(--border); }
        .logout-btn { display: flex; align-items: center; gap: 10px; padding: 11px 14px; background: rgba(255,107,138,0.08); border-radius: 10px; color: rgba(255,107,138,0.8); text-decoration: none; font-size: 0.88rem; font-weight: 500; transition: all 0.25s; border: 1px solid rgba(255,107,138,0.12); }
        .logout-btn:hover { background: rgba(255,107,138,0.15); color: var(--rose); transform: translateX(3px); }

        /* MAIN CONTENT */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; transition: margin-left 0.4s; }
        .top-header { background: rgba(248,250,253,0.92); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); padding: 0 36px; height: 70px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
        .page-breadcrumb h1 { font-size: 1.45rem; font-weight: 800; color: var(--text-primary); }
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

        /* PAGE BODY */
        .page-body { padding: 32px 36px; display: flex; flex-direction: column; gap: 28px; }

        /* STAT BANNER */
        .stat-banner { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        .stat-mini { background: white; border-radius: 18px; padding: 20px 22px; display: flex; align-items: center; gap: 16px; box-shadow: var(--card-shadow); border: 1.5px solid transparent; transition: all 0.3s; animation: card-in 0.6s cubic-bezier(0.4,0,0.2,1) both; }
        .stat-mini:hover { transform: translateY(-4px); box-shadow: var(--card-hover-shadow); }
        .stat-mini-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
        .teal .stat-mini-icon { background: rgba(0,212,170,0.1); color: #00b891; }
        .sky .stat-mini-icon { background: rgba(56,189,248,0.1); color: #0ea5e9; }
        .violet .stat-mini-icon { background: rgba(167,139,250,0.1); color: #8b5cf6; }
        .stat-mini-info h4 { font-size: 1.6rem; font-weight: 900; color: var(--text-primary); }
        .teal .stat-mini-info h4 { color: #00996e; }
        .sky .stat-mini-info h4 { color: #0284c7; }
        .violet .stat-mini-info h4 { color: #7c3aed; }
        .stat-mini-info p { font-size: 0.77rem; color: var(--text-secondary); font-weight: 500; }
        @keyframes card-in { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }

        /* CARD */
        .pms-card { background: white; border-radius: 20px; box-shadow: var(--card-shadow); border: 1.5px solid rgba(0,0,0,0.04); overflow: hidden; animation: card-in 0.6s cubic-bezier(0.4,0,0.2,1) both; }
        .pms-card-header { padding: 20px 28px; border-bottom: 1px solid rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: space-between; }
        .pms-card-header-left { display: flex; align-items: center; gap: 12px; }
        .pms-card-header-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
        .pms-card-header h3 { font-size: 0.98rem; font-weight: 700; color: var(--text-primary); }
        .pms-card-header p { font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px; }
        .pms-card-body { padding: 28px; }

        /* FILTER BAR */
        .filter-bar { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; align-items: flex-end; }
        .filter-group label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; color: var(--text-secondary); display: block; margin-bottom: 6px; }
        .filter-group input, .filter-group select { width: 100%; padding: 10px 14px; border: 1.5px solid rgba(0,0,0,0.08); border-radius: 11px; font-family: 'Outfit', sans-serif; font-size: 0.85rem; background: white; transition: all 0.25s; }
        .filter-group input:focus, .filter-group select:focus { border-color: var(--teal); box-shadow: 0 0 0 4px rgba(0,212,170,0.08); outline: none; }
        .btn-filter, .btn-reset { padding: 10px 20px; border-radius: 11px; font-weight: 600; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-family: 'Outfit', sans-serif; text-decoration: none; }
        .btn-filter { background: linear-gradient(135deg, var(--navy), var(--navy-mid)); color: white; border: none; }
        .btn-filter:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(11,20,38,0.25); }
        .btn-reset { background: var(--off-white); color: var(--text-secondary); border: 1.5px solid rgba(0,0,0,0.08); margin-left: 6px; }
        .btn-reset:hover { border-color: var(--teal); color: var(--teal); transform: translateY(-2px); }

        /* TABLE */
        .table-search-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; gap: 14px; }
        .search-input-wrap { position: relative; flex: 1; min-width: 220px; max-width: 360px; }
        .search-input-wrap input { width: 100%; padding: 10px 16px 10px 40px; border: 1.5px solid rgba(0,0,0,0.08); border-radius: 11px; font-family: 'Outfit', sans-serif; font-size: 0.85rem; background: var(--off-white); transition: all 0.25s; }
        .search-input-wrap input:focus { border-color: var(--teal); background: white; box-shadow: 0 0 0 4px rgba(0,212,170,0.08); outline: none; }
        .search-input-wrap .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .table-count-badge { font-size: 0.75rem; font-weight: 600; padding: 5px 13px; border-radius: 20px; background: rgba(0,212,170,0.1); color: #00996e; white-space: nowrap; }

        .pms-table-wrap { overflow-x: auto; border-radius: 14px; border: 1.5px solid rgba(0,0,0,0.05); }
        .pms-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .pms-table thead tr { background: var(--navy); }
        .pms-table thead th { padding: 14px 18px; font-size: 0.72rem; font-weight: 700; color: rgba(255,255,255,0.65); text-transform: uppercase; letter-spacing: 0.8px; }
        .pms-table tbody td { padding: 14px 18px; color: var(--text-primary); border-bottom: 1px solid rgba(0,0,0,0.04); }
        .pms-table tbody tr:last-child td { border-bottom: none; }
        .pms-table tbody tr:hover { background: rgba(0,212,170,0.03); }

        .patient-name-cell { display: flex; align-items: center; gap: 10px; }
        .patient-avatar-sm { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; color: white; flex-shrink: 0; }
        .patient-name-text { font-weight: 600; }
        .row-serial { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; }

        .gender-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 0.73rem; font-weight: 600; }
        .gender-badge.male { background: rgba(56,189,248,0.1); color: #0284c7; }
        .gender-badge.female { background: rgba(255,107,138,0.1); color: #e0466a; }

        .btn-edit { padding: 7px 14px; border-radius: 9px; background: rgba(0,212,170,0.08); color: #00996e; font-size: 0.78rem; font-weight: 700; text-decoration: none; border: 1.5px solid rgba(0,212,170,0.15); transition: all 0.25s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-edit:hover { background: rgba(0,212,170,0.15); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,212,170,0.2); }

        .pagination { display: flex; gap: 4px; padding: 16px 0 0; align-items: center; justify-content: center; }
        .pagination a, .pagination span { min-width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.82rem; font-weight: 600; text-decoration: none; color: var(--text-secondary); border: 1px solid transparent; transition: all 0.2s; }
        .pagination a:hover { background: var(--teal-glow); color: var(--teal); border-color: var(--teal); }
        .pagination .active { background: linear-gradient(135deg, var(--navy), var(--navy-mid)); color: white; }

        .table-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 16px; font-size: 0.78rem; color: var(--text-secondary); }

        /* MODAL */
        .modal { display: none; position: fixed; inset: 0; background: rgba(11,20,38,0.6); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: 24px; width: 90%; max-width: 500px; box-shadow: 0 30px 60px rgba(11,20,38,0.3); animation: modalSlide 0.3s; }
        @keyframes modalSlide { from { opacity:0; transform:scale(0.95) } to { opacity:1; transform:scale(1) } }
        .modal-header { padding: 20px 24px 0; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { font-size: 1.2rem; font-weight: 700; }
        .close-modal { width: 28px; height: 28px; border-radius: 8px; border: none; background: var(--off-white); cursor: pointer; font-size: 0.9rem; }
        .close-modal:hover { background: rgba(255,107,138,0.1); color: var(--rose); }
        .modal-body { padding: 20px 24px; }
        .detail-row { display: flex; padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .detail-label { font-weight: 600; width: 120px; font-size: 0.85rem; }
        .detail-value { font-size: 0.85rem; color: var(--text-secondary); }

        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(11,20,38,0.5); backdrop-filter: blur(4px); z-index: 999; }
        .footer { padding: 20px 36px; text-align: center; font-size: 0.77rem; color: var(--text-muted); border-top: 1px solid rgba(0,0,0,0.05); }

        .av-0 { background: linear-gradient(135deg, #00d4aa, #00a085); }
        .av-1 { background: linear-gradient(135deg, #38bdf8, #0284c7); }
        .av-2 { background: linear-gradient(135deg, #a78bfa, #7c3aed); }
        .av-3 { background: linear-gradient(135deg, #ffb340, #e69500); }
        .av-4 { background: linear-gradient(135deg, #ff6b8a, #e0466a); }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); z-index: 1001; }
            .sidebar.show { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .main-content { margin-left: 0; }
            .menu-toggle { display: flex; }
            .top-header { padding: 0 20px; }
            .page-body { padding: 20px; }
            .stat-banner { grid-template-columns: 1fr 1fr; }
            .header-profile-name { display: none; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-scroll">
        <div class="sidebar-logo">
            <div class="logo-mark">
                <div class="logo-icon"><img src="images/aroroy_logo.png" alt="Aroroy Logo"></div>
                <div class="logo-text"><h3>Aroroy PIS</h3></div>
            </div>
        </div>
        <div class="sidebar-user">
            <div class="user-card">
                <div class="user-avatar"><img src="user_images/<?php echo $_SESSION['profile_picture'] ?? 'default.jpg'; ?>" alt="User"></div>
                <div class="user-card-info">
                    <h4><?php echo $_SESSION['display_name'] ?? 'Admin'; ?></h4>
                    <p>System Administrator</p>
                </div>
                <div class="user-status"></div>
            </div>
        </div>
        <div class="nav-section">
            <div class="nav-label">Main</div>
            <ul style="list-style:none">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><span class="nav-icon"><i class="fas fa-squares-four"></i></span><span>Dashboard</span></a></li>
                <li class="nav-item has-sub open">
                    <a href="#" class="nav-link active"><span class="nav-icon"><i class="fas fa-user-injured"></i></span><span>Patients</span></a>
                    <ul class="sub-menu">
                        <li><a href="new_prescription.php" class="nav-link">New Prescription</a></li>
                        <li><a href="patients.php" class="nav-link">Add Patients</a></li>
                        <li><a href="patient_history.php" class="nav-link">Patient History</a></li>
                        <li><a href="patients_records.php" class="nav-link active">View All Patients</a></li>
                    </ul>
                </li>
                <li class="nav-item has-sub"><a href="#" class="nav-link"><span class="nav-icon"><i class="fas fa-pills"></i></span><span>Medicines</span></a><ul class="sub-menu"><li><a href="medicines.php" class="nav-link">Add Medicine</a></li><li><a href="medicine_details.php" class="nav-link">Medicine Details</a></li></ul></li>
                <li class="nav-item has-sub"><a href="#" class="nav-link"><span class="nav-icon"><i class="fas fa-book-medical"></i></span><span>Appointments</span></a><ul class="sub-menu"><li><a href="appointments.php" class="nav-link">Appointments</a></li></ul></li>
                <li class="nav-item"><a href="reports.php" class="nav-link"><span class="nav-icon"><i class="fas fa-chart-bar"></i></span><span>Reports</span></a></li>
            </ul>
        </div>
    </div>
    <div class="sidebar-bottom">
        <a href="logout.php" class="logout-btn"><i class="fas fa-arrow-right-from-bracket"></i><span>Sign Out</span></a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="top-header">
        <div class="page-breadcrumb">
            <h1>All Patient Records</h1>
            <p>View and manage all registered patients from the database</p>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <div class="header-profile">
                <div class="header-avatar"><i class="fas fa-user"></i></div>
                <span class="header-profile-name"><?php echo $_SESSION['display_name'] ?? 'Admin'; ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <button class="menu-toggle" id="menuToggle"><span></span><span></span><span></span></button>
        </div>
    </div>

    <div class="page-body">
        <!-- Stats -->
        <div class="stat-banner">
            <div class="stat-mini teal">
                <div class="stat-mini-icon"><i class="fas fa-users"></i></div>
                <div class="stat-mini-info"><h4><?php echo number_format($stats['total_patients']); ?></h4><p>Total Patients</p></div>
            </div>
            <div class="stat-mini sky">
                <div class="stat-mini-icon"><i class="fas fa-mars"></i></div>
                <div class="stat-mini-info"><h4><?php echo number_format($stats['male_count']); ?></h4><p>Male Patients</p></div>
            </div>
            <div class="stat-mini violet">
                <div class="stat-mini-icon"><i class="fas fa-venus"></i></div>
                <div class="stat-mini-info"><h4><?php echo number_format($stats['female_count']); ?></h4><p>Female Patients</p></div>
            </div>
        </div>

        <!-- Patient Records Card -->
        <div class="pms-card">
            <div class="pms-card-header">
                <div class="pms-card-header-left">
                    <div class="pms-card-header-icon" style="background:rgba(56,189,248,0.1);color:#0ea5e9">
                        <i class="fas fa-table-list"></i>
                    </div>
                    <div><h3>Patient Records</h3><p>All registered patients sorted alphabetically</p></div>
                </div>
            </div>
            <div class="pms-card-body">
                <!-- Filter Form -->
                <form method="GET" class="filter-bar">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Name, CNIC, Phone…" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">All</option>
                            <option value="Male" <?php echo $gender_filter == 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $gender_filter == 'Female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Apply</button>
                        <a href="patients_records.php" class="btn-reset"><i class="fas fa-undo"></i> Reset</a>
                    </div>
                </form>

                <!-- Live Search & Table -->
                <div class="table-search-bar">
                    <div class="search-input-wrap">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="tableSearch" placeholder="Search within results…" oninput="filterTable()">
                    </div>
                    <span class="table-count-badge" id="visibleCount">
                        <i class="fas fa-users" style="margin-right:5px"></i><?php echo $total_records; ?> records
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
                            <?php if (empty($patients)): ?>
                                <tr><td colspan="9"><div class="no-results"><i class="fas fa-user-slash"></i><p>No patient records found.</p></div></td></tr>
                            <?php else: ?>
                                <?php foreach ($patients as $i => $row):
                                    $initials = strtoupper(substr($row['patient_name'], 0, 1));
                                    $colorClass = 'av-' . ($i % 5);
                                    $genderLower = strtolower($row['gender']);
                                    $genderClass = in_array($genderLower, ['male','female']) ? $genderLower : 'other';
                                    $genderIcon = $genderLower === 'male' ? 'fa-mars' : ($genderLower === 'female' ? 'fa-venus' : 'fa-genderless');
                                    $ageDisplay = $row['age'] ?? 'N/A';
                                ?>
                                <tr>
                                    <td><span class="row-serial"><?php echo $offset + $i + 1; ?></span></td>
                                    <td>
                                        <div class="patient-name-cell">
                                            <div class="patient-avatar-sm <?php echo $colorClass; ?>"><?php echo $initials; ?></div>
                                            <span class="patient-name-text"><?php echo htmlspecialchars($row['patient_name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                                    <td style="font-family:monospace;"><?php echo htmlspecialchars($row['cnic']); ?></td>
                                    <td><?php echo $row['date_of_birth'] ?? 'Not set'; ?></td>
                                    <td><?php echo $ageDisplay; ?></td>
                                    <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                    <td><span class="gender-badge <?php echo $genderClass; ?>"><i class="fas <?php echo $genderIcon; ?>"></i> <?php echo htmlspecialchars($row['gender']); ?></span></td>
                                    <td>
                                        <a href="update_patient.php?id=<?php echo $row['id']; ?>" class="btn-edit"><i class="fas fa-pen"></i> Edit</a>
                                        <button class="btn-view" onclick='viewPatient(<?php echo (int)$row["id"]; ?>, <?php echo json_encode($row["patient_name"]); ?>, <?php echo json_encode($row["cnic"]); ?>, <?php echo json_encode($row["phone_number"]); ?>, <?php echo json_encode($row["address"]); ?>, <?php echo json_encode($row["date_of_birth"] ?? 'Not set'); ?>, <?php echo json_encode($row["gender"]); ?>, <?php echo json_encode($ageDisplay); ?>)'><i class="fas fa-eye"></i> View</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <span id="footerCount">Showing <strong><?php echo count($patients); ?></strong> of <strong><?php echo $total_records; ?></strong> patients</span>
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?><a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender_filter); ?>">&laquo; Prev</a><?php endif; ?>
                            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                <?php if ($p == $page): ?><span class="active"><?php echo $p; ?></span>
                                <?php else: ?><a href="?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender_filter); ?>"><?php echo $p; ?></a><?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?><a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender_filter); ?>">Next &raquo;</a><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> Aroroy Patient Management System &nbsp;&middot;&nbsp; All rights reserved
    </div>
</div>

<!-- View Patient Modal -->
<div id="patientModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-injured"></i> Patient Details</h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>

<script>
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    menuToggle.addEventListener('click', () => {
        const isOpen = sidebar.classList.contains('show');
        sidebar.classList.toggle('show', !isOpen);
        overlay.classList.toggle('show', !isOpen);
        menuToggle.classList.toggle('open', !isOpen);
    });
    overlay.addEventListener('click', () => { sidebar.classList.remove('show'); overlay.classList.remove('show'); menuToggle.classList.remove('open'); });

    document.querySelectorAll('.has-sub > .nav-link').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const parent = link.parentElement;
            document.querySelectorAll('.has-sub.open').forEach(el => { if (el !== parent) el.classList.remove('open'); });
            parent.classList.toggle('open');
        });
    });

    function viewPatient(id, name, cnic, phone, address, dob, gender, age) {
        const modalBody = document.getElementById('modalBody');
        modalBody.innerHTML = `
            <div class="detail-row"><div class="detail-label">Patient ID:</div><div class="detail-value">#${id}</div></div>
            <div class="detail-row"><div class="detail-label">Full Name:</div><div class="detail-value"><strong>${name}</strong></div></div>
            <div class="detail-row"><div class="detail-label">CNIC Number:</div><div class="detail-value">${cnic}</div></div>
            <div class="detail-row"><div class="detail-label">Phone Number:</div><div class="detail-value">${phone}</div></div>
            <div class="detail-row"><div class="detail-label">Address:</div><div class="detail-value">${address}</div></div>
            <div class="detail-row"><div class="detail-label">Date of Birth:</div><div class="detail-value">${dob}</div></div>
            <div class="detail-row"><div class="detail-label">Age:</div><div class="detail-value">${age}</div></div>
            <div class="detail-row"><div class="detail-label">Gender:</div><div class="detail-value">${gender}</div></div>
            <div style="margin-top:20px; padding-top:15px; border-top:1px solid #e2e8f0; text-align:center;">
                <a href="update_patient.php?id=${id}" class="btn-edit" style="padding:10px 20px;">
                    <i class="fas fa-edit"></i> Edit Patient
                </a>
            </div>`;
        document.getElementById('patientModal').style.display = 'flex';
    }

    function closeModal() { document.getElementById('patientModal').style.display = 'none'; }
    window.onclick = function(event) { if (event.target == document.getElementById('patientModal')) closeModal(); }

    function filterTable() {
        const q = document.getElementById('tableSearch').value.toLowerCase();
        const rows = document.querySelectorAll('#patientsTable tbody tr');
        let visible = 0;
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const show = text.includes(q);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const total = <?php echo $total_records; ?>;
        document.getElementById('visibleCount').innerHTML = '<i class="fas fa-users" style="margin-right:5px"></i>' + visible + ' records';
        document.getElementById('footerCount').innerHTML = 'Showing <strong>' + visible + '</strong> of <strong>' + total + '</strong> patients';
    }

    function exportToCSV() {
        const table = document.getElementById('patientsTable');
        const rows = table.querySelectorAll('tr');
        let csv = [];
        for (let row of rows) {
            const cells = row.querySelectorAll('th, td');
            let rowData = [];
            for (let cell of cells) {
                if (cell.cellIndex === cells.length - 1 && row.querySelector('th') === null) break;
                let text = cell.innerText.trim().replace(/"/g, '""');
                rowData.push(`"${text}"`);
            }
            if (rowData.length) csv.push(rowData.join(','));
        }
        const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'patients_export.csv';
        a.click();
        window.URL.revokeObjectURL(url);
    }
</script>
</body>
</html>