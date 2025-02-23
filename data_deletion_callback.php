<?php

// Récupérer la requête de Facebook
$request = $_GET['signed_request'] ?? null;

// Vérifier que la requête est valide
if (!$request) {
    echo json_encode(["error" => "Missing signed_request"]);
    exit;
}

// Clé secrète de l'application Facebook (remplace-la par la tienne)
$app_secret = "f551d16707919e5d4a7ba44feea637a5";

// Fonction pour décoder la requête signée de Facebook
function parse_signed_request($signed_request, $secret) {
    list($encoded_sig, $payload) = explode('.', $signed_request, 2);
    
    // Décoder la signature
    $sig = base64_url_decode($encoded_sig);
    
    // Décoder le payload
    $data = json_decode(base64_url_decode($payload), true);

    // Vérifier la signature
    $expected_sig = hash_hmac('sha256', $payload, $secret, true);
    
    if ($sig !== $expected_sig) {
        return null; // Signature invalide
    }

    return $data;
}

// Fonction pour décoder en base64
function base64_url_decode($input) {
    return base64_decode(strtr($input, '-_', '+/'));
}

// Décoder la requête
$data = parse_signed_request($request, $app_secret);

if (!$data) {
    echo json_encode(["error" => "Invalid signed request"]);
    exit;
}

// Récupérer l'ID utilisateur Facebook
$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["error" => "User ID not found"]);
    exit;
}

// Suppression des données utilisateur dans ta base de données (exemple Supabase)
try {
    // Connexion à la base de données (remplace avec tes infos Supabase ou autre)
    $dsn = "pgsql:host=https://lpnkjrwflelezfunblrg.supabase.co;dbname=postgres";
    $pdo = new PDO($dsn, "postgres", "H2@Xq@3-w!!kU6K", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Supprimer les données de l'utilisateur
    $stmt = $pdo->prepare("DELETE FROM users WHERE facebook_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);

} catch (Exception $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    exit;
}

// Répondre à Facebook avec l'URL de confirmation de suppression
$response = [
    "url" => "https://bourbonaiagency.github.io/SuppressionDonnees.html",
    "confirmation_code" => uniqid()
];

echo json_encode($response);
?>
