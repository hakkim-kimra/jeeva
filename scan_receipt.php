<?php
$apiKey = "YOUR_GEMINI_API_KEY_HERE"; // <--- PASTE YOUR GOOGLE AI STUDIO KEY HERE
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

header('Content-Type: application/json');

if (!isset($_FILES['receipt_image']) || $_FILES['receipt_image']['error'] != UPLOAD_ERR_OK) {
    echo json_encode(["error" => "No image uploaded. Code: " . $_FILES['receipt_image']['error']]);
    exit;
}

$imagePath = $_FILES['receipt_image']['tmp_name'];
$imageData = base64_encode(file_get_contents($imagePath));
$mimeType = mime_content_type($imagePath);

$prompt = "Analyze this receipt image. Extract:
1. Total Amount (number only).
2. Category (Food, Transport, Housing, Shopping, Utilities).
3. Items (list).
4. Date.
Return ONLY a JSON object with keys: 'amount', 'category', 'items', 'date'. No markdown.";

$data = [
    "contents" => [ [ "parts" => [ ["text" => $prompt], [ "inline_data" => [ "mime_type" => $mimeType, "data" => $imageData ] ] ] ] ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// XAMPP SSL BYPASS
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $json = json_decode($response, true);
    $rawText = $json['candidates'][0]['content']['parts'][0]['text'] ?? '{"error": "AI failed to read"}';
    echo trim(str_replace(["```json", "```"], "", $rawText)); 
} else {
    echo json_encode(["error" => "API Error ($httpCode)"]);
}
?>