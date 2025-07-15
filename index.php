<?php
require_once 'config/db.php';
require_once 'includes/header.php';
?>

<div class="jumbotron text-center">
    <h1 class="display-4">Welcome to VoteMS</h1>
    <p class="lead">A secure, mobile-optimized e-voting system for your organization</p>
    <hr class="my-4">
    <p>Cast your votes anytime, anywhere with our easy-to-use platform.</p>
    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
        <a href="auth/login.php" class="btn btn-primary btn-lg px-4 gap-3">Login</a>
        <a href="auth/register_voter.php" class="btn btn-outline-secondary btn-lg px-4">Register as Voter</a>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Secure Voting</h5>
                <p class="card-text">Our system ensures that each voter can only vote once per position, maintaining the integrity of your elections.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Mobile Optimized</h5>
                <p class="card-text">Designed to work perfectly on any device, from smartphones to desktop computers.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Real-time Results</h5>
                <p class="card-text">View election results immediately after voting ends, with clear charts and statistics.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>