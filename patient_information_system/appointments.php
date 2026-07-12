<?php
include './config/connection.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("location:index.php"); exit; }

// Read all session values BEFORE releasing the lock
$user_id = $_SESSION['user_id'];
$display_name = $_SESSION['display_name'] ?? 'Admin';
$profile_picture = $_SESSION['profile_picture'] ?? 'default.jpg';

// 🔓 Release the session lock so other pages don't freeze
session_write_close();

// Handle status update with optional note
if (isset($_POST['action_submit'])) {
    $id = (int)$_POST['appt_id'];
    $status = $_POST['new_status'];
    $note = trim($_POST['admin_note'] ?? '');
    $stmt = $con->prepare("UPDATE appointments SET status = ?, admin_note = ? WHERE id = ?");
    $stmt->execute([$status, $note, $id]);
    header("Location: appointments.php");
    exit;
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    $stmt = $con->prepare("DELETE FROM appointments WHERE id = ?");
    $stmt->execute([$deleteId]);
    header("Location: appointments.php");
    exit;
}

// Fetch appointments with patient names
$appts = $con->query("SELECT a.*, p.patient_name FROM appointments a JOIN patients p ON a.patient_id = p.id ORDER BY a.id DESC");
$appointments = $appts ? $appts->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments | Aroroy PIS</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ── ALL DESIGN TOKENS & STYLES FROM SAMPLE ── */
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
        .sidebar {
            position: fixed; left: 0; top: 0; width: var(--sidebar-width);
            height: 100%; background: var(--navy); z-index: 1000;
            display: flex; flex-direction: column;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden;
        }
        .sidebar::before { content: ''; position: absolute; width: 350px; height: 350px; background: radial-gradient(circle, rgba(24, 222, 182, 0.45) 0%, transparent 70%); top: -80px; left: -80px; border-radius: 50%; pointer-events: none; }
        .sidebar::after { content: ''; position: absolute; width: 250px; height: 250px; background: radial-gradient(circle, rgba(159, 147, 192, 0.18) 0%, transparent 70%); bottom: 80px; right: -60px; border-radius: 50%; pointer-events: none; }
        .sidebar-scroll { overflow-y: auto; flex: 1; position: relative; z-index: 1; }
        .sidebar-scroll::-webkit-scrollbar { width: 3px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(0,212,170,0.3); border-radius: 10px; }
        .sidebar-logo { padding: 28px 24px 20px; border-bottom: 1px solid var(--border); }
        .logo-mark { display: flex; align-items: center; gap: 12px; }
        .logo-icon { width: 50px; height: 50px; background: linear-gradient(135deg, var(--teal), #00a085); border-radius: 40%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: white; box-shadow: 0 8px 20px rgba(0,212,170,0.3); flex-shrink: 0; }
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
        .nav-label { font-size: 0.65rem; font-weight: 600; color: rgba(255,255,255,0.25); letter-spacing: 1.8px; text-transform: uppercase; padding: 0 8px 8px; margin-top: 8px; }
        .nav-item { list-style: none; margin: 2px 0; }
        .nav-link { display: flex; align-items: center; padding: 10px 12px; color: rgba(255,255,255,0.55); text-decoration: none; border-radius: 10px; gap: 10px; font-size: 0.88rem; font-weight: 500; transition: all 0.25s; }
        .nav-link .nav-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.95rem; background: rgba(255,255,255,0.05); flex-shrink: 0; transition: all 0.25s; }
        .nav-link:hover { background: rgba(255,255,255,0.06); color: white; }
        .nav-link:hover .nav-icon { background: rgba(0,212,170,0.15); color: var(--teal); }
        .nav-link.active { background: linear-gradient(90deg, rgba(0,212,170,0.15), rgba(0,212,170,0.05)); color: white; border: 1px solid rgba(0,212,170,0.2); }
        .nav-link.active .nav-icon { background: var(--teal); color: var(--navy); }
        .nav-item.has-sub > .nav-link { cursor: pointer; }
        .nav-item.has-sub > .nav-link::after { content: '\f078'; font-family: 'Font Awesome 6 Free'; font-weight: 900; font-size: 0.6rem; margin-left: auto; transition: transform 0.3s; opacity: 0.5; }
        .nav-item.has-sub.open > .nav-link::after { transform: rotate(180deg); }
        .sub-menu { overflow: hidden; max-height: 0; transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1); }
        .nav-item.has-sub.open .sub-menu { max-height: 300px; }
        .sub-menu li { list-style: none; }
        .sub-menu .nav-link { padding: 8px 12px 8px 54px; font-size: 0.82rem; color: rgba(255,255,255,0.45); }
        .sub-menu .nav-link:hover { color: var(--teal); background: rgba(0,212,170,0.06); }
        .sidebar-bottom { padding: 16px; border-top: 1px solid var(--border); position: relative; z-index: 1; }
        .logout-btn { display: flex; align-items: center; gap: 10px; padding: 11px 14px; background: rgba(255,107,138,0.08); border-radius: 10px; color: rgba(255,107,138,0.8); text-decoration: none; font-size: 0.88rem; font-weight: 500; transition: all 0.25s; border: 1px solid rgba(255,107,138,0.12); }
        .logout-btn:hover { background: rgba(255,107,138,0.15); color: var(--rose); transform: translateX(3px); }

        /* ===== MAIN CONTENT ===== */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; transition: margin-left 0.4s; }
        .top-header { background: rgba(248, 250, 253, 0.92); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); padding: 0 36px; height: 70px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 900; border-bottom: 1px solid rgba(0,0,0,0.06); }
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

        .page-body { padding: 32px 36px; }
        .card { background: white; border-radius: 20px; box-shadow: var(--card-shadow); padding: 24px; }
        .appt-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .appt-table th { padding: 14px 16px; background: var(--off-white); font-weight: 700; text-align: left; border-bottom: 1.5px solid rgba(0,0,0,0.06); }
        .appt-table td { padding: 14px 16px; border-bottom: 1px solid rgba(0,0,0,0.04); vertical-align: middle; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fecdd5; color: #9b1c2c; }
        .action-btn { padding: 5px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-right: 6px; cursor: pointer; border: none; font-family: 'Outfit', sans-serif; transition: all 0.2s; }
        .btn-approve { background: #d1fae5; color: #065f46; }
        .btn-approve:hover { background: #a7f3d0; }
        .btn-reject { background: #fecdd5; color: #9b1c2c; }
        .btn-reject:hover { background: #fda4af; }
        .btn-delete { background: #fee2e2; color: #b91c1c; }
        .btn-delete:hover { background: #fecaca; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(11,20,38,0.5); backdrop-filter: blur(4px); z-index: 999; }
        
        /* Note modal */
        .note-modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 2000;
            align-items: center; justify-content: center;
        }
        .note-modal.show { display: flex; }
        .note-modal-content {
            background: white; border-radius: 20px; padding: 28px;
            width: 90%; max-width: 480px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .note-modal h3 { margin-bottom: 16px; }
        .note-modal textarea {
            width: 100%; border: 1.5px solid rgba(0,0,0,0.1);
            border-radius: 12px; padding: 12px;
            font-family: 'Outfit', sans-serif; resize: vertical; min-height: 80px;
        }
        .note-modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }
        .btn-cancel { background: #e2e8f0; color: #333; border: none; padding: 8px 18px; border-radius: 8px; cursor: pointer; }
        .btn-submit { background: var(--teal); color: white; border: none; padding: 8px 18px; border-radius: 8px; cursor: pointer; }
        
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

<aside class="sidebar" id="sidebar">
    <div class="sidebar-scroll">
        <div class="sidebar-logo">
            <div class="logo-mark">
                <div class="logo-icon"><img src="images/aroroy_logo.png" alt="Aroroy Logo" style="width:50px; height:50px; object-fit:cover;"></div>
                <div class="logo-text"><h3>Aroroy PIS</h3></div>
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
                    <a href="#" class="nav-link active">
                        <span class="nav-icon"><i class="fa-solid fa-book-medical"></i></span>
                        <span>Appointments</span>
                    </a>
                    <ul class="sub-menu">
                        <li><a href="appointments.php" class="nav-link active">Appointments</a></li>
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
        <a href="logout.php" class="logout-btn"><i class="fas fa-arrow-right-from-bracket"></i><span>Sign Out</span></a>
    </div>
</aside>

<div class="main-content">
    <div class="top-header">
        <div class="page-breadcrumb">
            <h1>Appointments</h1>
            <p>Manage patient appointment requests</p>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <div class="header-profile">
                <div class="header-avatar"><i class="fas fa-user"></i></div>
                <span class="header-profile-name"><?php echo htmlspecialchars($display_name); ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <button class="menu-toggle" id="menuToggle"><span></span><span></span><span></span></button>
        </div>
    </div>

    <div class="page-body">
        <div class="card">
            <h2 style="margin-bottom:20px"><i class="fas fa-calendar-check" style="color:var(--teal)"></i> Patient Appointments</h2>
            <div style="overflow-x:auto;">
                <table class="appt-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Note</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $a): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['patient_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($a['appointment_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($a['appointment_time'])); ?></td>
                            <td><?php echo htmlspecialchars($a['reason']); ?></td>
                            <td><span class="status-badge status-<?php echo $a['status']; ?>"><?php echo ucfirst($a['status']); ?></span></td>
                            <td><?php echo htmlspecialchars($a['admin_note'] ?? ''); ?></td>
                            <td>
                                <?php if ($a['status'] == 'pending'): ?>
                                    <button class="action-btn btn-approve" onclick="openNoteModal(<?php echo $a['id']; ?>, 'approved')"><i class="fas fa-check"></i> Approve</button>
                                    <button class="action-btn btn-reject" onclick="openNoteModal(<?php echo $a['id']; ?>, 'rejected')"><i class="fas fa-times"></i> Reject</button>
                                <?php else: ?>
                                    <span style="color:var(--text-muted)">—</span>
                                <?php endif; ?>
                                <a href="?delete=1&id=<?php echo $a['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this appointment? This cannot be undone.')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($appointments)): ?>
                            <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted)">No appointments yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Note Modal -->
<div class="note-modal" id="noteModal">
    <div class="note-modal-content">
        <h3>Add a Note (optional)</h3>
        <form method="POST">
            <input type="hidden" name="appt_id" id="noteApptId">
            <input type="hidden" name="new_status" id="noteStatus">
            <textarea name="admin_note" placeholder="Write a message to the patient..."></textarea>
            <div class="note-modal-actions">
                <button type="button" class="btn-cancel" onclick="closeNoteModal()">Cancel</button>
                <button type="submit" name="action_submit" class="btn-submit">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Mobile sidebar toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (menuToggle) {
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
    }

    // ========================
    // FIX: Proper sidebar submenu toggling + active state management
    // Prevents accidental opens, ensures only relevant menu expands
    // ========================
    (function() {
        const navItems = document.querySelectorAll('.nav-item.has-sub');
        
        // Helper to close all other submenus except the target one
        function closeOtherSubmenus(keepOpenItem = null) {
            navItems.forEach(item => {
                if (keepOpenItem !== item && item.classList.contains('open')) {
                    item.classList.remove('open');
                }
            });
        }
        
        // Set up click handlers for each parent nav-link (toggle submenu)
        navItems.forEach(item => {
            const parentLink = item.querySelector(':scope > .nav-link');
            if (parentLink && parentLink.getAttribute('href') === '#') {
                parentLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();  // Prevent any weird bubbling
                    
                    // If this submenu is already open, just close it
                    if (item.classList.contains('open')) {
                        item.classList.remove('open');
                    } else {
                        // Close others first, then open this one
                        closeOtherSubmenus(item);
                        item.classList.add('open');
                    }
                });
            }
        });
        
        // Function to expand active menu based on current URL
        function expandActiveMenu() {
            const currentPath = window.location.pathname.split('/').pop();
            const allSubLinks = document.querySelectorAll('.sub-menu .nav-link');
            let activeParent = null;
            
            // Find which submenu contains the active link
            allSubLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href === currentPath || (currentPath === '' && href === 'dashboard.php')) {
                    const parentItem = link.closest('.nav-item.has-sub');
                    if (parentItem) activeParent = parentItem;
                }
            });
            
            // Also check if main non-submenu item is active
            const mainLinks = document.querySelectorAll('.nav-item:not(.has-sub) > .nav-link');
            mainLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href === currentPath || (currentPath === '' && href === 'dashboard.php')) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
            
            // For the active submenu parent, expand it and close others
            if (activeParent) {
                closeOtherSubmenus(activeParent);
                activeParent.classList.add('open');
            } else {
                // If no active submenu found, close all
                closeOtherSubmenus(null);
            }
        }
        
        // Also ensure that clicking on child links doesn't affect toggle behavior
        document.querySelectorAll('.sub-menu .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                // Allow normal navigation, but no bubbling to parent toggles
                e.stopPropagation();
            });
        });
        
        // On page load, expand the correct menu based on current page
        expandActiveMenu();
        
        // Small edge: When window resizes or any popstate, re-evaluate (optional)
        window.addEventListener('popstate', function() {
            expandActiveMenu();
        });
    })();

    // Note modal handlers
    const noteModal = document.getElementById('noteModal');
    function openNoteModal(apptId, status) {
        if (!noteModal) return;
        document.getElementById('noteApptId').value = apptId;
        document.getElementById('noteStatus').value = status;
        noteModal.classList.add('show');
    }
    function closeNoteModal() {
        if (noteModal) noteModal.classList.remove('show');
    }
    if (noteModal) {
        noteModal.addEventListener('click', function(e) {
            if (e.target === noteModal) closeNoteModal();
        });
    }
</script>
</body>
</html>