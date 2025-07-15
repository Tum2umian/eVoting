<?php
session_start();
require_once '../config/db.php';
require_once '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];
    
    if ($user_type === 'enumerator') {
        $table = 'Enumerator';
        $redirect = '../enumerator/dashboard.php';
    } else {
        $table = 'Voter';
        $redirect = '../voter/dashboard.php';
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user[$table.'ID'];
            $_SESSION['user_type'] = $user_type;
            $_SESSION['name'] = $user['Name'];
            
            header("Location: $redirect");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } catch (PDOException $e) {
        $error = "Login failed. Please try again.";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="text-center mb-4">Login</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="user_type" class="form-label">I am a:</label>
                <select class="form-select" id="user_type" name="user_type" required>
                    <option value="voter">Voter</option>
                    <option value="enumerator">Enumerator</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <p class="mt-3 text-center">Don't have an account? 
            <a href="register_voter.php">Register as Voter</a> or 
            <a href="register_enumerator.php">Register as Enumerator</a>
        </p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>