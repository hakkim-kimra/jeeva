<?php
session_start();
include 'db_connect.php';

// SECURE FIX: Strict Email Targeting
if (!isset($_SESSION['user_email'])) { header("Location: index.php"); exit(); }
$email = $_SESSION['user_email'];

$msg = "";
$msg_color = "";

// Handle Profile Updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_user = $conn->real_escape_string($_POST['username']);
    $new_email = $conn->real_escape_string($_POST['email']);
    $new_salary = $conn->real_escape_string($_POST['salary']);
    
    // UPDATE uniquely by current session email
    $sql = "UPDATE users SET username='$new_user', email='$new_email', salary='$new_salary' WHERE email='$email'";
    
    if ($conn->query($sql)) {
        $_SESSION['user'] = $new_user; 
        $_SESSION['user_email'] = $new_email; // Update session if email changes
        $email = $new_email; // Set variable to new email to load correct data below
        $msg = "Profile details updated successfully.";
        $msg_color = "#22c55e"; 
    } else {
        $msg = "Update failed. Email might already exist.";
        $msg_color = "#f43f5e"; 
    }
}

// Fetch Latest User Data safely
$user_query = $conn->query("SELECT * FROM users WHERE email='$email'");
$u = $user_query->fetch_assoc();
$username = $u['username'];

