<?php
session_start();
include 'db_connect.php';

// SECURE FIX: Check for unique email, not just username
if (!isset($_SESSION['user_email'])) { header("Location: index.php"); exit(); }
$email = $_SESSION['user_email'];
$username = $_SESSION['user'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $salary = $_POST['salary'];
    
    // SECURE FIX: Update the database using the unique EMAIL
    $conn->query("UPDATE users SET salary='$salary' WHERE email='$email'");
    
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #0f172a; color: #fff; font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #1e293b; padding: 50px; border-radius: 24px; border: 1px solid #334155; width: 100%; max-width: 500px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .icon-circle { width: 80px; height: 80px; background: rgba(6, 182, 212, 0.1); color: #06b6d4; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 30px; border: 1px solid rgba(6, 182, 212, 0.3); }
        h1 { font-family: 'Montserrat', sans-serif; margin-bottom: 10px; }
        p { color: #94a3b8; margin-bottom: 30px; line-height: 1.5; }
        input { width: 100%; padding: 20px; background: #0f172a; border: 2px solid #334155; color: #fff; border-radius: 15px; font-size: 24px; text-align: center; outline: none; transition: 0.3s; font-weight: 700; }
        input:focus { border-color: #06b6d4; box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.15); }
        button { width: 100%; padding: 18px; background: linear-gradient(90deg, #06b6d4, #3b82f6); color: #fff; border: none; border-radius: 15px; font-weight: 700; font-size: 16px; cursor: pointer; margin-top: 30px; transition: 0.3s; }
        button:hover { transform: translateY(-3px); }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-circle"><i class="fa-solid fa-rocket"></i></div>
        <h1>Welcome, <?= htmlspecialchars($username) ?>!</h1>
        <p>Before we start, let's set your <strong>Monthly Budget Goal</strong>. This helps our AI track your savings effectively.</p>
        <form method="POST">
            <label style="display:block; color:#06b6d4; font-size:12px; font-weight:700; margin-bottom:10px; text-transform:uppercase;">Monthly Income / Budget</label>
            <input type="number" name="salary" placeholder="₹ 0.00" required autofocus>
            <button type="submit">Go to Dashboard <i class="fa-solid fa-arrow-right"></i></button>
        </form>
    </div>
</body>
</html>