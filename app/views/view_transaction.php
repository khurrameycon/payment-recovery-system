<?php
// File: app/views/view_transaction.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Details | Payment Recovery System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        /* Base styles */
        body {
            background-color: #f3f4f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: -18px;
            top: 0;
            height: 100%;
            border-left: 2px dashed #e9ecef;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            left: -24px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #fff;
            border: 2px solid #6c757d;
        }
        
        .timeline-item.success::after {
            border-color: #28a745;
            background-color: #d4edda;
        }
        
        .timeline-item.warning::after {
            border-color: #ffc107;
            background-color: #fff3cd;
        }
        
        .timeline-item.info::after {
            border-color: #17a2b8;
            background-color: #d1ecf1;
        }
        
        .timeline-item.danger::after {
            border-color: #dc3545;
            background-color: #f8d7da;
        }
        
        .timeline-date {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .timeline-content {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background-color: #f8f9fa;
        }
        
        .detail-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            margin-right: 1rem;
        }
        
        .recovery-link-input {
            background-color: #f8f9fa;
            font-size: 0.875rem;
        }
        
        .recovery-link-input:focus {
            background-color: #fff;
        }
        
        .action-btn {
            border-radius: 50rem;
            padding: 0.25rem 0.75rem;
        }
        
        .badge {
            padding: 0.35em 0.65em;
        }
        
        .recovery-status {
            position: relative;
            padding-left: 1.5rem;
        }
        
        .recovery-status::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
        }
        
        .recovery-status.pending::before {
            background-color: #ffc107;
        }
        
        .recovery-status.recovered::before {
            background-color: #28a745;
        }
        
        .recovery-status.expired::before {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Page Content -->
    <div class="container-fluid p-4">
        <!-- Page Heading -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php?route=dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="index.php?route=failed-transactions">Failed Transactions</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Transaction Details</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0 text-gray-800">Transaction Details</h1>
            </div>
            <div>
                <a href="index.php?route=failed-transactions" class="btn btn-outline-primary me-2">
                    <i class="fas fa-arrow-left me-1"></i> Back to List
                </a>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-bolt me-1"></i> Actions
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="index.php?route=send-reminder&id=<?php echo $transaction['id']; ?>&channel=email">Send Email Reminder</a></li>
                        <li><a class="dropdown-item" href="index.php?route=send-reminder&id=<?php echo $transaction['id']; ?>&channel=sms">Send SMS Reminder</a></li>
                        <li><a class="dropdown-item" href="index.php?route=send-reminder&id=<?php echo $transaction['id']; ?>&channel=whatsapp">Send WhatsApp Reminder</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="index.php?route=generate-report&id=<?php echo $transaction['id']; ?>">Generate Report</a></li>
                    </ul>
                </div>
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
        
        <!-- Main Content Row -->
        <div class="row g-4">
            <!-- Transaction Information -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold">Transaction Information</h6>
                        <span class="recovery-status <?php echo $transaction['recovery_status']; ?>">
                            <?php echo ucfirst($transaction['recovery_status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="d-flex mb-3">
                                    <div class="detail-icon">
                                        <i class="fas fa-hashtag"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Transaction Reference</div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($transaction['transaction_reference']); ?></div>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="detail-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Transaction Date</div>
                                        <div class="fw-bold"><?php echo date('F d, Y h:i A', strtotime($transaction['transaction_date'])); ?></div>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="detail-icon" style="background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Failure Reason</div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($transaction['failure_reason']); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex mb-3">
                                    <div class="detail-icon" style="background-color: rgba(40, 167, 69, 0.1); color: #28a745;">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Amount</div>
                                        <div class="h4 mb-0 fw-bold">$<?php echo number_format($transaction['amount'], 2); ?></div>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="detail-icon" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107;">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Payment Method</div>
                                        <div class="fw-bold">Credit Card</div>
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div class="detail-icon" style="background-color: rgba(23, 162, 184, 0.1); color: #17a2b8;">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Category</div>
                                        <div class="fw-bold">
                                            <?php
                                            $category = '';
                                            if ($transaction['amount'] >= 500) {
                                                $category = 'High Value';
                                                $badgeClass = 'bg-success';
                                            } else if ($transaction['amount'] >= 100) {
                                                $category = 'Medium Value';
                                                $badgeClass = 'bg-info';
                                            } else {
                                                $category = 'Low Value';
                                                $badgeClass = 'bg-primary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $category; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recovery Link Section -->
                        <div class="mt-4">
                            <h6 class="mb-3">Recovery Link</h6>
                            <?php if ($recoveryLink): ?>
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control recovery-link-input" value="<?php echo htmlspecialchars($recoveryLink['recovery_link']); ?>" id="recoveryLinkInput" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyRecoveryLink()">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="small text-muted mb-1">Status</div>
                                        <span class="badge bg-<?php echo $recoveryLink['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($recoveryLink['status']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="small text-muted mb-1">Created</div>
                                        <div><?php echo date('M d, Y', strtotime($recoveryLink['created_at'] ?? $transaction['transaction_date'])); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="small text-muted mb-1">Expires</div>
                                        <div><?php echo date('M d, Y', strtotime($recoveryLink['expiry_date'])); ?></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    No recovery link has been generated for this transaction.
                                </div>
                                <a href="index.php?route=create-recovery-link&id=<?php echo $transaction['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-link me-1"></i> Generate Recovery Link
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Communication Timeline -->
                <div class="card mt-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold">Communication Timeline</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($communications)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                No communication attempts have been made for this transaction.
                            </div>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach ($communications as $comm): 
                                    $itemClass = '';
                                    $iconClass = 'fas fa-paper-plane text-primary';
                                    
                                    if ($comm['status'] == 'sent') {
                                        $itemClass = 'info';
                                        $iconClass = 'fas fa-paper-plane text-primary';
                                    } else if ($comm['status'] == 'opened') {
                                        $itemClass = 'warning';
                                        $iconClass = 'fas fa-envelope-open text-warning';
                                    } else if ($comm['status'] == 'clicked') {
                                        $itemClass = 'success';
                                        $iconClass = 'fas fa-mouse-pointer text-success';
                                    } else if ($comm['status'] == 'scheduled') {
                                        $itemClass = '';
                                        $iconClass = 'fas fa-clock text-secondary';
                                    }
                                ?>
                                <div class="timeline-item <?php echo $itemClass; ?>">
                                    <div class="timeline-date mb-1">
                                        <?php echo date('M d, Y h:i A', strtotime($comm['sent_at'] ?? $comm['scheduled_at'])); ?>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="d-flex align-items-center">
                                            <i class="<?php echo $iconClass; ?> me-2"></i>
                                            <div>
                                                <strong>
                                                    <?php 
                                                    $action = '';
                                                    if ($comm['status'] == 'scheduled') {
                                                        $action = 'Reminder scheduled';
                                                    } else if ($comm['status'] == 'sent') {
                                                        $action = ucfirst($comm['channel']) . ' reminder sent';
                                                    } else if ($comm['status'] == 'opened') {
                                                        $action = ucfirst($comm['channel']) . ' was opened';
                                                    } else if ($comm['status'] == 'clicked') {
                                                        $action = 'Recovery link was clicked';
                                                    }
                                                    echo $action;
                                                    ?>
                                                </strong>
                                                <div>
                                                    <span class="badge bg-<?php 
                                                        echo $comm['channel'] == 'email' ? 'primary' : 
                                                            ($comm['channel'] == 'sms' ? 'success' : 'info'); 
                                                    ?> me-2">
                                                        <?php echo ucfirst($comm['channel']); ?>
                                                    </span>
                                                    <?php echo str_replace('_', ' ', ucfirst($comm['message_template'])); ?>
                                                </div>
                                            </div>
                                            <div class="ms-auto">
                                                <?php if ($comm['status'] == 'opened' || $comm['status'] == 'clicked'): ?>
                                                <span class="badge bg-light text-dark">
                                                    <?php
                                                    $eventTime = $comm['status'] == 'opened' ? $comm['opened_at'] : $comm['clicked_at'];
                                                    $timeDiff = strtotime($eventTime) - strtotime($comm['sent_at']);
                                                    $hours = floor($timeDiff / 3600);
                                                    $minutes = floor(($timeDiff % 3600) / 60);
                                                    
                                                    if ($hours > 0) {
                                                        echo "after {$hours}h {$minutes}m";
                                                    } else {
                                                        echo "after {$minutes}m";
                                                    }
                                                    ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#sendReminderModal">
                            <i class="fas fa-plus me-1"></i> Send New Reminder
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Customer Information -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold">Customer Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="avatar-circle mx-auto mb-3" style="width: 80px; height: 80px; background-color: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user fa-2x text-secondary"></i>
                            </div>
                            <h5 class="mb-0"><?php echo $customer['first_name'] ?? 'Customer'; ?> <?php echo $customer['last_name'] ?? ''; ?></h5>
                            <div class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="small text-muted mb-2">Contact Information</div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-envelope text-primary me-3"></i>
                                <div><?php echo htmlspecialchars($customer['email']); ?></div>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-phone text-primary me-3"></i>
                                <div><?php echo $customer['phone'] ? htmlspecialchars($customer['phone']) : 'Not available'; ?></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="small text-muted mb-2">Customer Segment</div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users text-primary me-3"></i>
                                <div>
                                    <?php
                                    $segmentBadge = 'bg-primary';
                                    if (isset($customer['segment'])) {
                                        if ($customer['segment'] == 'premium') {
                                            $segmentBadge = 'bg-success';
                                        } else if ($customer['segment'] == 'standard') {
                                            $segmentBadge = 'bg-info';
                                        }
                                    }
                                    ?>
                                    <span class="badge <?php echo $segmentBadge; ?>">
                                        <?php echo ucfirst($customer['segment'] ?? 'standard'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Send Reminder Modal -->
    <div class="modal fade" id="sendReminderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Reminder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="index.php?route=send-reminder" method="post" id="reminderForm">
                        <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Select Channel</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="channel" id="emailChannel" value="email" checked autocomplete="off">
                                <label class="btn btn-outline-primary" for="emailChannel">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                                
                                <input type="radio" class="btn-check" name="channel" id="smsChannel" value="sms" autocomplete="off">
                                <label class="btn btn-outline-primary" for="smsChannel">
                                    <i class="fas fa-sms me-2"></i>SMS
                                </label>
                                
                                <input type="radio" class="btn-check" name="channel" id="whatsappChannel" value="whatsapp" autocomplete="off">
                                <label class="btn btn-outline-primary" for="whatsappChannel">
                                    <i class="fab fa-whatsapp me-2"></i>WhatsApp
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Select Template</label>
                            <select class="form-select" name="template">
                                <option value="reminder_1">First Reminder</option>
                                <option value="reminder_2">Second Reminder</option>
                                <option value="reminder_3">Final Reminder</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="reminderForm" class="btn btn-primary">Send Reminder</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Copy recovery link
        function copyRecoveryLink() {
            const copyText = document.getElementById("recoveryLinkInput");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);
            
            // Show feedback
            const button = copyText.nextElementSibling;
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 2000);
        }
    </script>
</body>
</html>