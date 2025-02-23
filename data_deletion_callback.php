<?php

require_once __DIR__ . '/vendor/autoload.php'; // Charger Composer et dotenv

use Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Récupérer les identifiants de connexion depuis .env
$host = $_ENV['SUPABASE_HOST'];
$db   = $_ENV['SUPABASE_DB_NAME'];
$user = $_ENV['SUPABASE_USER'];
$pass = $_ENV['SUPABASE_PASSWORD'];
$facebook_secret = $_ENV['FACEBOOK_APP_SECRET'];

// Connexion à Supabase (PostgreSQL)
try {
    $dsn = "pgsql:host=$host;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    exit;
}

// Vérifier la requête Facebook
$request = $_GET['signed_request'] ?? null;
if (!$request) {
    echo json_encode(["error" => "Missing signed_request"]);
    exit;
}

// Fonction pour décoder la requête signée de Facebook
function parse_signed_request($signed_request, $secret) {
    list($encoded_sig, $payload) = explode('.', $signed_request, 2);
    
    $sig = base64_url_decode($encoded_sig);
    $data = json_decode(base64_url_decode($payload), true);
    $expected_sig = hash_hmac('sha256', $payload, $secret, true);
    
    return ($sig === $expected_sig) ? $data : null;
}

function base64_url_decode($input) {
    return base64_decode(strtr($input, '-_', '+/'));
}

$data = parse_signed_request($request, $facebook_secret);
if (!$data || !isset($data['user_id'])) {
    echo json_encode(["error" => "Invalid signed request or missing user_id"]);
    exit;
}

// Récupérer l'ID utilisateur Facebook
$user_id = $data['user_id'];

// Supprimer l'utilisateur de Supabase
try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE facebook_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
} catch (Exception $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    exit;
}

// Répondre à Facebook avec une confirmation
$response = [
    "url" => "http://everrr.re/facebook/SuppressionDonnees.html",
    "confirmation_code" => uniqid()
];

echo json_encode($response);

?>
