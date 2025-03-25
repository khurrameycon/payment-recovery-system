<?php
// File: app/views/settings/api.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Settings | Payment Recovery System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <style>
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
            <h1 class="h3 mb-0 text-gray-800">API Settings</h1>
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
                            <a class="nav-link active" href="index.php?route=settings/api">
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
                        <h6 class="card-title">API Status</h6>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-success me-2">Active</span>
                            <span>Your API is working properly</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Settings Content -->
            <div class="col-lg-9">
                <!-- API Tokens Card -->
                <div class="card settings-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">API Tokens</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTokenModal">
                            <i class="fas fa-plus me-1"></i> Create Token
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($apiTokens)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                You don't have any API tokens yet. Create one to start using the API.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Scopes</th>
                                            <th>Created</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($apiTokens as $token): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($token['name']); ?></td>
                                                <td>
                                                <?php if (isset($token['scopes']) && is_array($token['scopes'])): ?>
                                                <?php foreach ($token['scopes'] as $scope): ?>
                                                    <span class="badge bg-primary me-1"><?php echo $scope; ?></span>
                                                <?php endforeach; ?>
                                                <?php else: ?>
                                                <span class="badge bg-secondary">No scopes</span>
                                                <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($token['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($token['created_by_name']); ?></td>
                                                <td>
                                                    <form action="index.php?route=settings/revoke-token" method="post" onsubmit="return confirm('Are you sure you want to revoke this token? This cannot be undone.');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['new_token'])): ?>
                            <div class="alert alert-success mt-3">
                                <p><strong>Token created successfully!</strong></p>
                                <p>Make sure to copy your new token now. You won't be able to see it again.</p>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo $_SESSION['new_token']; ?>" id="newToken" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToken()">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <?php unset($_SESSION['new_token']); ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- API Documentation Card -->
                <div class="card settings-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">API Documentation</h5>
                    </div>
                    <div class="card-body">
                        <div class="settings-section">
                            <h6 class="settings-section-title">Authentication</h6>
                            <p>To authenticate API requests, include your API token in the Authorization header:</p>
                            <div class="bg-light p-3 rounded">
                                <code>Authorization: Bearer YOUR_API_TOKEN</code>
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h6 class="settings-section-title">API Endpoints</h6>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Endpoint</th>
                                        <th>Method</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>/api/v1/transactions</code></td>
                                        <td><span class="badge bg-success">GET</span></td>
                                        <td>Get list of failed transactions</td>
                                    </tr>
                                    <tr>
                                        <td><code>/api/v1/transactions/{id}</code></td>
                                        <td><span class="badge bg-primary">GET</span></td>
                                        <td>Get transaction details</td>
                                    </tr>
                                    <tr>
                                        <td><code>/api/v1/reminders</code></td>
                                        <td><span class="badge bg-warning text-dark">POST</span></td>
                                        <td>Send payment reminder</td>
                                    </tr>
                                    <tr>
                                        <td><code>/api/v1/recovery-links</code></td>
                                        <td><span class="badge bg-warning text-dark">POST</span></td>
                                        <td>Create recovery link</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <a href="#" class="btn btn-outline-primary">
                            <i class="fas fa-book me-1"></i> Full API Documentation
                        </a>
                    </div>
                </div>
                
                <!-- Webhooks Card -->
                <div class="card settings-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Webhooks</h5>
                        <button class="btn btn-primary btn-sm" disabled>
                            <i class="fas fa-plus me-1"></i> Add Webhook
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Webhooks are available on the Premium and Enterprise plans.
                            <a href="index.php?route=settings/billing" class="alert-link">Upgrade your plan</a> to access this feature.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Token Modal -->
    <div class="modal fade" id="createTokenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create API Token</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="index.php?route=settings/create-token" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="tokenName" class="form-label">Token Name</label>
                            <input type="text" class="form-control" id="tokenName" name="name" required placeholder="e.g., Dashboard Integration">
                            <div class="form-text">Give your token a descriptive name to identify its purpose.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Scopes</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="scopeRead" name="scopes[]" value="read" checked>
                                <label class="form-check-label" for="scopeRead">
                                    Read (View transactions and analytics)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="scopeWrite" name="scopes[]" value="write">
                                <label class="form-check-label" for="scopeWrite">
                                    Write (Create and update resources)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="scopeRecover" name="scopes[]" value="recover">
                                <label class="form-check-label" for="scopeRecover">
                                    Recover (Send reminders and create recovery links)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Token</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function copyToken() {
            var tokenInput = document.getElementById("newToken");
            tokenInput.select();
            tokenInput.setSelectionRange(0, 99999);
            document.execCommand("copy");
            
            var copyButton = tokenInput.nextElementSibling;
            copyButton.innerHTML = '<i class="fas fa-check"></i>';
            
            setTimeout(function() {
                copyButton.innerHTML = '<i class="fas fa-copy"></i>';
            }, 2000);
        }
    </script>
</body>
</html>