// Format "Last Login"
$last_login = $u['last_login'] ? date("F j, Y, g:i a", strtotime($u['last_login'])) : "Just now";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - FinTrackPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@500;700;800&display=swap');
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        /* FIXED SIDEBAR LAYOUT */
        body { background: #0f172a; color: #fff; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        .sidebar { position: fixed; top: 0; left: 0; width: 260px; height: 100vh; background: #1e293b; padding: 30px 20px; display: flex; flex-direction: column; border-right: 1px solid #334155; z-index: 100; overflow-y: auto; }
        .brand { font-family: 'Montserrat', sans-serif; font-size: 22px; font-weight: 800; margin-bottom: 50px; display: flex; align-items: center; gap: 12px; color: #fff; padding-left: 5px; }
        .brand i { color: #06b6d4; font-size: 24px; } .brand span { color: #06b6d4; }
        .nav-link { display: flex; align-items: center; gap: 15px; padding: 14px; color: #94a3b8; text-decoration: none; border-radius: 12px; margin-bottom: 8px; font-weight: 500; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover, .nav-link.active { background: linear-gradient(90deg, rgba(6, 182, 212, 0.1), transparent); color: #06b6d4; font-weight: 700; border-left: 4px solid #06b6d4; }
        .nav-link i { width: 20px; text-align: center; }
        .main { margin-left: 260px; padding: 40px; min-height: 100vh; display: flex; justify-content: center; }
        ::-webkit-scrollbar { width: 8px; } ::-webkit-scrollbar-track { background: #0f172a; } ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

        /* PROFILE STYLES */
        .profile-wrapper { width: 100%; max-width: 850px; padding-bottom: 50px; }
        .header-card { background: #1e293b; border: 1px solid #334155; border-radius: 20px; padding: 40px; display: flex; align-items: center; gap: 30px; margin-bottom: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .avatar { width: 110px; height: 110px; border-radius: 50%; border: 4px solid #06b6d4; background-image: url('https://ui-avatars.com/api/?name=<?= urlencode($username) ?>&background=06b6d4&color=fff&size=256&bold=true'); background-size: cover; box-shadow: 0 0 20px rgba(6, 182, 212, 0.2); }
        .user-details h1 { font-family: 'Montserrat', sans-serif; font-size: 32px; font-weight: 700; margin-bottom: 8px; }
        .user-details p { color: #94a3b8; font-size: 14px; display: flex; align-items: center; gap: 10px; margin-bottom: 5px; }
        .badge { background: linear-gradient(90deg, #eab308, #facc15); color: #000; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }

        .settings-card { background: #1e293b; border-radius: 20px; padding: 40px; border: 1px solid #334155; }
        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #334155; color: #fff; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .input-group { display: flex; flex-direction: column; gap: 10px; }
        label { color: #94a3b8; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        input { background: #0f172a; border: 1px solid #334155; padding: 16px; border-radius: 12px; color: #fff; font-size: 15px; outline: none; transition: 0.3s; font-family: 'Inter', sans-serif; }
        input:focus { border-color: #06b6d4; box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1); }
        
        .action-row { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; }
        .btn-save { background: linear-gradient(90deg, #06b6d4, #3b82f6); color: #fff; padding: 14px 35px; border-radius: 30px; border: none; font-weight: 700; cursor: pointer; transition: transform 0.2s; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3); }
        .btn-save:hover { transform: translateY(-2px); }
        .btn-logout { color: #f43f5e; text-decoration: none; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-logout:hover { color: #fff; }
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; text-align: center; font-size: 14px; }

        /* ABOUT CARD STYLES */
        .tech-badge { background: #0f172a; border: 1px solid #334155; color: #cbd5e1; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .tech-badge i { color: #06b6d4; }
        .social-link { width: 40px; height: 40px; border-radius: 50%; background: #0f172a; display: flex; align-items: center; justify-content: center; color: #94a3b8; text-decoration: none; border: 1px solid #334155; transition: 0.3s; }
        .social-link:hover { background: #06b6d4; color: #fff; border-color: #06b6d4; transform: translateY(-3px); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="profile-wrapper">
            
            <div class="header-card">
                <div class="avatar"></div>
                <div class="user-details">
                    <h1><?= htmlspecialchars($u['username']) ?> <span class="badge">PRO</span></h1>
                    <p><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($u['email']) ?></p>
                    <p><i class="fa-solid fa-clock"></i> Last Active: <span style="color:#22d3ee"><?= $last_login ?></span></p>
                </div>
            </div>

            <?php if($msg): ?>
                <div class="alert" style="background: <?= $msg_color ?>20; color: <?= $msg_color ?>; border: 1px solid <?= $msg_color ?>;">
                    <?= $msg ?>
                </div>
            <?php endif; ?>

            <div class="settings-card">
                <div class="section-title">Personal Information</div>
                <form method="POST">
                    <div class="form-grid">
                        <div class="input-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($u['username']) ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="section-title" style="margin-top: 10px;">Financial Settings</div>
                    <div class="form-grid">
                        <div class="input-group" style="grid-column: span 2;">
                            <label>Monthly Budget Limit (₹)</label>
                            <input type="number" name="salary" value="<?= htmlspecialchars($u['salary']) ?>" step="0.01" required>
                        </div>
                    </div>

                    <div class="action-row">
                        <a href="logout.php" class="btn-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a>
                        <button type="submit" class="btn-save">Save Changes</button>
                    </div>
                </form>
            </div>

            <div class="about-card" style="margin-top: 30px; background: #1e293b; padding: 40px; border-radius: 20px; border: 1px solid #334155; position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(6, 182, 212, 0.15); border-radius: 50%; filter: blur(50px);"></div>
                <div style="display: flex; gap: 30px; align-items: start;">
                    <div style="background: rgba(15, 23, 42, 0.5); padding: 20px; border-radius: 16px; border: 1px solid #334155;">
                        <i class="fa-solid fa-wallet" style="font-size: 32px; color: #06b6d4;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h2 style="font-family: 'Montserrat', sans-serif; font-size: 20px; margin-bottom: 10px;">About FinTrackPro <span style="font-size:11px; background:#334155; padding: 3px 8px; border-radius:10px; vertical-align: middle; margin-left: 8px;">v1.0.0</span></h2>
                        <p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin-bottom: 20px;">
                            FinTrackPro is an intelligent financial management solution designed to empower individuals with real-time budgeting insights. Powered by <strong>Artificial Intelligence</strong> for receipt analysis and dynamic data visualization, it simplifies the journey toward financial freedom.
                        </p>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 25px;">
                            <span class="tech-badge"><i class="fa-brands fa-php"></i> PHP 8</span>
                            <span class="tech-badge"><i class="fa-solid fa-database"></i> MySQL</span>
                            <span class="tech-badge"><i class="fa-solid fa-brain"></i> Google Gemini AI</span>
                            <span class="tech-badge"><i class="fa-solid fa-chart-pie"></i> Chart.js</span>
                        </div>
                        <div style="border-top: 1px solid #334155; padding-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Developed By</div>
                                <div style="font-size: 16px; font-weight: 600; color: #fff; margin-top: 4px;">
                                    J. Hakkim Khan <span style="color:#64748b; font-weight:400; margin: 0 5px;">&</span> Jeeva.B
                                </div>
                                <div style="font-size: 12px; color: #06b6d4;">Information Technology Students</div>
                            </div>
                            <div style="display: flex; gap: 15px;">
                                <a href="#" class="social-link"><i class="fa-brands fa-github"></i></a>
                                <a href="#" class="social-link"><i class="fa-brands fa-linkedin"></i></a>
                                <a href="mailto:contact@fintrack.com" class="social-link"><i class="fa-solid fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>