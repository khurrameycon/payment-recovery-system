<?php
// File: app/views/advanced_analytics.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Analytics | Payment Recovery System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- DateRangePicker -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <!-- Custom CSS -->
    <style>
        /* Include the same styles as in the dashboard, plus: */
        .filter-card {
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.08);
            background-color: #f8f9fa;
        }
        
        .chart-card {
            height: 100%;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .date-filter .form-control {
            border-radius: 50rem;
        }
        
        .metric-tile {
            background-color: #fff;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            transition: all 0.2s;
            height: 100%;
        }
        
        .metric-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }
        
        .metric-tile .value {
            font-size: 2rem;
            font-weight: 600;
        }
        
        .metric-tile .label {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .metric-tile .trend {
            font-size: 0.875rem;
        }
        
        .trend.up {
            color: #28a745;
        }
        
        .trend.down {
            color: #dc3545;
        }
        
        .insight-card {
            border-left: 4px solid;
            transition: all 0.2s;
        }
        
        .insight-card:hover {
            transform: translateX(5px);
        }
        
        .insight-card.primary {
            border-color: #2563eb;
        }
        
        .insight-card.success {
            border-color: #16a34a;
        }
        
        .insight-card.warning {
            border-color: #eab308;
        }
        
        .segmented-button .btn {
            border-radius: 0;
        }
        
        .segmented-button .btn:first-child {
            border-top-left-radius: 0.375rem;
            border-bottom-left-radius: 0.375rem;
        }
        
        .segmented-button .btn:last-child {
            border-top-right-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }
    </style>
</head>
<body>
    <!-- Include the same sidebar and top nav as the dashboard -->
    
    <!-- Page Content -->
    <div class="container-fluid p-4">
        <!-- Page Heading -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Advanced Analytics</h1>
            <div class="segmented-button btn-group" role="group">
                <button type="button" class="btn btn-outline-primary active">Last 30 Days</button>
                <button type="button" class="btn btn-outline-primary">Last Quarter</button>
                <button type="button" class="btn btn-outline-primary">This Year</button>
                <button type="button" class="btn btn-outline-primary">Custom</button>
            </div>
        </div>
        
        <!-- Filter Row -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card filter-card">
                    <div class="card-body py-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <div class="date-filter">
                                    <div class="input-group">
                                        <span class="input-group-text bg-transparent border-end-0">
                                            <i class="fas fa-calendar"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0" id="dateRange" value="03/01/2025 - 03/15/2025">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="channelFilter">
                                    <option value="all">All Channels</option>
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                    <option value="whatsapp">WhatsApp</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="segmentFilter">
                                    <option value="all">All Segments</option>
                                    <option value="high_value">High Value</option>
                                    <option value="medium_value">Medium Value</option>
                                    <option value="low_value">Low Value</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100">Apply Filters</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Key Metrics Row -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="metric-tile">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="label">Average Recovery Time</div>
                        <i class="fas fa-clock text-primary"></i>
                    </div>
                    <div class="value"><?php echo $avgTimeToRecovery ?? '36'; ?> hours</div>
                    <div class="trend up">
                        <i class="fas fa-arrow-down me-1"></i>12% faster
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-tile">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="label">Best Performing Channel</div>
                        <i class="fas fa-crown text-warning"></i>
                    </div>
                    <div class="value">Email</div>
                    <div class="trend up">
                        <i class="fas fa-arrow-up me-1"></i>68% recovery rate
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-tile">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="label">Best Performing Day</div>
                        <i class="fas fa-calendar-check text-success"></i>
                    </div>
                    <div class="value">Tuesday</div>
                    <div class="trend up">
                        <i class="fas fa-arrow-up me-1"></i>15% higher than average
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="metric-tile">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="label">Best Performing Time</div>
                        <i class="fas fa-hourglass-half text-info"></i>
                    </div>
                    <div class="value">10:00 AM</div>
                    <div class="trend up">
                        <i class="fas fa-arrow-up me-1"></i>22% higher than average
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-xl-8">
                <div class="card chart-card">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold">Recovery Rate by Time of Day</h6>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary active">Hourly</button>
                            <button type="button" class="btn btn-outline-secondary">Daily</button>
                            <button type="button" class="btn btn-outline-secondary">Weekly</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="timeOfDayChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card chart-card">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold">Recovery Rate by Segment</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="segmentChart"></canvas>
                        </div>
                        <div class="mt-4">
                            <?php foreach ($segmentStats as $segment): ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small text-capitalize"><?php echo str_replace('_', ' ', $segment['segment']); ?></span>
                                    <span class="small text-muted"><?php echo $segment['recovery_rate']; ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-<?php echo $segment['segment'] == 'high_value' ? 'success' : ($segment['segment'] == 'medium_value' ? 'info' : 'primary'); ?>" 
                                         role="progressbar" style="width: <?php echo $segment['recovery_rate']; ?>%" 
                                         aria-valuenow="<?php echo $segment['recovery_rate']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Secondary Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-xl-6">
                <div class="card chart-card">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold">Recovery Rate by Day of Week</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="dayOfWeekChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card chart-card">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold">Open & Click Rates by Channel</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="engagementChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Insights Row -->
        <div class="row g-4">
            <div class="col-12">
                <h5 class="mb-3">Smart Insights</h5>
            </div>
            <div class="col-md-4">
                <div class="card insight-card primary mb-3">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-lightbulb text-primary me-2"></i>
                            Time Optimization
                        </h6>
                        <p class="card-text">Send your reminders between 9-11 AM on Tuesdays and Wednesdays to increase recovery rates by up to 24%.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card insight-card success mb-3">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-users text-success me-2"></i>
                            Segment Focus
                        </h6>
                        <p class="card-text">High value customers are responding best to the second reminder. Consider a more personalized approach for this segment.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card insight-card warning mb-3">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-bell text-warning me-2"></i>
                            Channel Strategy
                        </h6>
                        <p class="card-text">Email performs better for high value transactions, while SMS shows better results for lower value recoveries.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Moment.js -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <!-- DateRangePicker -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
        // Initialize DateRangePicker
        $('#dateRange').daterangepicker({
            opens: 'left',
            startDate: moment().subtract(29, 'days'),
            endDate: moment(),
            ranges: {
               'Today': [moment(), moment()],
               'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
               'Last 7 Days': [moment().subtract(6, 'days'), moment()],
               'Last 30 Days': [moment().subtract(29, 'days'), moment()],
               'This Month': [moment().startOf('month'), moment().endOf('month')],
               'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });
        
        // Time of Day Chart
        const timeOfDayCtx = document.getElementById('timeOfDayChart').getContext('2d');
        const timeOfDayChart = new Chart(timeOfDayCtx, {
            type: 'line',
            data: {
                labels: ['12am', '2am', '4am', '6am', '8am', '10am', '12pm', '2pm', '4pm', '6pm', '8pm', '10pm'],
                datasets: [
                    {
                        label: 'Email',
                        data: [10, 8, 5, 7, 20, 30, 45, 40, 35, 25, 15, 10],
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'SMS',
                        data: [15, 10, 7, 8, 25, 40, 50, 45, 30, 28, 20, 15],
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Recovery Rate (%) by Hour'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Recovery Rate (%)'
                        }
                    }
                }
            }
        });
        
        // Segment Chart
        const segmentCtx = document.getElementById('segmentChart').getContext('2d');
        const segmentData = {
            labels: ['High Value', 'Medium Value', 'Low Value'],
            datasets: [{
                data: [75, 60, 40],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(23, 162, 184, 0.8)',
                    'rgba(0, 123, 255, 0.8)'
                ],
                borderWidth: 0
            }]
        };
        const segmentChart = new Chart(segmentCtx, {
            type: 'polarArea',
            data: segmentData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Day of Week Chart
        const dayOfWeekCtx = document.getElementById('dayOfWeekChart').getContext('2d');
        const dayOfWeekData = {
            labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            datasets: [{
                label: 'Recovery Rate (%)',
                data: [55, 70, 65, 60, 50, 30, 25],
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderRadius: 6
            }]
        };
        const dayOfWeekChart = new Chart(dayOfWeekCtx, {
            type: 'bar',
            data: dayOfWeekData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Recovery Rate (%)'
                        }
                    }
                }
            }
        });
        
        // Engagement Chart
        const engagementCtx = document.getElementById('engagementChart').getContext('2d');
        const engagementData = {
            labels: ['Email', 'SMS', 'WhatsApp'],
            datasets: [
                {
                    label: 'Open Rate',
                    data: [65, 90, 85],
                    backgroundColor: 'rgba(54, 162, 235, 0.7)'
                },
                {
                    label: 'Click Rate',
                    data: [40, 60, 55],
                    backgroundColor: 'rgba(255, 193, 7, 0.7)'
                },
                {
                    label: 'Recovery Rate',
                    data: [30, 45, 40],
                    backgroundColor: 'rgba(40, 167, 69, 0.7)'
                }
            ]
        };
        const engagementChart = new Chart(engagementCtx, {
            type: 'bar',
            data: engagementData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Rate (%)'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>