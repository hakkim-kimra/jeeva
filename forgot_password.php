<?php
session_start();
include 'db_connect.php';

// LOAD PHPMAILER (Pointing to your new files)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (isset($_POST['send_code'])) {
    $email = $conn->real_escape_string($_POST['email']);
    
    // Check if email exists in database
    $check = $conn->query("SELECT * FROM users WHERE email='$email'");

    if ($check->num_rows > 0) {
        $otp = rand(100000, 999999);
        // Save OTP to Database
        $conn->query("UPDATE users SET reset_token='$otp' WHERE email='$email'");
        
        // Save email to session for the next page
        $_SESSION['reset_email'] = $email;

        // --- SEND EMAIL LOGIC ---
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'hakkimkhan677@gmail.com'; // <--- PUT YOUR GMAIL HERE
            $mail->Password   = 'fcea tvzv jlll dqno';   // <--- PUT APP PASSWORD HERE
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('no-reply@fintrackpro.com', 'FinTrack Security');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Secure Reset Code';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; background: #f4f4f4;'>
                    <div style='background: #fff; padding: 20px; border-radius: 10px; text-align: center;'>
                        <h2 style='color: #06b6d4;'>Password Reset Request</h2>
                        <p>Use the code below to reset your FinTrackPro password.</p>
                        <h1 style='letter-spacing: 5px; background: #0f172a; color: #fff; padding: 10px; display: inline-block; border-radius: 5px;'>$otp</h1>
                        <p style='color: #999; font-size: 12px; margin-top: 20px;'>If you did not request this, please ignore this email.</p>
                    </div>
                </div>";

            $mail->send();
            
            // Success! Redirect to verify page
            echo "<script>
                alert('Secure code sent to your email!');
                window.location.href='reset_code.php';
            </script>";
            
        } catch (Exception $e) {
            echo "<script>alert('Message could not be sent. Mailer Error: {$mail->ErrorInfo}');</script>";
        }

    } else {
        echo "<script>alert('No account found with that email.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Recovery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Montserrat:wght@700;800&display=swap');
        body { background: #0f172a; font-family: 'Inter', sans-serif; height: 100vh; display: flex; justify-content: center; align-items: center; }
        
        .card {
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1); padding: 50px; border-radius: 30px;
            width: 100%; max-width: 500px; text-align: center; color: #fff;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        input { width: 100%; background: #0f172a; border: 1px solid #334155; padding: 15px; border-radius: 10px; color: #fff; margin-bottom: 20px; outline: none; }
        button { width: 100%; padding: 15px; background: #06b6d4; color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Forgot Password?</h1>
        <p>We will send a code to your registered email.</p>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit" name="send_code">Send Email</button>
        </form>
        <a href="index.php" style="display:block; margin-top:20px; color:#94a3b8; text-decoration:none;">Back to Login</a>
    </div>
</body>
</html>