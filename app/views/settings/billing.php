<!-- File: app/views/settings/billing.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing & Subscription | Payment Recovery System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        /* Include the same base styles as other settings pages */
        body {
            background-color: #f3f4f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background-color: #1e293b;
            min-height: 100vh;
        }
        
        .settings-nav .nav-link {
            color: #4b5563;
            border-radius: 0;
            padding: 0.75rem 1.25rem;
            border-left: 3px solid transparent;
        }
        
        .settings-nav .nav-link.active {
            background-color: #f3f4f6;
            color: #2563eb;
            border-left-color: #2563eb;
            font-weight: 500;
        }
        
        .settings-nav .nav-link:hover:not(.active) {
            background-color: #f9fafb;
            border-left-color: #d1d5db;
        }
        
        .settings-nav .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
        .settings-card {
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .settings-section {
            margin-bottom: 2rem;
        }
        
        .settings-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        /* Plan card styles */
        .plan-card {
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            height: 100%;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .plan-card.active {
            border-color: #2563eb;
        }
        
        .plan-card .card-header {
            border-radius: 8px 8px 0 0;
            border-bottom: none;
            text-align: center;
            padding: 1.5rem 1rem;
        }
        
        .plan-card .price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
        }
        
        .plan-card .period {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .plan-card .feature-list {
            list-style-type: none;
            padding-left: 0;
            margin-bottom: 0;
        }
        
        .plan-card .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .plan-card .feature-list li:last-child {
            border-bottom: none;
        }
        
        .plan-card .feature-list i {
            color: #2563eb;
            margin-right: 0.5rem;
        }
        
        /* Usage progress bar styles */
        .usage-progress {
            height: 0.8rem;
            border-radius: 0.5rem;
        }
        
        .payment-method-card {
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }
        
        .payment-method-card:hover {
            border-color: #d1d5db;
            background-color: #f9fafb;
        }
        
        .payment-method-card.active {
            border-color: #2563eb;
            background-color: rgba(37, 99, 235, 0.05);
        }
        
        .card-logo {
            font-size: 1.5rem;
        }
        
        .card-logo i {
            margin-right: 0.5rem;
        }
        
        .invoice-table th, .invoice-table td {
            padding: 0.75rem 1rem;
        }
    </style>
</head>
<body>
    <!-- Include the same navbar and sidebar as dashboard -->
    
    <!-- Main Content -->
    <div class="container-fluid p-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Organization Settings</h1>
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
        
        <div class="row">
            <!-- Settings Navigation -->
            <div class="col-lg-3 mb-4">
                <div class="card settings-card">
                    <div class="card-body p-0">
                        <div class="settings-nav nav flex-column nav-pills">
                            <a class="nav-link" href="index.php?route=settings/general">
                                <i class="fas fa-cog"></i> General
                            </a>
                            <a class="nav-link" href="index.php?route=settings/branding">
                                <i class="fas fa-palette"></i> Branding
                            </a>
                            <a class="nav-link" href="index.php?route=settings/users">
                                <i class="fas fa-users"></i> Users & Permissions
                            </a>
                            <a class="nav-link" href="index.php?route=settings/api">
                                <i class="fas fa-code"></i> API & Webhooks
                            </a>
                            <a class="nav-link active" href="index.php?route=settings/billing">
                                <i class="fas fa-credit-card"></i> Billing & Subscription
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card settings-card mt-4">
                    <div class="card-body">
                        <h6 class="card-title">Current Plan</h6>
                        <h5 class="text-primary"><?php echo ucfirst($subscription['plan']); ?></h5>
                        <p class="card-text small text-muted">
                            Next billing date: <?php echo date('M j, Y', strtotime($subscription['next_billing_date'])); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Settings Content -->
            <div class="col-lg-9">
                <div class="card settings-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Subscription Usage</h5>
                    </div>
                    <div class="card-body">
                        <div class="settings-section">
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Recovery Attempts</span>
                                    <span>
                                        <?php 
                                        $usagePercent = min(100, ($usageReport['usage'][0]['messages_sent'] / $subscription['limits']['recovery_attempts']) * 100); 
                                        ?>
                                        <strong><?php echo $usageReport['usage'][0]['messages_sent']; ?></strong> / 
                                        <?php echo number_format($subscription['limits']['recovery_attempts']); ?>
                                    </span>
                                </div>
                                <div class="progress usage-progress">
                                    <div class="progress-bar <?php echo $usagePercent > 80 ? 'bg-warning' : 'bg-primary'; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $usagePercent; ?>%" 
                                         aria-valuenow="<?php echo $usagePercent; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Users</span>
                                    <span>
                                        <?php 
                                        $userCount = count($users);
                                        $userPercent = min(100, ($userCount / $subscription['limits']['users']) * 100); 
                                        ?>
                                        <strong><?php echo $userCount; ?></strong> / 
                                        <?php echo $subscription['limits']['users']; ?>
                                    </span>
                                </div>
                                <div class="progress usage-progress">
                                    <div class="progress-bar <?php echo $userPercent > 80 ? 'bg-warning' : 'bg-primary'; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $userPercent; ?>%" 
                                         aria-valuenow="<?php echo $userPercent; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                            </div>
                            
                            <div class="row g-4 mt-2">
                                <div class="col-md-4">
                                    <div class="card bg-light text-center">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted mb-1">Recovered Amount</h6>
                                            <h3 class="mb-0 text-success">
                                                $<?php echo number_format($usageReport['usage'][0]['recovered_amount'], 2); ?>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light text-center">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted mb-1">Recovery Rate</h6>
                                            <h3 class="mb-0 text-primary">
                                                <?php 
                                                $recoveryRate = $usageReport['usage'][0]['transactions_count'] > 0
                                                    ? round(($usageReport['usage'][0]['recovered_count'] / $usageReport['usage'][0]['transactions_count']) * 100, 1)
                                                    : 0;
                                                echo $recoveryRate . '%';
                                                ?>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light text-center">
                                        <div class="card-body">
                                            <h6 class="card-title text-muted mb-1">SMS Messages</h6>
                                            <h3 class="mb-0 text-primary">
                                                <?php echo number_format($usageReport['usage'][0]['sms_count']); ?>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card settings-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Subscription Plans</h5>
                    </div>
                    <div class="card-body">
                        <div class="settings-section">
                            <div class="row g-4">
                                <?php foreach ($plans as $planId => $plan): ?>
                                <div class="col-lg-4">
                                    <div class="card plan-card <?php echo $subscription['plan'] === $planId ? 'active' : ''; ?>">
                                        <div class="card-header bg-white">
                                            <h5><?php echo $plan['name']; ?></h5>
                                            <div class="price">
                                                $<?php echo $plan['price']; ?>
                                                <span class="period">/month</span>
                                            </div>
                                            <div class="text-muted small">
                                                Billed monthly
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <ul class="feature-list">
                                                <?php foreach ($plan['features'] as $feature): ?>
                                                <li><i class="fas fa-check"></i> <?php echo $feature; ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <div class="card-footer bg-white border-0 text-center">
                                            <?php if ($subscription['plan'] === $planId): ?>
                                            <button class="btn btn-primary disabled" disabled>Current Plan</button>
                                            <?php else: ?>
                                            <a href="index.php?route=settings/change-plan&plan=<?php echo $planId; ?>" class="btn btn-outline-primary">
                                                Switch to <?php echo $plan['name']; ?>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card settings-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Payment Method</h5>
                        <a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addPaymentMethodModal">
                            <i class="fas fa-plus"></i> Add Payment Method
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($subscription['payment_details'])): ?>
                        <div class="payment-method-card active">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="card-logo">
                                        <?php
                                        $details = json_decode($subscription['payment_details'], true);
                                        $cardBrand = $details['brand'] ?? 'Unknown';
                                        $cardIcon = 'fa-credit-card';
                                        
                                        switch ($cardBrand) {
                                            case 'Visa':
                                                $cardIcon = 'fa-cc-visa';
                                                break;
                                            case 'Mastercard':
                                                $cardIcon = 'fa-cc-mastercard';
                                                break;
                                            case 'American Express':
                                                $cardIcon = 'fa-cc-amex';
                                                break;
                                            case 'Discover':
                                                $cardIcon = 'fa-cc-discover';
                                                break;
                                        }
                                        ?>
                                        <i class="fab <?php echo $cardIcon; ?>"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div><?php echo $cardBrand; ?> ending in <?php echo $details['last4'] ?? '****'; ?></div>
                                        <div class="text-muted small">
                                            Expires <?php echo $details['exp_month'] ?? '**'; ?>/<?php echo $details['exp_year'] ?? '**'; ?>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <a href="#" class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#editPaymentMethodModal">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                    <a href="#" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No payment method on file. Please add a payment method to continue your subscription.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Billing History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($billingHistory)): ?>
                        <div class="table-responsive">
                            <table class="table invoice-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Invoice</th>
                                        <th>Plan</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($billingHistory as $invoice): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($invoice['payment_date'])); ?></td>
                                        <td>INV-<?php echo str_pad($invoice['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo ucfirst($invoice['plan']); ?></td>
                                        <td>$<?php echo number_format($invoice['amount'], 2); ?></td>
                                        <td><span class="badge bg-success">Paid</span></td>
                                        <td>
                                            <a href="index.php?route=settings/invoice&id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            No billing history available.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Payment Method Modal -->
    <div class="modal fade" id="addPaymentMethodModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Payment Method</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="index.php?route=settings/add-payment-method" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="card_number" class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" id="expiry_date" name="expiry_date" placeholder="MM/YY" required>
                            </div>
                            <div class="col-sm-6">
                                <label for="security_code" class="form-label">Security Code</label>
                                <input type="text" class="form-control" id="security_code" name="security_code" placeholder="123" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="cardholder_name" class="form-label">Cardholder Name</label>
                            <input type="text" class="form-control" id="cardholder_name" name="cardholder_name" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="default_payment_method" name="default_payment_method" checked>
                            <label class="form-check-label" for="default_payment_method">
                                Set as default payment method
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Payment Method</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Payment Method Modal -->
    <div class="modal fade" id="editPaymentMethodModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Payment Method</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="index.php?route=settings/update-payment-method" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_expiry_date" class="form-label">Expiry Date</label>
                            <input type="text" class="form-control" id="edit_expiry_date" name="expiry_date" placeholder="MM/YY" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Payment Method</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>