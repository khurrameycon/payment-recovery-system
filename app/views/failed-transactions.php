<?php
// File: app/views/enhanced_failed_transactions.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Failed Transactions | Payment Recovery System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .filter-card {
            margin-bottom: 1.5rem;
        }
        
        .filter-badge {
            background-color: #e9ecef;
            color: #495057;
            font-weight: normal;
            margin-right: 0.5rem;
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.85em;
            border-radius: 50rem;
        }
        
        .filter-badge .close {
            margin-left: 0.5rem;
            font-size: 0.85rem;
            cursor: pointer;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.25rem;
        }
        
        .action-button {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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
        
        .data-table {
            width: 100% !important;
        }
        
        .data-table thead th {
            font-weight: 600;
            color: #495057;
        }
        
        .transaction-status {
            position: relative;
            padding-left: 1rem;
        }
        
        .transaction-status::before {
            content: '';
            position: absolute;
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            background-color: currentColor;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .status-pending {
            color: #ffc107;
        }
        
        .status-recovered {
            color: #198754;
        }
        
        .status-expired {
            color: #dc3545;
        }
        
        .amount-column {
            font-weight: 600;
        }
        
        .pagination .page-link {
            color: #6c757d;
            border-color: #e9ecef;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }
        
        .datepicker-input {
            background-color: #fff;
        }
        
        .batch-actions {
            display: none;
        }
        
        .segment-badge {
            font-weight: normal;
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
                <h1 class="h3 mb-0">Failed Transactions</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php?route=dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Failed Transactions</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex">
                <button class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="fas fa-file-import me-1"></i> Import
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
        
        <!-- Stats Row -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="value"><?php echo number_format($stats['total_failed'] ?? 0); ?></div>
                    <div class="label">Total Failed Transactions</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card">
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="value"><?php echo number_format($stats['total_recovered'] ?? 0); ?></div>
                    <div class="label">Recovered Transactions</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card">
                    <div class="stats-icon bg-info bg-opacity-10 text-info">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="value"><?php echo number_format($stats['recovery_rate'] ?? 0, 1); ?>%</div>
                    <div class="label">Recovery Rate</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stats-card">
                    <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="value">$<?php echo number_format($stats['pending_amount'] ?? 0, 0); ?></div>
                    <div class="label">Pending Recovery Amount</div>
                </div>
            </div>
        </div>
        
        <!-- Filters Card -->
        <div class="card filter-card">
            <div class="card-header">
                <h5 class="card-title mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date Range</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <input type="text" class="form-control datepicker-input" id="dateRange" name="date_range" value="Last 30 days">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="recovered">Recovered</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Amount</label>
                        <select class="form-select" name="amount_range">
                            <option value="">Any Amount</option>
                            <option value="0-100">$0 - $100</option>
                            <option value="100-500">$100 - $500</option>
                            <option value="500-1000">$500 - $1,000</option>
                            <option value="1000-5000">$1,000 - $5,000</option>
                            <option value="5000+">$5,000+</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Customer Segment</label>
                        <select class="form-select" name="segment">
                            <option value="">All Segments</option>
                            <option value="vip">VIP</option>
                            <option value="high_priority">High Priority</option>
                            <option value="standard">Standard</option>
                            <option value="nurture">Nurture</option>
                            <option value="low_priority">Low Priority</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Apply Filters
                        </button>
                    </div>
                </form>
                
                <div class="active-filters mt-3" id="activeFilters">
                    <!-- Active filters will be displayed here -->
                </div>
            </div>
        </div>
        
        <!-- Data Table Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Transaction List</h5>
                <div>
                    <button class="btn btn-sm btn-outline-success me-2 batch-actions" id="batchRecoveryBtn">
                        <i class="fas fa-link me-1"></i> Generate Recovery Links
                    </button>
                    <button class="btn btn-sm btn-outline-primary me-2 batch-actions" id="batchReminderBtn">
                        <i class="fas fa-bell me-1"></i> Send Smart Reminders
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="exportBtn">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" data-export-type="csv">CSV</a></li>
                            <li><a class="dropdown-item" href="#" data-export-type="excel">Excel</a></li>
                            <li><a class="dropdown-item" href="#" data-export-type="pdf">PDF</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Reminders</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">No failed transactions found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input transaction-checkbox" type="checkbox" value="<?php echo $transaction['id']; ?>">
                                            </div>
                                        </td>
                                        <td><?php echo $transaction['id']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <?php echo htmlspecialchars($transaction['email']); ?>
                                                    <?php if (!empty($transaction['segment'])): ?>
                                                        <br>
                                                        <span class="badge bg-<?php 
                                                            echo $transaction['segment'] === 'vip' ? 'success' : 
                                                                ($transaction['segment'] === 'high_priority' ? 'info' : 
                                                                ($transaction['segment'] === 'low_priority' ? 'secondary' : 'primary')); 
                                                        ?> segment-badge">
                                                            <?php echo ucfirst(str_replace('_', ' ', $transaction['segment'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="amount-column"><?php echo '$' . number_format($transaction['amount'], 2); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['failure_reason']); ?></td>
                                        <td>
                                            <div class="transaction-status status-<?php echo $transaction['recovery_status']; ?>">
                                                <?php echo ucfirst($transaction['recovery_status']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($transaction['reminder_count'])):
                                                echo $transaction['reminder_count']; 
                                            else:
                                                echo "0";
                                            endif;
                                            ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="index.php?route=view-transaction&id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-outline-primary action-button" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="index.php?route=schedule-smart-reminder&id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-outline-warning action-button" title="Send Smart Reminder">
                                                    <i class="fas fa-bell"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-secondary action-button dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="index.php?route=schedule-smart-reminder&id=<?php echo $transaction['id']; ?>&channel=email">Email Reminder</a></li>
                                                    <li><a class="dropdown-item" href="index.php?route=schedule-smart-reminder&id=<?php echo $transaction['id']; ?>&channel=sms">SMS Reminder</a></li>
                                                    <li><a class="dropdown-item" href="index.php?route=schedule-smart-reminder&id=<?php echo $transaction['id']; ?>&channel=whatsapp">WhatsApp Reminder</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item" href="index.php?route=create-recovery-link&id=<?php echo $transaction['id']; ?>">Generate Recovery Link</a></li>
                                                </ul>
                                            </div>
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
    
    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Failed Transactions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="index.php?route=fetch-from-nmi" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text">From</span>
                                <input type="date" class="form-control" name="start_date" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                                <span class="input-group-text">To</span>
                                <input type="date" class="form-control" name="end_date" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Gateway</label>
                            <select class="form-select" name="gateway">
                                <option value="nmi">Network Merchants Inc. (NMI)</option>
                                <option value="stripe" disabled>Stripe (Coming Soon)</option>
                                <option value="paypal" disabled>PayPal (Coming Soon)</option>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            This will import all failed transactions from the selected date range that aren't already in the system.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Import Transactions</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Send Reminders Modal -->
    <div class="modal fade" id="batchReminderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Smart Reminders</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="batchReminderForm">
                        <input type="hidden" name="transaction_ids" id="reminderTransactionIds">
                        
                        <div class="mb-3">
                            <label class="form-label">Selected Transactions</label>
                            <div class="alert alert-info">
                                <span id="selectedCount">0</span> transactions selected
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Communication Channel</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="channel" id="smartChannel" value="smart" checked autocomplete="off">
                                <label class="btn btn-outline-primary" for="smartChannel">
                                    <i class="fas fa-magic me-1"></i> Smart Selection
                                </label>
                                
                                <input type="radio" class="btn-check" name="channel" id="emailChannel" value="email" autocomplete="off">
                                <label class="btn btn-outline-primary" for="emailChannel">
                                    <i class="fas fa-envelope me-1"></i> Email
                                </label>
                                
                                <input type="radio" class="btn-check" name="channel" id="smsChannel" value="sms" autocomplete="off">
                                <label class="btn btn-outline-primary" for="smsChannel">
                                    <i class="fas fa-sms me-1"></i> SMS
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Timing</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="timing" id="timingSmart" value="smart" checked>
                                <label class="form-check-label" for="timingSmart">
                                    Use optimal timing based on customer timezone
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="timing" id="timingNow" value="now">
                                <label class="form-check-label" for="timingNow">
                                    Send immediately
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="sendBatchRemindersBtn">Send Reminders</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Moment.js -->
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <!-- DateRangePicker -->
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const dataTable = $('.data-table').DataTable({
                pageLength: 25,
                ordering: true,
                searching: true,
                responsive: true,
                language: {
                    paginate: {
                        next: '<i class="fas fa-chevron-right"></i>',
                        previous: '<i class="fas fa-chevron-left"></i>'
                    }
                }
            });
            
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
            
            // Handle filter form submission
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                
                // Update active filters display
                updateActiveFilters();
                
                // Here you would normally make an AJAX request to get filtered data
                // For now, just show a message
                showMessage('Filters applied successfully');
            });
            
            // Handle select all checkbox
            $('#selectAll').on('change', function() {
                const isChecked = $(this).prop('checked');
                $('.transaction-checkbox').prop('checked', isChecked);
                updateBatchActions();
            });
            
            // Handle individual checkboxes
            $('.transaction-checkbox').on('change', function() {
                updateBatchActions();
                
                // If any checkbox is unchecked, uncheck "Select All"
                if (!$(this).prop('checked')) {
                    $('#selectAll').prop('checked', false);
                }
                
                // If all checkboxes are checked, check "Select All"
                if ($('.transaction-checkbox:checked').length === $('.transaction-checkbox').length) {
                    $('#selectAll').prop('checked', true);
                }
            });
            
            // Handle batch reminder button
            $('#batchReminderBtn').on('click', function() {
                const selectedIds = getSelectedTransactionIds();
                $('#reminderTransactionIds').val(selectedIds.join(','));
                $('#selectedCount').text(selectedIds.length);
                $('#batchReminderModal').modal('show');
            });
            
            // Handle sending batch reminders
            $('#sendBatchRemindersBtn').on('click', function() {
                // Get form data
                const formData = $('#batchReminderForm').serialize();
                
                // Here you would normally make an AJAX request to send reminders
                // For now, just show a message
                $('#batchReminderModal').modal('hide');
                showMessage('Smart reminders scheduled for ' + $('#selectedCount').text() + ' transactions');
            });
            
            // Handle batch recovery link generation
            $('#batchRecoveryBtn').on('click', function() {
                const selectedIds = getSelectedTransactionIds();
                
                // Here you would normally make an AJAX request to generate recovery links
                // For now, just show a message
                showMessage('Recovery links generated for ' + selectedIds.length + ' transactions');
            });
            
            // Handle refresh button
            $('#refreshBtn').on('click', function() {
                // Here you would normally reload the page or make an AJAX request
                // For demonstration, just show a message
                showMessage('Data refreshed successfully');
            });
            
            // Function to get selected transaction IDs
            function getSelectedTransactionIds() {
                const selectedIds = [];
                $('.transaction-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });
                return selectedIds;
            }
            
            // Function to update batch action buttons visibility
            function updateBatchActions() {
                const selectedCount = $('.transaction-checkbox:checked').length;
                if (selectedCount > 0) {
                    $('.batch-actions').show();
                } else {
                    $('.batch-actions').hide();
                }
            }
            
            // Function to update active filters display
            function updateActiveFilters() {
                const formData = $('#filterForm').serializeArray();
                let html = '';
                
                formData.forEach(function(field) {
                    if (field.value && field.name !== 'csrf_token') {
                        let label = $(`label[for="${field.name}"]`).text() || field.name;
                        let value = field.value;
                        
                        // Format date range
                        if (field.name === 'date_range') {
                            label = 'Date Range';
                        }
                        
                        // Format segment
                        if (field.name === 'segment' && value) {
                            value = value.charAt(0).toUpperCase() + value.slice(1).replace('_', ' ');
                        }
                        
                        html += `<span class="filter-badge">${label}: ${value} <span class="close" data-field="${field.name}">×</span></span>`;
                    }
                });
                
                $('#activeFilters').html(html);
            }
            
            // Handle removing filters
            $('#activeFilters').on('click', '.close', function() {
                const field = $(this).data('field');
                $(`[name="${field}"]`).val('');
                
                // Update filters display
                updateActiveFilters();
                
                // Here you would normally make an AJAX request to update results
                // For now, just show a message
                showMessage('Filter removed');
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
            
            // Initialize batch actions visibility
            updateBatchActions();
        });
    </script>
</body>
</html>