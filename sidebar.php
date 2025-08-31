<?php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get department name from session or database
$departmentName = isset($_SESSION['department_name']) ? $_SESSION['department_name'] : 'HR Department';

// Get current file path safely
$currentFile = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['PHP_SELF'];

// Function to check if a path matches the current page
function isActivePage($path, $currentPath)
{
    return strpos($currentPath, $path) !== false;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compensation Planning Module</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --text-dark: #212529;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --border-radius: 0.35rem;
            --shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            --transition-speed: 0.3s;
            --header-height: 70px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            overflow-x: hidden;
            background-color: var(--secondary-color);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar with open/close animation */
        .sidebar {
            width: var(--sidebar-width);
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            background: #2c3e50;
            color: white;
            padding: 0;
            z-index: 1000;
            transition: transform var(--transition-speed) ease, width var(--transition-speed) ease;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .sidebar.collapsed {
            transform: translateX(calc(var(--sidebar-width) * -1));
        }

        .sidebar-header {
            flex-shrink: 0;
            position: sticky;
            top: 0;
            background: #2c3e50;
            z-index: 1001;
        }

        .sidebar .logo {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .logo img {
            max-width: 100%;
            height: auto;
            max-height: 100px;
        }

        .system-name {
            padding: 0.5rem 1.5rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
            transition: opacity var(--transition-speed);
        }

        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding-bottom: 60px;
            /* Space for logout button */
        }

        .sidebar-footer {
            flex-shrink: 0;
            position: absolute;
            bottom: 0;
            width: 100%;
            background: #2c3e50;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar a {
            display: block;
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: padding var(--transition-speed);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 3px solid white;
        }

        /* Submenu Styles - With Animation */
        .menu-item {
            position: relative;
        }

        .menu-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .dropdown-arrow {
            transition: transform var(--transition-speed) ease;
            font-size: 0.8rem;
            transform: rotate(0deg);
            display: inline-block;
        }

        .menu-toggle.active .dropdown-arrow {
            transform: rotate(180deg);
        }

        .sub-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height var(--transition-speed) ease-out;
            background: rgba(0, 0, 0, 0.1);
        }

        .sub-menu.show {
            max-height: 500px;
        }

        .sub-menu a {
            padding-left: 2.5rem;
            font-size: 0.9rem;
            border-left: 3px solid transparent;
            transition: padding-left calc(var(--transition-speed) * 0.8) ease;
        }

        .sub-menu a:hover {
            padding-left: 3rem;
        }

        .sub-menu a.active {
            background-color: rgba(255, 255, 255, 0.15);
            border-left: 3px solid var(--info-color);
        }

        .admin-feature {
            background-color: rgba(0, 0, 0, 0.1);
        }

        /* Custom scrollbar for sidebar */
        .sidebar-content::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar-content::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        .sidebar-content::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Main Content with sidebar animation */
        .main-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-speed) ease;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width));
        }

        .main-wrapper.expanded {
            margin-left: 0;
            width: 100%;
        }

        /* Header - Fixed at the top */
        .header {
            background-color: white;
            padding: 1rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
            width: 100%;
            min-height: var(--header-height);
        }

        .hamburger {
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            transition: transform var(--transition-speed) ease;
            z-index: 1002;
        }

        .sidebar.collapsed~.main-wrapper .hamburger {
            transform: rotate(90deg);
        }

        .system-title {
            color: var(--primary-color);
            font-size: 1rem;
        }

        .header-title {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        /* Content area */
        .content {
            flex: 1;
            padding: 20px;
        }

        /* Compensation Module Styles */
        .module-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e3e6f0;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .module-title {
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: 600;
        }

        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .module-card {
            background: #f8f9fc;
            border-radius: var(--border-radius);
            padding: 1.25rem;
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid var(--primary-color);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-dark);
        }

        .card-content {
            color: #6e707e;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            flex-grow: 1;
        }

        .card-link {
            display: inline-block;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
        }

        .card-link:hover {
            text-decoration: underline;
        }

        /* Mobile overlay for sidebar */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        /* Responsive Design */
        /* Large devices (desktops, less than 1200px) */
        @media (max-width: 1199.98px) {
            .module-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        /* Medium devices (tablets, less than 992px) */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(calc(var(--sidebar-width) * -1));
                width: var(--sidebar-width);
            }

            .sidebar.show {
                transform: translateX(0);
                box-shadow: 5px 0 15px rgba(0, 0, 0, 0.2);
            }

            .main-wrapper {
                margin-left: 0;
                width: 100%;
            }

            .header-title {
                font-size: 1rem;
            }

            .system-title {
                font-size: 0.9rem;
            }
        }

        /* Small devices (landscape phones, less than 768px) */
        @media (max-width: 767.98px) {
            .header {
                padding: 0.75rem;
            }

            .header-title {
                font-size: 0.9rem;
            }

            .system-title {
                font-size: 0.8rem;
            }

            .module-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .module-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .content {
                padding: 15px;
            }

            .module-container {
                padding: 1rem;
            }
        }

        /* Extra small devices (portrait phones, less than 576px) */
        @media (max-width: 575.98px) {
            :root {
                --sidebar-width: 100%;
            }

            .header {
                flex-wrap: wrap;
            }

            .header>div:last-child {
                width: 100%;
                margin-top: 0.5rem;
            }

            .header-title {
                flex-direction: row;
                justify-content: space-between;
                width: 100%;
            }

            .module-card {
                padding: 1rem;
            }

            .sidebar .logo {
                padding: 1rem;
            }

            .sidebar a {
                padding: 1rem 1.5rem;
            }

            .sub-menu a {
                padding-left: 3rem;
            }
        }

        /* High-resolution displays */
        @media (min-resolution: 120dpi) {
            .sidebar a {
                padding: 1rem 1.5rem;
            }
        }

        /* Touch device improvements */
        @media (hover: none) and (pointer: coarse) {
            .module-card:hover {
                transform: none;
            }

            .sidebar a {
                padding: 1rem 1.5rem;
            }

            .card-link {
                min-height: 44px;
                line-height: 44px;
            }

            .hamburger {
                min-width: 44px;
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <!-- Mobile overlay for sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="freight.png" alt="SLATE Logo">
            </div>
            <div class="system-name"><?php echo htmlspecialchars($departmentName); ?></div>
        </div>

        <div class="sidebar-content">
            <!-- Dashboard -->
            <a href="../index.php" class="<?php echo ($currentFile == 'index.php' || $currentFile == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt fa-fw"></i>
                <span>Dashboard</span>
            </a>

            <!-- Core Human Capital with dropdown -->
            <div class="menu-item">
                <a href="#" class="menu-toggle <?php echo isActivePage('Corehumancapital', $currentPath) || isActivePage('hr_verification', $currentPath) || isActivePage('assign', $currentPath) || isActivePage('sched', $currentPath) || isActivePage('train', $currentPath) || isActivePage('status', $currentPath) || isActivePage('monitor-evaluation', $currentPath) ? 'active' : ''; ?>" id="core-human-capital-link">
                    <i class="fas fa-users fa-fw"></i>
                    <span>Core Human Capital</span>
                    <span class="dropdown-arrow">▼</span>
                </a>
                <div class="sub-menu" id="core-human-capital-submenu">
                    <a href="/system/hr_verification/hr_review.php" class="<?php echo isActivePage('hr_review.php', $currentPath) || isActivePage('verify_approve.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle fa-fw"></i>
                        <span>Verify & Approve Information</span>
                    </a>
                    <a href="/system/assign/assign.php" class="<?php echo isActivePage('assign.php', $currentPath) || isActivePage('assign_role_dept.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-user-tag fa-fw"></i>
                        <span>Assign Role and Department</span>
                    </a>
                    <a href="../sched/sched--onboard.php" class="<?php echo isActivePage('sched--onboard.php', $currentPath) || isActivePage('schedule_onboarding.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt fa-fw"></i>
                        <span>Schedule Onboarding & Orientation</span>
                    </a>
                    <a href="../train/training.php" class="<?php echo isActivePage('training.php', $currentPath) || isActivePage('training_records.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-graduation-cap fa-fw"></i>
                        <span>Training Records</span>
                    </a>
                    <a href="../status/status.php" class="<?php echo isActivePage('status.php', $currentPath) || isActivePage('update_employee_status.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-user-check fa-fw"></i>
                        <span>Employee status</span>
                    </a>
                    <a href="../monitor-evaluation/mande.php" class="<?php echo isActivePage('mande.php', $currentPath) || isActivePage('monitoring_evaluation.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line fa-fw"></i>
                        <span>Monitoring & Evaluation</span>
                    </a>
                </div>
            </div>

            <!-- HMO and Benefits with dropdown -->
            <div class="menu-item">
                <a href="#" class="menu-toggle <?php echo isActivePage('hmo-benefits', $currentPath) || isActivePage('benefits', $currentPath) ? 'active' : ''; ?>" id="hmo-benefits-link">
                    <i class="fas fa-heartbeat fa-fw"></i>
                    <span>HMO & Benefits</span>
                    <span class="dropdown-arrow">▼</span>
                </a>
                <div class="sub-menu" id="hmo-benefits-submenu">
                    <a href="../system/hmo-benefits/claim_management.php" class="<?php echo isActivePage('claim_management.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice-dollar fa-fw"></i>
                        <span>Claim Management</span>
                    </a>
                    <a href="../hmo-benefits/policy-coverage.php" class="<?php echo isActivePage('policy-coverage.php', $currentPath) || isActivePage('policy_coverage.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-shield-alt fa-fw"></i>
                        <span>Policy & Coverage Management</span>
                    </a>
                    <a href="../hmo-benefits/benefits-admin.php" class="<?php echo isActivePage('benefits-admin.php', $currentPath) || isActivePage('benefits_admin.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-hand-holding-usd fa-fw"></i>
                        <span>Benefits Administration</span>
                    </a>
                    <a href="../hmo-benefits/benefits-tracking.php" class="<?php echo isActivePage('benefits-tracking.php', $currentPath) || isActivePage('employee_self_service.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-user-cog fa-fw"></i>
                        <span>Benefits Tracking & Utilization</span>
                    </a>
                </div>
            </div>

            <!-- Payroll Management -->
            <div class="menu-item">
                <a href="#" class="menu-toggle <?php echo isActivePage('payroll', $currentPath) ? 'active' : ''; ?>" id="payroll-link">
                    <i class="fas fa-money-check fa-fw"></i>
                    <span>Payroll Management</span>
                    <span class="dropdown-arrow">▼</span>
                </a>
                <div class="sub-menu" id="payroll-submenu">
                    <a href="../payroll/earning.php" class="<?php echo isActivePage('earning.php', $currentPath) || isActivePage('earnings_calculation.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-calculator fa-fw"></i>
                        <span>Earnings Calculation</span>
                    </a>
                    <a href="../payroll/deductions.php" class="<?php echo isActivePage('deductions.php', $currentPath) || isActivePage('deductions_compliance.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-minus-circle fa-fw"></i>
                        <span>Deductions & Statutory Compliance</span>
                    </a>
                    <a href="../payroll/payslip-generator.php" class="<?php echo isActivePage('payslip-generator.php', $currentPath) || isActivePage('payslip_management.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-receipt fa-fw"></i>
                        <span>Payslip Management</span>
                    </a>
                    <a href="../payroll/disbursement.php" class="<?php echo isActivePage('disbursement.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-paper-plane fa-fw"></i>
                        <span>Disbursement</span>
                    </a>
                    <a href="../payroll/payroll-reports.php" class="<?php echo isActivePage('payroll-reports.php', $currentPath) || isActivePage('payroll_reports.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar fa-fw"></i>
                        <span>Payroll Reports</span>
                    </a>
                </div>
            </div>

            <!-- Compensation Planning -->
            <div class="menu-item">
                <a href="#" class="menu-toggle <?php echo isActivePage('compensation', $currentPath) ? 'active' : ''; ?>" id="compensation-link">
                    <i class="fas fa-coins fa-fw"></i>
                    <span>Compensation</span>
                    <span class="dropdown-arrow">▼</span>
                </a>
                <div class="sub-menu" id="compensation-submenu">
                    <a href="../compensation/base-salary.php" class="<?php echo isActivePage('base-salary.php', $currentPath) || isActivePage('base_salary.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-money-bill-wave fa-fw"></i>
                        <span>Base Salary Assignment</span>
                    </a>
                    <a href="../compensation/insentive-bonus.php" class="<?php echo isActivePage('insentive-bonus.php', $currentPath) || isActivePage('incentives.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-gift fa-fw"></i>
                        <span>Incentives & Bonus Management</span>
                    </a>
                    <a href="../compensation/allowance-benefits.php" class="<?php echo isActivePage('allowance-benefits.php', $currentPath) || isActivePage('allowances.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-hand-holding-usd fa-fw"></i>
                        <span>Allowances & Benefits (Monetary)</span>
                    </a>
                    <a href="../compensation/adjustment-deduc.php" class="<?php echo isActivePage('adjustment-deduc.php', $currentPath) || isActivePage('adjustments.php', $currentPath) ? 'active' : ''; ?>">
                        <i class="fas fa-adjust fa-fw"></i>
                        <span>Adjustments & Deductions</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="sidebar-footer">
            <!-- Logout -->
            <a href="/capstone/logout.php">
                <i class="fas fa-sign-out-alt fa-fw"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="main-wrapper" id="mainWrapper">
        <div class="header">
            <div class="hamburger" id="hamburger">☰</div>
            <div>
                <h1 class="header-title">hr4 compensation and hr intelligence <span class="system-title">| <?php echo htmlspecialchars($departmentName); ?></span></h1>
            </div>
        </div>

        <script>
            // Make sure we only initialize once even if included multiple times
            if (!window.sidebarInitialized) {
                window.sidebarInitialized = true;

                document.addEventListener('DOMContentLoaded', function() {
                    const sidebar = document.getElementById('sidebar');
                    const mainWrapper = document.getElementById('mainWrapper');
                    const hamburger = document.getElementById('hamburger');
                    const sidebarOverlay = document.getElementById('sidebarOverlay');

                    // Get menu elements safely (they might not exist in all pages)
                    const coreHumanCapitalLink = document.getElementById('core-human-capital-link');
                    const coreHumanCapitalSubmenu = document.getElementById('core-human-capital-submenu');
                    const hmoBenefitsLink = document.getElementById('hmo-benefits-link');
                    const hmoBenefitsSubmenu = document.getElementById('hmo-benefits-submenu');
                    const payrollLink = document.getElementById('payroll-link');
                    const payrollSubmenu = document.getElementById('payroll-submenu');
                    const compensationLink = document.getElementById('compensation-link');
                    const compensationSubmenu = document.getElementById('compensation-submenu');

                    // Check if mobile device
                    function isMobile() {
                        return window.innerWidth <= 991.98;
                    }

                    // Sidebar toggle function
                    function toggleSidebar() {
                        if (isMobile()) {
                            sidebar.classList.toggle('show');
                            sidebarOverlay.style.display = sidebar.classList.contains('show') ? 'block' : 'none';
                            document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
                        } else {
                            sidebar.classList.toggle('collapsed');
                            mainWrapper.classList.toggle('expanded');

                            // Rotate hamburger icon
                            if (sidebar.classList.contains('collapsed')) {
                                hamburger.style.transform = 'rotate(90deg)';
                            } else {
                                hamburger.style.transform = 'rotate(0deg)';
                            }

                            // Save sidebar state in localStorage
                            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                        }
                    }

                    // Apply saved sidebar state
                    function applySavedSidebarState() {
                        if (!isMobile()) {
                            const savedState = localStorage.getItem('sidebarCollapsed');
                            if (savedState === 'true') {
                                sidebar.classList.add('collapsed');
                                mainWrapper.classList.add('expanded');
                                hamburger.style.transform = 'rotate(90deg)';
                            }
                        }
                    }

                    // Hamburger menu click handler
                    if (hamburger) {
                        hamburger.addEventListener('click', function(e) {
                            e.stopPropagation();
                            toggleSidebar();
                        });
                    }

                    // Close sidebar when clicking on overlay
                    if (sidebarOverlay) {
                        sidebarOverlay.addEventListener('click', function() {
                            toggleSidebar();
                        });
                    }

                    // Menu toggle functions with safety checks
                    function setupMenuToggle(linkElement, submenuElement) {
                        if (linkElement && submenuElement) {
                            linkElement.addEventListener('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                submenuElement.classList.toggle('show');
                                this.classList.toggle('active');

                                // Close other menus when opening one
                                if (submenuElement.classList.contains('show')) {
                                    document.querySelectorAll('.sub-menu').forEach(menu => {
                                        if (menu !== submenuElement && menu.classList.contains('show')) {
                                            menu.classList.remove('show');
                                            menu.previousElementSibling.classList.remove('active');
                                        }
                                    });
                                }
                            });
                        }
                    }

                    // Setup all menu toggles
                    setupMenuToggle(coreHumanCapitalLink, coreHumanCapitalSubmenu);
                    setupMenuToggle(hmoBenefitsLink, hmoBenefitsSubmenu);
                    setupMenuToggle(payrollLink, payrollSubmenu);
                    setupMenuToggle(compensationLink, compensationSubmenu);

                    // Automatically open submenu if current page is in it
                    function autoOpenSubmenu(linkElement, submenuElement) {
                        if (linkElement && submenuElement) {
                            const activeItem = submenuElement.querySelector('a.active');
                            if (activeItem) {
                                submenuElement.classList.add('show');
                                linkElement.classList.add('active');
                            }
                        }
                    }

                    autoOpenSubmenu(coreHumanCapitalLink, coreHumanCapitalSubmenu);
                    autoOpenSubmenu(hmoBenefitsLink, hmoBenefitsSubmenu);
                    autoOpenSubmenu(payrollLink, payrollSubmenu);
                    autoOpenSubmenu(compensationLink, compensationSubmenu);

                    // Close sidebar when clicking outside on mobile
                    document.addEventListener('click', function(e) {
                        if (isMobile() &&
                            !e.target.closest('#sidebar') &&
                            sidebar.classList.contains('show')) {
                            toggleSidebar();
                        }
                    });

                    // Handle window resize
                    function handleResize() {
                        if (!isMobile()) {
                            sidebar.classList.remove('show');
                            sidebarOverlay.style.display = 'none';
                            document.body.style.overflow = '';
                        } else {
                            sidebar.classList.add('collapsed');
                            mainWrapper.classList.add('expanded');
                        }
                    }

                    window.addEventListener('resize', handleResize);
                    applySavedSidebarState(); // Apply saved state on load
                    handleResize(); // Initial check
                });
            }
        </script>
</body>

</html>