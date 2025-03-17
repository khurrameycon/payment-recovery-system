<!-- File: app/views/settings/branding.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branding Settings | Payment Recovery System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        /* Include the same base styles as general settings */
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
        
        .color-preview {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #ccc;
            vertical-align: middle;
            margin-right: 8px;
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 100px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
            margin-top: 10px;
        }
        
        .email-preview {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
            background-color: #fff;
        }
        
        .email-preview-header {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px 4px 0 0;
            border-bottom: 1px solid #ddd;
        }
        
        .email-preview-footer {
            background-color: #f8f9fa;
            padding: 10px;
            margin-top: 15px;
            border-radius: 0 0 4px 4px;
            border-top: 1px solid #ddd;
            font-size: 0.85rem;
            color: #6c757d;
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
                            <a class="nav-link active" href="index.php?route=settings/branding">
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
                                'standard' => 'Basic white-labeling',
                                'premium' => 'Advanced branding & custom domain',
                                'enterprise' => 'Complete white-label solution'
                            ];
                            echo $planLimits[$organization['plan']] ?? 'Limited branding'; 
                            ?>
                        </p>
                        <a href="index.php?route=settings/billing" class="btn btn-sm btn-outline-primary">Upgrade Plan</a>
                    </div>
                </div>
            </div>
            
            <!-- Settings Content -->
            <div class="col-lg-9">
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Branding Settings</h5>
                    </div>
                    <div class="card-body">
                        <form action="index.php?route=settings/update-branding" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="settings-section">
                                <h6 class="settings-section-title">Company Information</h6>
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Company Name</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($organization['branding']['company_name'] ?? $organization['name']); ?>">
                                    <div class="form-text">This name will be displayed on recovery emails and payment pages.</div>
                                </div>
                            </div>
                            
                            <div class="settings-section">
                                <h6 class="settings-section-title">Visual Branding</h6>
                                <div class="mb-3">
                                    <label for="logo" class="form-label">Company Logo</label>
                                    <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                    
                                    <?php if (!empty($organization['branding']['logo_url'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($organization['branding']['logo_url']); ?>" alt="Company Logo" class="image-preview">
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="form-text">Recommended size: 200x50 pixels. Max size: 2MB.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="favicon" class="form-label">Favicon</label>
                                    <input type="file" class="form-control" id="favicon" name="favicon" accept="image/*,.ico">
                                    
                                    <?php if (!empty($organization['branding']['favicon_url'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($organization['branding']['favicon_url']); ?>" alt="Favicon" class="image-preview" style="max-width: 50px; max-height: 50px;">
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="form-text">Recommended size: 32x32 pixels. Max size: 1MB.</div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="primary_color" class="form-label">Primary Color</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <span class="color-preview" id="primary_color_preview" style="background-color: <?php echo htmlspecialchars($organization['branding']['primary_color'] ?? '#2563eb'); ?>"></span>
                                            </span>
                                            <input type="text" class="form-control" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($organization['branding']['primary_color'] ?? '#2563eb'); ?>" onchange="updateColorPreview('primary_color')">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="secondary_color" class="form-label">Secondary Color</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <span class="color-preview" id="secondary_color_preview" style="background-color: <?php echo htmlspecialchars($organization['branding']['secondary_color'] ?? '#4f46e5'); ?>"></span>
                                            </span>
                                            <input type="text" class="form-control" id="secondary_color" name="secondary_color" value="<?php echo htmlspecialchars($organization['branding']['secondary_color'] ?? '#4f46e5'); ?>" onchange="updateColorPreview('secondary_color')">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="accent_color" class="form-label">Accent Color</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <span class="color-preview" id="accent_color_preview" style="background-color: <?php echo htmlspecialchars($organization['branding']['accent_color'] ?? '#16a34a'); ?>"></span>
                                            </span>
                                            <input type="text" class="form-control" id="accent_color" name="accent_color" value="<?php echo htmlspecialchars($organization['branding']['accent_color'] ?? '#16a34a'); ?>" onchange="updateColorPreview('accent_color')">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="settings-section">
                                <h6 class="settings-section-title">Email Customization</h6>
                                <div class="mb-3">
                                    <label for="email_header" class="form-label">Email Header</label>
                                    <textarea class="form-control" id="email_header" name="email_header" rows="3"><?php echo htmlspecialchars($organization['branding']['email_header'] ?? ''); ?></textarea>
                                    <div class="form-text">Custom HTML to include at the top of all recovery emails.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email_footer" class="form-label">Email Footer</label>
                                    <textarea class="form-control" id="email_footer" name="email_footer" rows="3"><?php echo htmlspecialchars($organization['branding']['email_footer'] ?? ''); ?></textarea>
                                    <div class="form-text">Custom HTML to include at the bottom of all recovery emails.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="support_email" class="form-label">Support Email</label>
                                    <input type="email" class="form-control" id="support_email" name="support_email" value="<?php echo htmlspecialchars($organization['branding']['support_email'] ?? ''); ?>">
                                    <div class="form-text">Email address displayed for support inquiries.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="support_phone" class="form-label">Support Phone</label>
                                    <input type="text" class="form-control" id="support_phone" name="support_phone" value="<?php echo htmlspecialchars($organization['branding']['support_phone'] ?? ''); ?>">
                                    <div class="form-text">Phone number displayed for support inquiries.</div>
                                </div>
                                
                                <!-- Email Preview -->
                                <div class="mb-3">
                                    <label class="form-label">Email Preview</label>
                                    <div class="email-preview">
                                        <div class="email-preview-header">
                                            <?php if (!empty($organization['branding']['logo_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($organization['branding']['logo_url']); ?>" alt="Company Logo" style="max-height: 40px;">
                                            <?php else: ?>
                                                <h4 style="margin: 0; color: <?php echo htmlspecialchars($organization['branding']['primary_color'] ?? '#2563eb'); ?>">
                                                    <?php echo htmlspecialchars($organization['branding']['company_name'] ?? $organization['name']); ?>
                                                </h4>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($organization['branding']['email_header'])): ?>
                                                <div class="mt-2"><?php echo $organization['branding']['email_header']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="email-preview-body">
                                            <p>Dear Customer,</p>
                                            <p>We noticed your recent payment of <strong>$100.00</strong> was declined. Please click the button below to complete your payment.</p>
                                            
                                            <p style="text-align: center; margin: 25px 0;">
                                                <a href="#" style="display: inline-block; padding: 10px 20px; background-color: <?php echo htmlspecialchars($organization['branding']['primary_color'] ?? '#2563eb'); ?>; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;">
                                                    Complete Payment
                                                </a>
                                            </p>
                                            
                                            <p>If you have any questions, please don't hesitate to contact our support team.</p>
                                            <p>Thank you,<br>The <?php echo htmlspecialchars($organization['branding']['company_name'] ?? $organization['name']); ?> Team</p>
                                        </div>
                                        
                                        <div class="email-preview-footer">
                                            <?php if (!empty($organization['branding']['email_footer'])): ?>
                                                <?php echo $organization['branding']['email_footer']; ?>
                                            <?php else: ?>
                                                <p>
                                                    <strong><?php echo htmlspecialchars($organization['branding']['company_name'] ?? $organization['name']); ?></strong><br>
                                                    <?php if (!empty($organization['branding']['support_email'])): ?>
                                                        Email: <?php echo htmlspecialchars($organization['branding']['support_email']); ?><br>
                                                    <?php endif; ?>
                                                    <?php if (!empty($organization['branding']['support_phone'])): ?>
                                                        Phone: <?php echo htmlspecialchars($organization['branding']['support_phone']); ?>
                                                    <?php endif; ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($organization['plan'] === 'premium' || $organization['plan'] === 'enterprise'): ?>
                            <div class="settings-section">
                                <h6 class="settings-section-title">Custom Domain</h6>
                                <div class="mb-3">
                                    <label for="custom_domain" class="form-label">Custom Domain</label>
                                    <input type="text" class="form-control" id="custom_domain" name="custom_domain" value="<?php echo htmlspecialchars($organization['custom_domain'] ?? ''); ?>" placeholder="recover.yourdomain.com">
                                    <div class="form-text">Set up a custom domain for recovery pages. Additional configuration required.</div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">Custom Domain Configuration</h6>
                                    <p>To use a custom domain, you'll need to create a CNAME record pointing to <code>recover.paymentrecovery.com</code> in your DNS settings.</p>
                                    <p>Once configured, please allow up to 24 hours for DNS propagation.</p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Save Branding Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Update color preview when input changes
        function updateColorPreview(fieldId) {
            const input = document.getElementById(fieldId);
            const preview = document.getElementById(fieldId + '_preview');
            preview.style.backgroundColor = input.value;
        }
    </script>
</body>
</html>