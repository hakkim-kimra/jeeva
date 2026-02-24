<?php 
session_start(); 
// Secure Email Session Check
if(isset($_SESSION['user_email'])) { header("Location: dashboard.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to FinTrackPro | Secure Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@700;800;900&display=swap');
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: #0f172a; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            overflow: hidden; 
        }

        /* --- 1. THE CINEMATIC SPLASH SCREEN --- */
        #splash-screen {
            position: fixed; inset: 0; background: #0f172a; z-index: 9999;
            display: flex; justify-content: center; align-items: center;
            animation: hideSplash 0.5s ease 3.5s forwards; 
        }

        .splash-logo {
            font-family: 'Montserrat', sans-serif; font-size: 70px; font-weight: 900; color: #fff;
            display: flex; align-items: center; gap: 20px; letter-spacing: -2px; opacity: 0;
            animation: textFadeInOut 3s ease forwards; 
        }
        .splash-logo span { color: #06b6d4; }
        .splash-logo i { color: #06b6d4; filter: drop-shadow(0 0 25px rgba(6,182,212,0.8)); }

        @keyframes textFadeInOut {
            0% { opacity: 0; transform: scale(0.9); filter: blur(10px); }
            20% { opacity: 1; transform: scale(1); filter: blur(0px); }
            80% { opacity: 1; transform: scale(1); filter: blur(0px); }
            100% { opacity: 0; transform: scale(1.1); filter: blur(10px); }
        }
        @keyframes hideSplash {
            0% { opacity: 1; visibility: visible; }
            100% { opacity: 0; visibility: hidden; } 
        }

        /* --- 2. THE MAIN SLIDING CONTAINER (Matches your Image) --- */
        .container {
            background-color: #1e293b; /* Dark Blue Form Background */
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
            width: 900px;
            max-width: 100%;
            height: 550px;
            /* Hides the form until splash screen is done */
            opacity: 0;
            animation: revealForm 1s cubic-bezier(0.16, 1, 0.3, 1) 3.5s forwards;
        }

        @keyframes revealForm {
            0% { opacity: 0; transform: translateY(40px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .form-container {
            position: absolute; top: 0; height: 100%; transition: all 0.6s ease-in-out;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
        }

        /* SIGN IN FORM (Left Side) */
        .sign-in-container { left: 0; width: 50%; z-index: 2; }
        .container.right-panel-active .sign-in-container { transform: translateX(100%); }

        /* SIGN UP FORM (Right Side) */
        .sign-up-container { left: 0; width: 50%; opacity: 0; z-index: 1; }
        .container.right-panel-active .sign-up-container {
            transform: translateX(100%); opacity: 1; z-index: 5; animation: show 0.6s;
        }

        @keyframes show { 0%, 49.99% { opacity: 0; z-index: 1; } 50%, 100% { opacity: 1; z-index: 5; } }

        /* --- 3. FORM STYLING --- */
        form {
            background-color: #1e293b; display: flex; align-items: center; justify-content: center;
            flex-direction: column; padding: 0 50px; height: 100%; text-align: center; width: 100%;
        }
        h1 { font-family: 'Montserrat', sans-serif; font-weight: 800; color: #fff; margin-bottom: 5px; font-size: 32px; }
        p.subtitle { color: #94a3b8; font-size: 13px; margin-bottom: 25px; }

        /* Input Fields */
        .input-group { position: relative; width: 100%; margin-bottom: 15px; }
        .input-group input {
            background-color: rgba(255, 255, 255, 0.05); /* Semi-transparent like the image */
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 45px 15px 20px; width: 100%;
            border-radius: 10px; color: #fff; font-size: 14px; outline: none; transition: 0.3s;
        }
        .input-group input:focus { border-color: #06b6d4; background-color: rgba(255, 255, 255, 0.1); }
        .input-group input::placeholder { color: #94a3b8; }

        /* The Eye Icon */
        .pwd-toggle { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; cursor: pointer; transition: 0.3s; }
        .pwd-toggle:hover { color: #06b6d4; }

        a.forgot { color: #06b6d4; font-size: 12px; text-decoration: none; margin: 10px 0 20px; font-weight: 600; }
        
        button {
            border-radius: 25px; border: none; background: #06b6d4; color: #fff;
            font-size: 13px; font-weight: 700; padding: 14px 45px; letter-spacing: 1px;
            text-transform: uppercase; transition: transform 0.1s, background 0.3s; cursor: pointer;
            box-shadow: 0 5px 15px rgba(6, 182, 212, 0.4); font-family: 'Montserrat', sans-serif;
        }
        button:active { transform: scale(0.95); }
        button:hover { background: #0891b2; }

        /* Error Box */
        #error-msg { display: none; background: rgba(244, 63, 94, 0.1); border: 1px solid #f43f5e; color: #f43f5e; padding: 10px; border-radius: 8px; font-size: 12px; font-weight: 600; width: 100%; margin-bottom: 15px; }

        /* --- 4. THE OVERLAY (The Cyan Sliding Panel) --- */
        .overlay-container {
            position: absolute; top: 0; left: 50%; width: 50%; height: 100%; overflow: hidden;
            transition: transform 0.6s ease-in-out; z-index: 100;
        }
        .container.right-panel-active .overlay-container { transform: translateX(-100%); }

        .overlay {
            background: linear-gradient(135deg, #06b6d4, #2563eb);
            background-repeat: no-repeat; background-size: cover; background-position: 0 0;
            color: #fff; position: relative; left: -100%; height: 100%; width: 200%;
            transform: translateX(0); transition: transform 0.6s ease-in-out;
        }
        .container.right-panel-active .overlay { transform: translateX(50%); }

        .overlay-panel {
            position: absolute; display: flex; align-items: center; justify-content: center;
            flex-direction: column; padding: 0 40px; text-align: center; top: 0; height: 100%;
            width: 50%; transform: translateX(0); transition: transform 0.6s ease-in-out;
        }

        .overlay-left { transform: translateX(-20%); }
        .container.right-panel-active .overlay-left { transform: translateX(0); }
        .overlay-right { right: 0; transform: translateX(0); }
        .container.right-panel-active .overlay-right { transform: translateX(20%); }

        .overlay-panel h1 { font-size: 38px; }
        .overlay-panel p { font-size: 15px; font-weight: 400; line-height: 1.6; margin: 20px 0 30px; }
        
        button.ghost {
            background-color: transparent; border: 2px solid #fff; box-shadow: none;
        }
        button.ghost:hover { background-color: #fff; color: #06b6d4; }
    </style>
</head>
<body>

    <div id="splash-screen">
        <div class="splash-logo">
            <i class="fa-solid fa-layer-group"></i> FinTrack<span>Pro</span>
        </div>
    </div>

    <div class="container" id="container">
        
        <div class="form-container sign-up-container">
            <form action="auth.php" method="POST" onsubmit="return validateSignup()">
                <h1>Create Account</h1>
                <p class="subtitle">Use your email for registration</p>
                
                <div id="error-msg"><i class="fa-solid fa-circle-exclamation"></i> Passwords do not match!</div>

                <div class="input-group">
                    <input type="text" name="username" placeholder="Username" required />
                </div>
                <div class="input-group">
                    <input type="email" name="email" placeholder="Email" required />
                </div>
                <div class="input-group">
                    <input type="password" name="password" id="reg-pwd" placeholder="Password" required />
                    <i class="fa-regular fa-eye pwd-toggle" onclick="togglePassword('reg-pwd', this)"></i>
                </div>
                <div class="input-group">
                    <input type="password" id="reg-confirm-pwd" placeholder="Confirm" required />
                    <i class="fa-regular fa-eye pwd-toggle" onclick="togglePassword('reg-confirm-pwd', this)"></i>
                </div>
                
                <button type="submit" name="signup">Sign Up</button>
            </form>
        </div>

        <div class="form-container sign-in-container">
            <form action="auth.php" method="POST">
                <h1>Sign In</h1>
                <p class="subtitle">Welcome back</p>
                
                <div class="input-group">
                    <input type="email" name="email" placeholder="Email" required />
                </div>
                <div class="input-group">
                    <input type="password" name="password" id="login-pwd" placeholder="Password" required />
                    <i class="fa-regular fa-eye pwd-toggle" onclick="togglePassword('login-pwd', this)"></i>
                </div>
                
                <a href="forgot_password.php" class="forgot">Forgot your password?</a>
                <button type="submit" name="signin">Sign In</button>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>To keep connected with us please login with your personal info</p>
                    <button class="ghost" id="signIn">Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Friend!</h1>
                    <p>Enter your personal details and start your journey with us</p>
                    <button class="ghost" id="signUp">Sign Up</button>
                </div>
            </div>
        </div>
        
    </div>

    <script>
        // 1. Sliding Panel Logic
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');

        signUpButton.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
            document.getElementById('error-msg').style.display = 'none'; // Clear errors when switching
        });

        // 2. Eye Icon Password Toggle Logic
        function togglePassword(inputId, iconElement) {
            const inputField = document.getElementById(inputId);
            if (inputField.type === "password") {
                inputField.type = "text";
                iconElement.classList.remove('fa-eye');
                iconElement.classList.add('fa-eye-slash');
                iconElement.style.color = '#06b6d4'; // Highlight when visible
            } else {
                inputField.type = "password";
                iconElement.classList.remove('fa-eye-slash');
                iconElement.classList.add('fa-eye');
                iconElement.style.color = '#94a3b8'; // Revert color
            }
        }

        // 3. Confirm Password Validation Logic
        function validateSignup() {
            const pwd = document.getElementById('reg-pwd').value;
            const confirmPwd = document.getElementById('reg-confirm-pwd').value;
            const errorMsg = document.getElementById('error-msg');

            if (pwd !== confirmPwd) {
                errorMsg.style.display = 'block';
                return false; // Prevent form submission
            }
            errorMsg.style.display = 'none';
            return true; // Allow submission
        }
    </script>
</body>
</html>