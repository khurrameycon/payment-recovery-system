<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Payment Recovery System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #4f46e5;
            --success-color: #16a34a;
            --warning-color: #eab308;
            --danger-color: #dc2626;
            --info-color: #0ea5e9;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
        }
        
        body {
            background-color: #f3f4f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background-color: var(--dark-color);
            color: white;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .sidebar-collapsed {
            width: 70px;
            transition: all 0.3s;
        }
        
        .content-wrapper {
            margin-left: 250px;
            transition: all 0.3s;
        }
        
        .content-expanded {
            margin-left: 70px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 0;
            padding: 0.75rem 1rem;
            transition: all 0.2s;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background-color: var(--primary-color);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            border-left: 4px solid;
            overflow: hidden;
        }
        
        .stat-card.primary {
            border-color: var(--primary-color);
        }
        
        .stat-card.success {
            border-color: var(--success-color);
        }
        
        .stat-card.warning {
            border-color: var(--warning-color);
        }
        
        .stat-card.info {
            border-color: var(--info-color);
        }
        
        .stat-card .card-body {
            position: relative;
        }
        
        .stat-card .stat-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 4rem;
            opacity: 0.15;
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }
        
        .progress {
            height: 0.8rem;
            border-radius: 0.5rem;
        }
        
        .top-nav {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .user-dropdown img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .chart-container {
            position: relative;
            min-height: 300px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            .content-wrapper {
                margin-left: 70px;
            }
            .sidebar-toggle {
                display: none;
            }
            .hide-on-collapse {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="p-3 d-flex justify-content-between align-items-center">
            <h5 class="text-white mb-0 hide-on-collapse">Recovery System</h5>
            <img src="assets/logo-small.png" class="d-none d-sm-block" width="30" height="30" alt="Logo">
            <button class="btn btn-sm btn-link text-white d-none d-md-block sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <hr class="my-2 text-secondary">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="index.php?route=dashboard">
                    <i class="fas fa-gauge-high"></i>
                    <span class="hide-on-collapse">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="index.php?route=failed-transactions">
                    <i class="fas fa-credit-card"></i>
                    <span class="hide-on-collapse">Failed Transactions</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="index.php?route=reminder-report">
                    <i class="fas fa-bell"></i>
                    <span class="hide-on-collapse">Reminders</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="index.php?route=advanced-analytics">
                    <i class="fas fa-chart-line"></i>
                    <span class="hide-on-collapse">Analytics</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="index.php?route=settings">
                    <i class="fas fa-gear"></i>
                    <span class="hide-on-collapse">Settings</span>
                </a>
            </li>
        </ul>
        <hr class="my-2 text-secondary">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="index.php?route=logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="hide-on-collapse">Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="content-wrapper" id="content">
        <!-- Top Navigation -->
        <nav class="navbar top-nav navbar-expand-lg navbar-light py-2">
            <div class="container-fluid">
                <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="topNav">
                    <form class="d-flex me-auto">
                        <div class="input-group">
                            <input class="form-control" type="search" placeholder="Search transactions..." aria-label="Search">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <span class="badge rounded-pill bg-danger">3</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <h6 class="dropdown-header">Notifications</h6>
                                <a class="dropdown-item" href="#">5 new recoveries today</a>
                                <a class="dropdown-item" href="#">10 reminders were sent</a>
                                <a class="dropdown-item" href="#">System update completed</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-center" href="#">View all notifications</a>
                            </div>
                        </li>
                        <li class="nav-item dropdown ms-3">
                            <a class="nav-link dropdown-toggle user-dropdown d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <img src="https://via.placeholder.com/36" alt="User">
                                <span class="ms-2"><?php echo $_SESSION['user_name'] ?? 'Administrator'; ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw me-2 text-muted"></i>
                                    Profile
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cogs fa-sm fa-fw me-2 text-muted"></i>
                                    Settings
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-list fa-sm fa-fw me-2 text-muted"></i>
                                    Activity Log
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="index.php?route=logout">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-muted"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid p-4">
            <!-- Page Heading -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                <div>
                    <button class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-download fa-sm"></i> Export
                    </button>
                    <button class="btn btn-sm btn-primary">
                        <i class="fas fa-plus fa-sm"></i> New Import
                    </button>
                </div>
            </div>

            <!-- Stats Cards Row -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card primary h-100">
                        <div class="card-body">
                            <div class="stat-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <div class="text-xs text-uppercase mb-1 text-muted">Failed Transactions</div>
                                <div class="h3 mb-0 font-weight-bold"><?php echo $stats['total_failed']; ?></div>
                            </div>
                            <div class="mt-2 text-success small">
                                <i class="fas fa-arrow-up"></i> 12% increase this week
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card success h-100">
                        <div class="card-body">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <div class="text-xs text-uppercase mb-1 text-muted">Recovered Payments</div>
                                <div class="h3 mb-0 font-weight-bold"><?php echo $stats['total_recovered']; ?></div>
                            </div>
                            <div class="mt-2 text-success small">
                                <i class="fas fa-arrow-up"></i> 8% increase this week
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card warning h-100">
                        <div class="card-body">
                            <div class="stat-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <div class="text-xs text-uppercase mb-1 text-muted">Recovery Rate</div>
                                <div class="h3 mb-0 font-weight-bold"><?php echo $stats['recovery_rate']; ?>%</div>
                            </div>
                            <div class="mt-2 text-success small">
                                <i class="fas fa-arrow-up"></i> 3% improvement
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card info h-100">
                        <div class="card-body">
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <div class="text-xs text-uppercase mb-1 text-muted">Amount Recovered</div>
                                <div class="h3 mb-0 font-weight-bold">$<?php echo number_format($stats['total_amount_recovered'], 2); ?></div>
                            </div>
                            <div class="mt-2 text-success small">
                                <i class="fas fa-arrow-up"></i> 25% increase this month
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-4 mb-4">
                <div class="col-xl-8">
                    <div class="card h-100">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold">Recovery Trend</h6>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link" type="button" id="trendDropdown" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="trendDropdown">
                                    <a class="dropdown-item" href="#">Weekly</a>
                                    <a class="dropdown-item" href="#">Monthly</a>
                                    <a class="dropdown-item" href="#">Yearly</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="recoveryTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card h-100">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold">Recovery by Channel</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="channelChart"></canvas>
                            </div>
                            <div class="mt-4">
                                <?php foreach ($recoveryRates as $rate): ?>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="small text-capitalize"><?php echo $rate['channel']; ?></span>
                                        <span class="small text-muted"><?php echo $rate['recovery_rate']; ?>%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-<?php echo $rate['channel'] == 'email' ? 'primary' : ($rate['channel'] == 'sms' ? 'success' : 'info'); ?>" 
                                             role="progressbar" style="width: <?php echo $rate['recovery_rate']; ?>%" 
                                             aria-valuenow="<?php echo $rate['recovery_rate']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Row -->
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold">Recent Reminders</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Channel</th>
                                            <th>Recipient</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reminders as $reminder): ?>
                                        <tr>
                                            <td><?php echo date('M d, H:i', strtotime($reminder['sent_at'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $reminder['channel'] == 'email' ? 'primary' : 'success'; ?>">
                                                    <?php echo ucfirst($reminder['channel']); ?>
                                                </span>
                                            </td>
                                            <td class="text-truncate" style="max-width: 150px;">
                                                <?php echo htmlspecialchars($reminder['channel'] == 'email' ? $reminder['email'] : $reminder['phone']); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusClass = 'secondary';
                                                if ($reminder['status'] == 'sent') $statusClass = 'info';
                                                if ($reminder['status'] == 'opened') $statusClass = 'primary';
                                                if ($reminder['status'] == 'clicked') $statusClass = 'success';
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($reminder['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="index.php?route=reminder-report" class="text-decoration-none">View all reminders</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold">Recent Recoveries</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // You'll need to fetch recent recoveries in your controller
                                        if (isset($recoveries) && !empty($recoveries)):
                                            foreach ($recoveries as $recovery): 
                                        ?>
                                        <tr>
                                            <td><?php echo date('M d, H:i', strtotime($recovery['recovery_date'])); ?></td>
                                            <td class="text-truncate" style="max-width: 150px;">
                                                <?php echo htmlspecialchars($recovery['email']); ?>
                                            </td>
                                            <td class="font-weight-bold">
                                                $<?php echo number_format($recovery['recovered_amount'], 2); ?>
                                            </td>
                                            <td>
                                                <a href="index.php?route=view-transaction&id=<?php echo $recovery['transaction_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php 
                                            endforeach; 
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">No recent recoveries to display</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="index.php?route=recovery-report" class="text-decoration-none">View all recoveries</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Toggle Sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('sidebar-collapsed');
            document.getElementById('content').classList.toggle('content-expanded');
            
            // Toggle visibility of text in sidebar
            const textElements = document.querySelectorAll('.hide-on-collapse');
            textElements.forEach(function(element) {
                element.classList.toggle('d-none');
            });
        });
        
        // Sample Recovery Trend Chart
        const trendCtx = document.getElementById('recoveryTrendChart').getContext('2d');
        const trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [{
                    label: 'Failed Transactions',
                    data: [65, 78, 80, 74, 83, 90, 95],
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    tension: 0.3,
                    fill: true
                }, {
                    label: 'Recovered Transactions',
                    data: [45, 55, 60, 58, 68, 76, 82],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Sample Channel Chart
        const channelCtx = document.getElementById('channelChart').getContext('2d');
        const channelChart = new Chart(channelCtx, {
            type: 'doughnut',
            data: {
                labels: ['Email', 'SMS', 'WhatsApp'],
                datasets: [{
                    data: [60, 30, 10],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>
</html>