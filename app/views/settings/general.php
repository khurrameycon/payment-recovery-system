<!-- File: app/views/settings/general.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Settings | Payment Recovery System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        /* Include the same base styles as dashboard */
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
                            <a class="nav-link active" href="index.php?route=settings/general">
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
                            <a class="nav-link" href="index.php?route=settings/billing">
                                <i class="fas fa-credit-card"></i> Billing & Subscription
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card settings-card mt-4">
                    <div class="card-body">
                        <h6 class="card-title">Current Plan</h6>
                        <h5 class="text-primary"><?php echo ucfirst($organization['plan']); ?></h5>
                        <p class="card-text small text-muted">
                            <?php 
                            $planLimits = [
                                'standard' => 'Up to 1,000 recoveries/month',
                                'premium' => 'Up to 5,000 recoveries/month',
                                'enterprise' => 'Unlimited recoveries'
                            ];
                            echo $planLimits[$organization['plan']] ?? 'Limited plan'; 
                            ?>
                        </p>
                        <a href="index.php?route=settings/billing" class="btn btn-sm btn-outline-primary">Manage Subscription</a>
                    </div>
                </div>
            </div>
            
            <!-- Settings Content -->
            <div class="col-lg-9">
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">General Settings</h5>
                    </div>
                    <div class="card-body">
                        <form action="index.php?route=settings/update-general" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="settings-section">
                                <h6 class="settings-section-title">Organization Information</h6>
                                <div class="mb-3">
                                    <label for="name" class="form-label">Organization Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($organization['name']); ?>" required>
                                    <div class="form-text">This name will be displayed throughout the application.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="subdomain" class="form-label">Subdomain</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="subdomain" value="<?php echo htmlspecialchars($organization['subdomain']); ?>" readonly>
                                        <span class="input-group-text">.paymentrecovery.com</span>
                                    </div>
                                    <div class="form-text">Your unique subdomain for accessing the application.</div>
                                </div>
                            </div>
                            
                            <div class="settings-section">
                                <h6 class="settings-section-title">Contact Information</h6>
                                <div class="mb-3">
                                    <label for="support_email" class="form-label">Support Email</label>
                                    <input type="email" class="form-control" id="support_email" name="support_email" value="<?php echo htmlspecialchars($organization['settings']['support_email'] ?? ''); ?>">
                                    <div class="form-text">Email address for customer support inquiries.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="support_phone" class="form-label">Support Phone</label>
                                    <input type="text" class="form-control" id="support_phone" name="support_phone" value="<?php echo htmlspecialchars($organization['settings']['support_phone'] ?? ''); ?>">
                                    <div class="form-text">Phone number for customer support inquiries.</div>
                                </div>
                            </div>
                            
                            <div class="settings-section">
                                <h6 class="settings-section-title">Regional Settings</h6>
                                <div class="mb-3">
                                    <label for="timezone" class="form-label">Default Timezone</label>
                                    <select class="form-select" id="timezone" name="timezone">
                                        <?php
                                        $timezones = DateTimeZone::listIdentifiers();
                                        $currentTimezone = $organization['settings']['timezone'] ?? 'UTC';
                                        
                                        foreach ($timezones as $tz) {
                                            $selected = ($tz === $currentTimezone) ? 'selected' : '';
                                            echo "<option value=\"{$tz}\" {$selected}>{$tz}</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="form-text">Default timezone for the application.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="date_format" class="form-label">Date Format</label>
                                    <select class="form-select" id="date_format" name="date_format">
                                        <?php
                                        $dateFormats = [
                                            'Y-m-d' => date('Y-m-d') . ' (YYYY-MM-DD)',
                                            'm/d/Y' => date('m/d/Y') . ' (MM/DD/YYYY)',
                                            'd/m/Y' => date('d/m/Y') . ' (DD/MM/YYYY)',
                                            'M j, Y' => date('M j, Y') . ' (Month Day, Year)'
                                        ];
                                        
                                        $currentFormat = $organization['settings']['date_format'] ?? 'Y-m-d';
                                        
                                        foreach ($dateFormats as $format => $example) {
                                            $selected = ($format === $currentFormat) ? 'selected' : '';
                                            echo "<option value=\"{$format}\" {$selected}>{$example}</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="form-text">Default date format for the application.</div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>