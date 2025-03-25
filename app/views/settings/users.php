<!-- File: app/views/settings/users.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users & Permissions | Payment Recovery System</title>
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
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #4b5563;
        }
        
        .role-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
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
                            <a class="nav-link active" href="index.php?route=settings/users">
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
                        <h5 class="text-primary"><?php echo ucfirst($organization['plan'] ?? 'Standard'); ?></h5>
                        <p class="card-text small text-muted">
                            <?php 
                            $userLimits = [
                                'standard' => 'Up to 5 users',
                                'premium' => 'Up to 15 users',
                                'enterprise' => 'Unlimited users'
                            ];
                            echo $userLimits[$organization['plan'] ?? 'standard'] ?? 'Limited users'; 
                            ?>
                        </p>
                        <a href="index.php?route=settings/billing" class="btn btn-sm btn-outline-primary">Upgrade Plan</a>
                    </div>
                </div>
            </div>
            
            <!-- Settings Content -->
            <div class="col-lg-9">
                <div class="card settings-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Users & Permissions</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus me-1"></i> Add User
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($users) && !empty($users)): ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar me-2">
                                                            <?php echo substr($user['name'], 0, 1); ?>
                                                        </div>
                                                        <div><?php echo htmlspecialchars($user['name']); ?></div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <?php if ($user['organization_role'] === 'owner'): ?>
                                                        <span class="badge bg-danger role-badge">Owner</span>
                                                    <?php elseif ($user['organization_role'] === 'admin'): ?>
                                                        <span class="badge bg-primary role-badge">Admin</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary role-badge">Member</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($user['status'] === 'active'): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?>
                                                </td>
                                                <td>
                                                    <?php if ($user['organization_role'] !== 'owner'): ?>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="userActions<?php echo $user['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                                Actions
                                                            </button>
                                                            <ul class="dropdown-menu" aria-labelledby="userActions<?php echo $user['id']; ?>">
                                                                <li>
                                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editRoleModal" data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars($user['name']); ?>" data-user-role="<?php echo $user['organization_role']; ?>">
                                                                        <i class="fas fa-user-tag me-2"></i> Edit Role
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#resetPasswordModal" data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars($user['name']); ?>">
                                                                        <i class="fas fa-key me-2"></i> Reset Password
                                                                    </a>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#removeUserModal" data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars($user['name']); ?>">
                                                                        <i class="fas fa-user-slash me-2"></i> Remove User
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Organization Owner</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">No users found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Audit Log</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            User activity audit log is available on the Premium and Enterprise plans.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="index.php?route=settings/add-user" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="form-text">User will receive an invitation email at this address.</div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="index.php?route=settings/update-user-role" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="user_id" id="editRoleUserId">
                    <div class="modal-body">
                        <p>Change role for <strong id="editRoleUserName"></strong>:</p>
                        <div class="mb-3">
                            <select class="form-select" name="role" id="editRoleSelect">
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset User Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="index.php?route=settings/reset-user-password" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="user_id" id="resetPasswordUserId">
                    <div class="modal-body">
                        <p>Are you sure you want to reset the password for <strong id="resetPasswordUserName"></strong>?</p>
                        <p>This will send the user an email with a password reset link.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Remove User Modal -->
    <div class="modal fade" id="removeUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Remove User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="index.php?route=settings/remove-user" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="user_id" id="removeUserId">
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This action cannot be undone.
                        </div>
                        <p>Are you sure you want to remove <strong id="removeUserName"></strong> from your organization?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Remove User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Handle modal data
        document.addEventListener('DOMContentLoaded', function() {
            // Edit role modal
            var editRoleModal = document.getElementById('editRoleModal');
            if (editRoleModal) {
                editRoleModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var userId = button.getAttribute('data-user-id');
                    var userName = button.getAttribute('data-user-name');
                    var userRole = button.getAttribute('data-user-role');
                    
                    var userIdInput = editRoleModal.querySelector('#editRoleUserId');
                    var userNameSpan = editRoleModal.querySelector('#editRoleUserName');
                    var roleSelect = editRoleModal.querySelector('#editRoleSelect');
                    
                    userIdInput.value = userId;
                    userNameSpan.textContent = userName;
                    
                    // Set the current role
                    for (var i = 0; i < roleSelect.options.length; i++) {
                        if (roleSelect.options[i].value === userRole) {
                            roleSelect.selectedIndex = i;
                            break;
                        }
                    }
                });
            }
            
            // Reset password modal
            var resetPasswordModal = document.getElementById('resetPasswordModal');
            if (resetPasswordModal) {
                resetPasswordModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var userId = button.getAttribute('data-user-id');
                    var userName = button.getAttribute('data-user-name');
                    
                    var userIdInput = resetPasswordModal.querySelector('#resetPasswordUserId');
                    var userNameSpan = resetPasswordModal.querySelector('#resetPasswordUserName');
                    
                    userIdInput.value = userId;
                    userNameSpan.textContent = userName;
                });
            }
            
            // Remove user modal
            var removeUserModal = document.getElementById('removeUserModal');
            if (removeUserModal) {
                removeUserModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var userId = button.getAttribute('data-user-id');
                    var userName = button.getAttribute('data-user-name');
                    
                    var userIdInput = removeUserModal.querySelector('#removeUserId');
                    var userNameSpan = removeUserModal.querySelector('#removeUserName');
                    
                    userIdInput.value = userId;
                    userNameSpan.textContent = userName;
                });
            }
        });
    </script>
</body>
</html>