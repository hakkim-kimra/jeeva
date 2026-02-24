<?php
session_start();
include 'db_connect.php';

// Security: Ensure the user came from the previous step
if (!isset($_SESSION['reset_email'])) { header("Location: forgot_password.php"); exit(); }
$email = $_SESSION['reset_email'];

if (isset($_POST['reset_pass'])) {
    $otp_input = $_POST['otp'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // 1. Validate Password Match
    if ($new_pass !== $confirm_pass) {
        echo "<script>alert('Passwords do not match!');</script>";
    } else {
        // 2. Verify OTP in Database
        $check = $conn->query("SELECT * FROM users WHERE email='$email' AND reset_token='$otp_input'");
        
        if ($check->num_rows > 0) {
            // 3. Hash New Password & Clear OTP (Security Best Practice)
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password='$hashed_pass', reset_token=NULL WHERE email='$email'");
            
            // 4. Success & Redirect
            session_unset(); // Clear session
            echo "<script>
                alert('Success! Your password has been updated securely.');
                window.location.href='index.php';
            </script>";
        } else {
            echo "<script>alert('Invalid OTP Code. Please try again.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Credentials | FinTrackPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same sleek styling as previous page */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Montserrat:wght@700;800&display=swap');
        body { background: #0f172a; font-family: 'Inter', sans-serif; height: 100vh; display: flex; justify-content: center; align-items: center; overflow: hidden; }
        .orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.4; }
        .orb-1 { width: 300px; height: 300px; background: #22c55e; top: -50px; right: -50px; }
        
        .card {
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1); padding: 50px; border-radius: 30px;
            width: 100%; max-width: 500px; text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5); z-index: 10;
        }

        .icon-box { width: 80px; height: 80px; background: rgba(34, 197, 94, 0.1); border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 25px; color: #22c55e; font-size: 32px; border: 1px solid rgba(34, 197, 94, 0.3); }
        
        h1 { font-family: 'Montserrat', sans-serif; color: #fff; margin-bottom: 10px; font-size: 24px; }
        p { color: #94a3b8; font-size: 14px; margin-bottom: 30px; }

        .input-group { position: relative; margin-bottom: 20px; }
        input { 
            width: 100%; background: #0f172a; border: 1px solid #334155; padding: 18px 20px 18px 50px;
            border-radius: 15px; color: #fff; font-size: 15px; outline: none; transition: 0.3s;
        }
        input:focus { border-color: #22c55e; box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15); }
        .input-icon { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: #64748b; }

        button {
            width: 100%; padding: 18px; background: linear-gradient(90deg, #22c55e, #10b981);
            color: #fff; border: none; border-radius: 15px; font-weight: 700; font-size: 15px;
            cursor: pointer; transition: 0.3s; margin-top: 10px;
        }
        button:hover { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(34, 197, 94, 0.4); }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>

    <div class="card">
        <div class="icon-box"><i class="fa-solid fa-shield-check"></i></div>
        <h1>Create New Password</h1>
        <p>Identity Verified for <b><?= htmlspecialchars($email) ?></b>.<br>Please set a strong new password.</p>
        
        <form method="POST">
            <div class="input-group">
                <i class="fa-solid fa-hashtag input-icon"></i>
                <input type="text" name="otp" placeholder="Enter 6-Digit OTP Code" required>
            </div>
            
            <div class="input-group">
                <i class="fa-solid fa-lock input-icon"></i>
                <input type="password" name="new_password" placeholder="New Password" required>
            </div>

            <div class="input-group">
                <i class="fa-solid fa-check-double input-icon"></i>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>

            <button type="submit" name="reset_pass">Update Security Credentials</button>
        </form>
    </div>
</body>
</html>