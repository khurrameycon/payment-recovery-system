<?php
// File: app/views/settings/communication.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communication Settings | Payment Recovery System</title>
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
        
        /* Time slider specific styles */
        .time-slider {
            width: 100%;
        }
        
        .time-display {
            font-weight: bold;
            text-align: center;
        }
        
        .segment-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .segment-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background-color: #f9fafb;
            border-radius: 8px 8px 0 0;
        }
        
        .segment-body {
            padding: 1rem;
        }
        
        .segment-badge {
            position: absolute;
            top: -10px;
            right: 10px;
        }
        
        /* Range slider styles */
        .range-container {
            position: relative;
            width: 100%;
            height: 70px;
            padding-top: 26px;
        }
        
        .hours-scale {
            position: absolute;
            width: 100%;
            top: 6px;
            height: 20px;
            display: flex;
            justify-content: space-between;
        }
        
        .hour-mark {
            width: 1px;
            height: 8px;
            background-color: #cbd5e0;
        }
        
        .hour-label {
            font-size: 0.75rem;
            color: #64748b;
            transform: translateX(-50%);
        }
        
        .hour-range {
            position: relative;
            height: 30px;
        }
        
        .range-fill {
            position: absolute;
            height: 8px;
            top: 11px;
            background-color: #3b82f6;
            border-radius: 4px;
        }
        
        .range-handle {
            position: absolute;
            width: 20px;
            height: 20px;
            top: 5px;
            margin-left: -10px;
            background-color: white;
            border: 2px solid #3b82f6;
            border-radius: 50%;
            cursor: pointer;
        }
        
        .working-hours {
            background-color: rgba(34, 197, 94, 0.2);
        }
        
        .quiet-hours {
            background-color: rgba(249, 115, 22, 0.2);
        }
    </style>
