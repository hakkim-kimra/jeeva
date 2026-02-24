<?php
session_start();
include 'db_connect.php';

// SECURE FIX: Ensure the user is logged in via their unique email
if (!isset($_SESSION['user_email'])) { 
    header("Location: index.php"); 
    exit(); 
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Grab the exact email of the currently logged-in user
    $email = $_SESSION['user_email']; 

    $amount = $_POST['amount'];
    $category = ($_POST['category'] === 'Custom') ? $_POST['custom_cat'] : $_POST['category'];
    $notes = $conn->real_escape_string($_POST['notes']);

    // Insert the expense tied strictly to their unique email
    $sql = "INSERT INTO expenses (user_email, amount, category, notes) VALUES ('$email', '$amount', '$category', '$notes')";
    $conn->query($sql);

    header("Location: dashboard.php");
    exit();
}
?>