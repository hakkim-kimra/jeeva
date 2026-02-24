<?php
session_start();
include 'db_connect.php';

// SIGN UP
if (isset($_POST['signup'])) {
    $user = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT); 

    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($check->num_rows > 0) {
        echo "<script>alert('Email already exists!'); window.location.href='index.php';</script>";
    } else {
        $conn->query("INSERT INTO users (username, email, password) VALUES ('$user', '$email', '$pass')");
        // SECURE FIX: Save both name and unique email to session
        $_SESSION['user'] = $user;
        $_SESSION['user_email'] = $email; 
        echo "<script>window.location.href='onboarding.php';</script>"; 
    }
}

// SIGN IN
if (isset($_POST['signin'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $pass = $_POST['password'];
    
    $result = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($pass, $row['password'])) {
            // SECURE FIX: Save both name and unique email to session
            $_SESSION['user'] = $row['username'];
            $_SESSION['user_email'] = $row['email']; 
            
            $conn->query("UPDATE users SET last_login = NOW() WHERE email = '$email'");

            if ($row['salary'] == 0.00 || $row['salary'] == NULL) {
                echo "<script>window.location.href='onboarding.php';</script>";
            } else {
                echo "<script>window.location.href='dashboard.php';</script>";
            }
        } else {
            echo "<script>alert('Incorrect Password'); window.location.href='index.php';</script>";
        }
    } else {
        echo "<script>alert('User not found'); window.location.href='index.php';</script>";
    }
}
?>