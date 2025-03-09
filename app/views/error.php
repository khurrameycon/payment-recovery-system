<!DOCTYPE html>
<html>
<head>
    <title>Error | Payment Recovery System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="alert alert-danger">
            <h4>Error</h4>
            <p><?php echo isset($error) ? htmlspecialchars($error) : 'An unknown error occurred'; ?></p>
        </div>
        <a href="javascript:history.back()" class="btn btn-primary">Go Back</a>
    </div>
</body>
</html>