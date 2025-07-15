<?php
require_once '../config/db.php';
require_once '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $regno = trim($_POST['regno']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Validate RegNo format (e.g., 2023/BSE/088/PS)
    if (!preg_match('/^\d{4}\/[A-Z]{3}\/\d{3}\/PS$/', $regno)) {
        $error = "Invalid Registration Number format. Please use format like 2023/BSE/088/PS";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO Voter (Name, Regno, Email, Password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $regno, $email, $password]);
            
            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            $error = "Email or Registration Number already exists or registration failed.";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="text-center mb-4">Register as Voter</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="regno" class="form-label">Registration Number</label>
                <input type="text" class="form-control" id="regno" name="regno" placeholder="e.g., 2023/BSE/088/PS" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>
        <p class="mt-3 text-center">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>