</head>
<body>
    <!-- Include the same navbar and sidebar as dashboard -->
    
    <!-- Main Content -->
    <div class="container-fluid p-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Communication Settings</h1>
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
                            <a class="nav-link active" href="index.php?route=settings/communication">
                                <i class="fas fa-envelope"></i> Communication
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
                            $planFeatures = [
                                'standard' => 'Basic timing controls',
                                'premium' => 'Advanced segmentation',
                                'enterprise' => 'Custom strategies'
                            ];
                            echo $planFeatures[$organization['plan']] ?? 'Basic features'; 
                            ?>
                        </p>
                        <a href="index.php?route=settings/billing" class="btn btn-sm btn-outline-primary">Upgrade Plan</a>
                    </div>
                </div>
            </div>
            
            <!-- Settings Content -->
            <div class="col-lg-9">
                <form action="index.php?route=settings/update-communication" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <!-- Business Hours Card -->
                    <div class="card settings-card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Business Hours & Quiet Time</h5>
                        </div>
                        <div class="card-body">
                            <div class="settings-section">
                                <h6 class="settings-section-title">Business Hours</h6>
                                <p class="text-muted mb-3">Define when the system can send payment recovery reminders.</p>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="business_hours_start" class="form-label">Start Time</label>
                                        <select class="form-select" id="business_hours_start" name="business_hours_start">
                                            <?php for ($i = 0; $i < 24; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo ($settings['business_hours_start'] ?? 9) == $i ? 'selected' : ''; ?>>
                                                    <?php echo sprintf('%02d:00', $i); ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="business_hours_end" class="form-label">End Time</label>
                                        <select class="form-select" id="business_hours_end" name="business_hours_end">
                                            <?php for ($i = 0; $i < 24; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo ($settings['business_hours_end'] ?? 17) == $i ? 'selected' : ''; ?>>
                                                    <?php echo sprintf('%02d:00', $i); ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="range-container working-hours">
                                    <div class="hours-scale">
                                        <?php for ($i = 0; $i <= 24; $i += 6): ?>
                                            <div style="position: absolute; left: <?php echo ($i / 24) * 100; ?>%">
                                                <div class="hour-mark"></div>
                                                <div class="hour-label"><?php echo $i; ?>:00</div>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="hour-range">
                                        <?php 
                                        $start = ($settings['business_hours_start'] ?? 9) / 24 * 100;
                                        $end = ($settings['business_hours_end'] ?? 17) / 24 * 100;
                                        ?>
                                        <div class="range-fill" id="business-range-fill" style="left: <?php echo $start; ?>%; width: <?php echo $end - $start; ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="send_on_weekends" name="send_on_weekends" <?php echo isset($settings['send_on_weekends']) && $settings['send_on_weekends'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="send_on_weekends">
                                                Allow sending on weekends
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="respect_holidays" name="respect_holidays" <?php echo isset($settings['respect_holidays']) && $settings['respect_holidays'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="respect_holidays">
                                                Respect holidays (don't send on holidays)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="settings-section">
                                <h6 class="settings-section-title">Quiet Hours</h6>
                                <p class="text-muted mb-3">Define when the system should NOT send recovery reminders, even within business hours.</p>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="quiet_hours_start" class="form-label">Start Time</label>
                                        <select class="form-select" id="quiet_hours_start" name="quiet_hours_start">
                                            <?php for ($i = 0; $i < 24; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo ($settings['quiet_hours_start'] ?? 22) == $i ? 'selected' : ''; ?>>
                                                    <?php echo sprintf('%02d:00', $i); ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="quiet_hours_end" class="form-label">End Time</label>
                                        <select class="form-select" id="quiet_hours_end" name="quiet_hours_end">
                                            <?php for ($i = 0; $i < 24; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo ($settings['quiet_hours_end'] ?? 7) == $i ? 'selected' : ''; ?>>
                                                    <?php echo sprintf('%02d:00', $i); ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="range-container quiet-hours">
                                    <div class="hours-scale">
                                        <?php for ($i = 0; $i <= 24; $i += 6): ?>
                                            <div style="position: absolute; left: <?php echo ($i / 24) * 100; ?>%">
                                                <div class="hour-mark"></div>
                                                <div class="hour-label"><?php echo $i; ?>:00</div>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="hour-range">
                                        <?php 
                                        $start = ($settings['quiet_hours_start'] ?? 22) / 24 * 100;
                                        $end = ($settings['quiet_hours_end'] ?? 7) / 24 * 100;
                                        
                                        // Handle the overnight case (e.g., 22:00 to 07:00)
                                        $width = $end > $start ? $end - $start : (100 - $start) + $end;
                                        ?>
                                        <div class="range-fill" id="quiet-range-fill" style="left: <?php echo $start; ?>%; width: <?php echo $width; ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Quiet hours apply to all customers in their local timezone. For example, if quiet hours are set to 10:00 PM - 7:00 AM, the system won't send reminders during those hours in each customer's timezone.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Segment Strategies Card -->
                    <div class="card settings-card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Segment Communication Strategies</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">Configure how the system communicates with different customer segments.</p>
                            
                            <?php foreach ($segmentStrategies as $strategy): 
                                // Define badge color for each segment
                                $badgeClass = 'bg-primary';
                                switch ($strategy['segment']) {
                                    case 'vip':
                                        $badgeClass = 'bg-success';
                                        break;
                                    case 'high_priority':
                                        $badgeClass = 'bg-info';
                                        break;
                                    case 'nurture':
                                        $badgeClass = 'bg-warning';
                                        break;
                                    case 'low_priority':
                                        $badgeClass = 'bg-secondary';
                                        break;
                                }
                            ?>
                            <div class="segment-card">
                                <span class="segment-badge badge <?php echo $badgeClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $strategy['segment'])); ?></span>
                                <div class="segment-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><?php echo ucfirst(str_replace('_', ' ', $strategy['segment'])); ?> Segment</h6>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="strategy_active_<?php echo $strategy['id']; ?>" name="strategies[<?php echo $strategy['id']; ?>][active]" <?php echo $strategy['active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="strategy_active_<?php echo $strategy['id']; ?>">Active</label>
                                    </div>
                                </div>
                                <div class="segment-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Primary Channel</label>
                                            <select class="form-select" name="strategies[<?php echo $strategy['id']; ?>][primary_channel]">
                                                <option value="email" <?php echo $strategy['primary_channel'] === 'email' ? 'selected' : ''; ?>>Email</option>
                                                <option value="sms" <?php echo $strategy['primary_channel'] === 'sms' ? 'selected' : ''; ?>>SMS</option>
                                                <option value="whatsapp" <?php echo $strategy['primary_channel'] === 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Fallback Channel</label>
                                            <select class="form-select" name="strategies[<?php echo $strategy['id']; ?>][fallback_channel]">
                                                <option value="">None</option>
                                                <option value="email" <?php echo $strategy['fallback_channel'] === 'email' ? 'selected' : ''; ?>>Email</option>
                                                <option value="sms" <?php echo $strategy['fallback_channel'] === 'sms' ? 'selected' : ''; ?>>SMS</option>
                                                <option value="whatsapp" <?php echo $strategy['fallback_channel'] === 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Maximum Attempts</label>
                                            <select class="form-select" name="strategies[<?php echo $strategy['id']; ?>][max_attempts]">
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                    <option value="<?php echo $i; ?>" <?php echo $strategy['max_attempts'] == $i ? 'selected' : ''; ?>>
                                                        <?php echo $i; ?> attempt<?php echo $i > 1 ? 's' : ''; ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Minimum Hours Between Attempts</label>
                                            <select class="form-select" name="strategies[<?php echo $strategy['id']; ?>][min_hours_between]">
                                                <option value="12" <?php echo $strategy['min_hours_between'] == 12 ? 'selected' : ''; ?>>12 hours</option>
                                                <option value="24" <?php echo $strategy['min_hours_between'] == 24 ? 'selected' : ''; ?>>24 hours</option>
                                                <option value="48" <?php echo $strategy['min_hours_between'] == 48 ? 'selected' : ''; ?>>48 hours</option>
                                                <option value="72" <?php echo $strategy['min_hours_between'] == 72 ? 'selected' : ''; ?>>72 hours</option>
                                                <option value="168" <?php echo $strategy['min_hours_between'] == 168 ? 'selected' : ''; ?>>1 week</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col">
                                            <label class="form-label">Preferred Time</label>
                                            <select class="form-select" name="strategies[<?php echo $strategy['id']; ?>][preferred_time]">
                                                <option value="business_hours" <?php echo $strategy['preferred_time'] === 'business_hours' ? 'selected' : ''; ?>>Within Business Hours</option>
                                                <option value="morning" <?php echo $strategy['preferred_time'] === 'morning' ? 'selected' : ''; ?>>Morning (9 AM - 12 PM)</option>
                                                <option value="afternoon" <?php echo $strategy['preferred_time'] === 'afternoon' ? 'selected' : ''; ?>>Afternoon (12 PM - 5 PM)</option>
                                                <option value="evening" <?php echo $strategy['preferred_time'] === 'evening' ? 'selected' : ''; ?>>Early Evening (5 PM - 8 PM)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Country-Specific Settings Card -->
                    <div class="card settings-card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Country-Specific Settings</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Configure country-specific communication rules.</p>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Country-specific communication rules are available on the Premium and Enterprise plans. Upgrade your plan to access these features.
                            </div>
                            
                            <button type="button" class="btn btn-outline-primary" <?php echo $organization['plan'] === 'standard' ? 'disabled' : ''; ?>>
                                <i class="fas fa-plus me-1"></i> Add Country Rule
                            </button>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Save Communication Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Update visual ranges when select boxes change
        document.getElementById('business_hours_start').addEventListener('change', updateBusinessRange);
        document.getElementById('business_hours_end').addEventListener('change', updateBusinessRange);
        document.getElementById('quiet_hours_start').addEventListener('change', updateQuietRange);
        document.getElementById('quiet_hours_end').addEventListener('change', updateQuietRange);
        
        function updateBusinessRange() {
            const start = document.getElementById('business_hours_start').value;
            const end = document.getElementById('business_hours_end').value;
            const startPercent = (start / 24) * 100;
            const endPercent = (end / 24) * 100;
            const width = endPercent - startPercent;
            
            const rangeFill = document.getElementById('business-range-fill');
            rangeFill.style.left = startPercent + '%';
            rangeFill.style.width = width + '%';
        }
        
        function updateQuietRange() {
            const start = document.getElementById('quiet_hours_start').value;
            const end = document.getElementById('quiet_hours_end').value;
            const startPercent = (start / 24) * 100;
            const endPercent = (end / 24) * 100;
            
            // Handle the overnight case (e.g., 22:00 to 07:00)
            const width = parseInt(end) > parseInt(start) ? endPercent - startPercent : (100 - startPercent) + endPercent;
            
            const rangeFill = document.getElementById('quiet-range-fill');
            rangeFill.style.left = startPercent + '%';
            rangeFill.style.width = width + '%';
        }
    </script>
</body>
</html>