<?php
// File: app/views/enhanced_reminder_report.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminder Report | Payment Recovery System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- DateRangePicker -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f3f4f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .page-header {
            margin-bottom: 1.5rem;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: none;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .filter-card {
            margin-bottom: 1.5rem;
        }
        
        .stats-card {
            text-align: center;
            padding: 1rem;
            height: 100%;
            transition: all 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: inline-block;
            padding: 0.75rem;
            border-radius: 50%;
        }
        
        .stats-card .value {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .stats-card .label {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .tab-card .nav-tabs {
            border-bottom: none;
            padding: 0.5rem 1rem 0;
        }
        
        .tab-card .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            padding: 0.75rem 1rem;
        }
        
        .tab-card .nav-tabs .nav-link.active {
            color: #0d6efd;
            background-color: transparent;
            border-bottom: 3px solid #0d6efd;
        }
        
        .reminder-table th {
            font-weight: 600;
            color: #495057;
        }
        
        .channel-badge {
            padding: 0.35em 0.65em;
            border-radius: 50rem;
            font-size: 0.75em;
            font-weight: 500;
        }
        
        .sentiment-positive {
            color: #198754;
        }
        
        .sentiment-neutral {
            color: #6c757d;
        }
        
        .sentiment-negative {
            color: #dc3545;
        }
        
        .progress {
            height: 0.5rem;
            border-radius: 0.5rem;
        }
        
        .summary-item {
            margin-bottom: 1rem;
        }
        
        .summary-item .title {
            font-weight: 600;
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .summary-item .value {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .summary-item .change {
            font-size: 0.875rem;
            margin-left: 0.5rem;
        }
        
        .summary-item .positive {
            color: #198754;
        }
        
        .summary-item .negative {
            color: #dc3545;
        }
        
        .time-chart-legend {
            display: flex;
            justify-content: center;
            margin-top: 1rem;
        }
        
        .time-chart-legend .legend-item {
            display: flex;
            align-items: center;
            margin-right: 1.5rem;
        }
        
        .time-chart-legend .legend-color {
            width: 1rem;
            height: 1rem;
            border-radius: 0.25rem;
            margin-right: 0.5rem;
        }
        
        .badge-outline {
            background-color: transparent;
            border: 1px solid;
        }
        
        .badge-outline-primary {
            color: #0d6efd;
            border-color: #0d6efd;
        }
        
        .badge-outline-success {
            color: #198754;
            border-color: #198754;
        }
        
        .badge-outline-info {
            color: #0dcaf0;
            border-color: #0dcaf0;
        }
        
        .best-time-card {
            text-align: center;
            padding: 1.5rem;
        }
        
        .best-time-card .time-display {
            font-size: 3rem;
            font-weight: 700;
            margin: 1rem 0;
            color: #0d6efd;
        }
        
        .best-time-card .day-badge {
            display: inline-block;
            padding: 0.35em 1em;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 50rem;
            margin-bottom: 1rem;
        }
        
        .reminder-stats-row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #e9ecef;
            padding: 0.75rem 0;
        }
        
        .reminder-stats-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <!-- Include sidebar and navigation -->

    <!-- Page Content -->
    <div class="container-fluid p-4">
        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Reminder Reports</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php?route=dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Reminder Reports</li>
                    </ol>
                </nav>
            </div>
            <div>
                <button class="btn btn-outline-secondary me-2" id="exportBtn">
                    <i class="fas fa-download me-1"></i> Export Report
                </button>
                <button class="btn btn-primary" id="refreshBtn">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Filters Card -->
        <div class="card filter-card">
            <div class="card-header">
                <h5 class="card-title mb-0">Report Filters</h5>
            </div>
            <div class="card-body">
                <form id="filterForm" class="row g-3" action="index.php" method="get">
                    <input type="hidden" name="route" value="reminder-report">
                    
                    <div class="col-md-4">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <input type="text" class="form-control" id="dateRange" name="date_range" value="<?php echo isset($_GET['date_range']) ? htmlspecialchars($_GET['date_range']) : 'Last 30 days'; ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Channel</label>
                        <select class="form-select" name="channel">
                            <option value="">All Channels</option>
                            <option value="email" <?php echo isset($_GET['channel']) && $_GET['channel'] === 'email' ? 'selected' : ''; ?>>Email</option>
                            <option value="sms" <?php echo isset($_GET['channel']) && $_GET['channel'] === 'sms' ? 'selected' : ''; ?>>SMS</option>
                            <option value="whatsapp" <?php echo isset($_GET['channel']) && $_GET['channel'] === 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Customer Segment</label>
                        <select class="form-select" name="segment">
                            <option value="">All Segments</option>
                            <option value="vip" <?php echo isset($_GET['segment']) && $_GET['segment'] === 'vip' ? 'selected' : ''; ?>>VIP</option>
                            <option value="high_priority" <?php echo isset($_GET['segment']) && $_GET['segment'] === 'high_priority' ? 'selected' : ''; ?>>High Priority</option>
                            <option value="standard" <?php echo isset($_GET['segment']) && $_GET['segment'] === 'standard' ? 'selected' : ''; ?>>Standard</option>
                            <option value="nurture" <?php echo isset($_GET['segment']) && $_GET['segment'] === 'nurture' ? 'selected' : ''; ?>>Nurture</option>
                            <option value="low_priority" <?php echo isset($_GET['segment']) && $_GET['segment'] === 'low_priority' ? 'selected' : ''; ?>>Low Priority</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Group By</label>
                        <select class="form-select" name="group_by">
                            <option value="day" <?php echo isset($_GET['group_by']) && $_GET['group_by'] === 'day' ? 'selected' : ''; ?>>Day</option>
                            <option value="week" <?php echo isset($_GET['group_by']) && $_GET['group_by'] === 'week' ? 'selected' : ''; ?>>Week</option>
                            <option value="month" <?php echo isset($_GET['group_by']) && $_GET['group_by'] === 'month' ? 'selected' : ''; ?>>Month</option>
                            <option value="channel" <?php echo isset($_GET['group_by']) && $_GET['group_by'] === 'channel' ? 'selected' : ''; ?>>Channel</option>
                            <option value="segment" <?php echo isset($_GET['group_by']) && $_GET['group_by'] === 'segment' ? 'selected' : ''; ?>>Segment</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Summary Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="value"><?php echo number_format($stats['total_sent'] ?? 0); ?></div>
                    <div class="label">Total Reminders Sent</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card">
                    <div class="stats-icon bg-info bg-opacity-10 text-info">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                    <div class="value"><?php echo $stats['open_rate'] ?? 0; ?>%</div>
                    <div class="label">Average Open Rate</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card">
                    <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-mouse-pointer"></i>
                    </div>
                    <div class="value"><?php echo $stats['click_rate'] ?? 0; ?>%</div>
                    <div class="label">Average Click Rate</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card">
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="value"><?php echo $stats['recovery_rate'] ?? 0; ?>%</div>
                    <div class="label">Average Recovery Rate</div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row g-3 mb-4">
            <!-- Performance Trend Chart -->
            <div class="col-xl-8">
                <div class="card tab-card">
                    <ul class="nav nav-tabs" id="reminderTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="trend-tab" data-bs-toggle="tab" data-bs-target="#trend" type="button" role="tab" aria-controls="trend" aria-selected="true">Performance Trend</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="channel-tab" data-bs-toggle="tab" data-bs-target="#channel" type="button" role="tab" aria-controls="channel" aria-selected="false">Channel Comparison</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="segment-tab" data-bs-toggle="tab" data-bs-target="#segment" type="button" role="tab" aria-controls="segment" aria-selected="false">Segment Performance</button>
                        </li>
                    </ul>
                    <div class="card-body">
                        <div class="tab-content" id="reminderTabContent">
                            <div class="tab-pane fade show active" id="trend" role="tabpanel" aria-labelledby="trend-tab">
                                <div class="chart-container">
                                    <canvas id="performanceTrendChart"></canvas>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="channel" role="tabpanel" aria-labelledby="channel-tab">
                                <div class="chart-container">
                                    <canvas id="channelComparisonChart"></canvas>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="segment" role="tabpanel" aria-labelledby="segment-tab">
                                <div class="chart-container">
                                    <canvas id="segmentPerformanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Best Sending Time -->
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Optimal Sending Time</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="best-time-card">
                            <div class="day-badge bg-primary bg-opacity-10 text-primary">
                                Tuesday
                            </div>
                            <div class="time-display">10:00 AM</div>
                            <p class="text-muted mb-4">Based on highest open & recovery rates</p>
                            
                            <div class="reminder-stats-row">
                                <div class="stats-label">Open Rate</div>
                                <div class="stats-value text-primary">78.2%</div>
                            </div>
                            <div class="reminder-stats-row">
                                <div class="stats-label">Click Rate</div>
                                <div class="stats-value text-primary">45.6%</div>
                            </div>
                            <div class="reminder-stats-row">
                                <div class="stats-label">Recovery Rate</div>
                                <div class="stats-value text-primary">36.8%</div>
                            </div>
                            
                            <div class="mt-4">
                                <span class="badge badge-outline badge-outline-primary me-2">Morning</span>
                                <span class="badge badge-outline badge-outline-success me-2">Business Hours</span>
                                <span class="badge badge-outline badge-outline-info">Weekday</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Performance by Time Chart -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Performance by Time of Day</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="adjustForTimezone" checked>
                    <label class="form-check-label" for="adjustForTimezone">Adjust for customer timezone</label>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="timeOfDayChart"></canvas>
                </div>
                <div class="time-chart-legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: rgba(13, 110, 253, 0.7);"></div>
                        <div>Open Rate</div>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: rgba(25, 135, 84, 0.7);"></div>
                        <div>Click Rate</div>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: rgba(220, 53, 69, 0.7);"></div>
                        <div>Recovery Rate</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Detailed Stats Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Detailed Reminder Statistics</h5>
                <button class="btn btn-sm btn-outline-secondary" id="exportTableBtn">
                    <i class="fas fa-download me-1"></i> Export Table
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover reminder-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Channel</th>
                                <th>Sent</th>
                                <th>Opened</th>
                                <th>Open Rate</th>
                                <th>Clicked</th>
                                <th>Click Rate</th>
                                <th>Recovered</th>
                                <th>Recovery Rate</th>
                                <th>Avg. Response Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reminderStats)): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">No data available for the selected date range</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reminderStats as $stat): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($stat['date'])); ?></td>
                                        <td>
                                            <span class="channel-badge bg-<?php 
                                                echo $stat['channel'] === 'email' ? 'primary' : 
                                                    ($stat['channel'] === 'sms' ? 'success' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst($stat['channel']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($stat['total_sent']); ?></td>
                                        <td><?php echo number_format($stat['opened']); ?></td>
                                        <td class="<?php echo getColorClass($stat['open_rate']); ?>">
                                            <?php echo number_format($stat['open_rate'], 1); ?>%
                                        </td>
                                        <td><?php echo number_format($stat['clicked']); ?></td>
                                        <td class="<?php echo getColorClass($stat['click_rate']); ?>">
                                            <?php echo number_format($stat['click_rate'], 1); ?>%
                                        </td>
                                        <td><?php echo number_format($stat['recovered']); ?></td>
                                        <td class="<?php echo getColorClass($stat['recovery_rate']); ?>">
                                            <?php echo number_format($stat['recovery_rate'], 1); ?>%
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($stat['avg_response_hours'])):
                                                echo number_format($stat['avg_response_hours'], 1) . ' hrs';
                                            else:
                                                echo "N/A";
                                            endif;
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Moment.js -->
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <!-- DateRangePicker -->
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DateRangePicker
            $('#dateRange').daterangepicker({
                ranges: {
                   'Today': [moment(), moment()],
                   'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                   'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                   'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                   'This Month': [moment().startOf('month'), moment().endOf('month')],
                   'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                startDate: moment().subtract(29, 'days'),
                endDate: moment(),
                locale: {
                    format: 'MMM D, YYYY'
                }
            });
            
            // Performance Trend Chart
            const trendCtx = document.getElementById('performanceTrendChart').getContext('2d');
            const trendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: ['Jan 1', 'Jan 8', 'Jan 15', 'Jan 22', 'Jan 29', 'Feb 5', 'Feb 12', 'Feb 19', 'Feb 26', 'Mar 5', 'Mar 12', 'Mar 19'],
                    datasets: [
                        {
                            label: 'Open Rate',
                            data: [45, 55, 60, 58, 62, 65, 68, 70, 68, 72, 75, 78],
                            borderColor: 'rgba(13, 110, 253, 0.7)',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Click Rate',
                            data: [25, 28, 32, 30, 35, 38, 40, 42, 40, 45, 48, 46],
                            borderColor: 'rgba(25, 135, 84, 0.7)',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Recovery Rate',
                            data: [15, 18, 20, 22, 25, 28, 30, 32, 35, 38, 36, 37],
                            borderColor: 'rgba(220, 53, 69, 0.7)',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            },
                            suggestedMax: 100
                        }
                    }
                }
            });
            
            // Channel Comparison Chart
            const channelCtx = document.getElementById('channelComparisonChart').getContext('2d');
            const channelChart = new Chart(channelCtx, {
                type: 'bar',
                data: {
                    labels: ['Open Rate', 'Click Rate', 'Recovery Rate'],
                    datasets: [
                        {
                            label: 'Email',
                            data: [65, 40, 32],
                            backgroundColor: 'rgba(13, 110, 253, 0.7)'
                        },
                        {
                            label: 'SMS',
                            data: [85, 52, 38],
                            backgroundColor: 'rgba(25, 135, 84, 0.7)'
                        },
                        {
                            label: 'WhatsApp',
                            data: [90, 55, 40],
                            backgroundColor: 'rgba(13, 202, 240, 0.7)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            },
                            suggestedMax: 100
                        }
                    }
                }
            });
            
            // Segment Performance Chart
            const segmentCtx = document.getElementById('segmentPerformanceChart').getContext('2d');
            const segmentChart = new Chart(segmentCtx, {
                type: 'radar',
                data: {
                    labels: ['Open Rate', 'Click Rate', 'Recovery Rate', 'Response Time', 'Engagement'],
                    datasets: [
                        {
                            label: 'VIP',
                            data: [90, 70, 65, 85, 88],
                            borderColor: 'rgba(220, 53, 69, 0.7)',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            pointBackgroundColor: 'rgba(220, 53, 69, 1)'
                        },
                        {
                            label: 'High Priority',
                            data: [85, 65, 55, 75, 80],
                            borderColor: 'rgba(13, 110, 253, 0.7)',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            pointBackgroundColor: 'rgba(13, 110, 253, 1)'
                        },
                        {
                            label: 'Standard',
                            data: [75, 50, 40, 60, 65],
                            borderColor: 'rgba(25, 135, 84, 0.7)',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            pointBackgroundColor: 'rgba(25, 135, 84, 1)'
                        },
                        {
                            label: 'Low Priority',
                            data: [60, 35, 25, 45, 50],
                            borderColor: 'rgba(108, 117, 125, 0.7)',
                            backgroundColor: 'rgba(108, 117, 125, 0.1)',
                            pointBackgroundColor: 'rgba(108, 117, 125, 1)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            angleLines: {
                                display: true
                            },
                            suggestedMin: 0,
                            suggestedMax: 100
                        }
                    }
                }
            });
            
            // Time of Day Chart
            const timeCtx = document.getElementById('timeOfDayChart').getContext('2d');
            const timeChart = new Chart(timeCtx, {
                type: 'bar',
                data: {
                    labels: ['12am', '2am', '4am', '6am', '8am', '10am', '12pm', '2pm', '4pm', '6pm', '8pm', '10pm'],
                    datasets: [
                        {
                            label: 'Open Rate',
                            data: [10, 5, 3, 15, 45, 75, 70, 65, 60, 50, 35, 20],
                            backgroundColor: 'rgba(13, 110, 253, 0.7)'
                        },
                        {
                            label: 'Click Rate',
                            data: [5, 2, 1, 10, 30, 50, 45, 40, 38, 32, 20, 10],
                            backgroundColor: 'rgba(25, 135, 84, 0.7)'
                        },
                        {
                            label: 'Recovery Rate',
                            data: [3, 1, 0, 5, 25, 40, 35, 30, 28, 25, 15, 8],
                            backgroundColor: 'rgba(220, 53, 69, 0.7)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            },
                            suggestedMax: 100
                        }
                    }
                }
            });
            
            // Handle timezone adjustment
            $('#adjustForTimezone').change(function() {
                // In a real implementation, this would reload the data with timezone adjustments
                // For now, just toggle between two datasets to simulate the effect
                if ($(this).is(':checked')) {
                    timeChart.data.datasets[0].data = [10, 5, 3, 15, 45, 75, 70, 65, 60, 50, 35, 20];
                    timeChart.data.datasets[1].data = [5, 2, 1, 10, 30, 50, 45, 40, 38, 32, 20, 10];
                    timeChart.data.datasets[2].data = [3, 1, 0, 5, 25, 40, 35, 30, 28, 25, 15, 8];
                } else {
                    timeChart.data.datasets[0].data = [15, 10, 5, 8, 30, 60, 75, 70, 65, 55, 45, 30];
                    timeChart.data.datasets[1].data = [8, 5, 3, 5, 20, 40, 50, 45, 42, 35, 30, 15];
                    timeChart.data.datasets[2].data = [5, 3, 1, 2, 15, 30, 40, 35, 32, 28, 20, 10];
                }
                timeChart.update();
            });
            
            // Handle refresh button
            $('#refreshBtn').on('click', function() {
                // In a real implementation, this would reload the data
                // For now, just show a message
                showMessage('Report data refreshed successfully');
            });
            
            // Handle export button
            $('#exportBtn').on('click', function() {
                // In a real implementation, this would generate and download a report
                // For now, just show a message
                showMessage('Report exported successfully');
            });
            
            // Handle export table button
            $('#exportTableBtn').on('click', function() {
                // In a real implementation, this would generate and download the table data
                // For now, just show a message
                showMessage('Table data exported successfully');
            });
            
            // Function to show message
            function showMessage(message) {
                const alertHtml = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                
                $('.page-header').after(alertHtml);
                
                // Auto dismiss after 3 seconds
                setTimeout(function() {
                    $('.alert').alert('close');
                }, 3000);
            }
        });
        
        /**
         * Helper function to get color class based on value
         */
        function getColorClass(value) {
            if (value >= 50) {
                return 'sentiment-positive';
            } else if (value >= 30) {
                return 'sentiment-neutral';
            } else {
                return 'sentiment-negative';
            }
        }
    </script>
</body>
</html>