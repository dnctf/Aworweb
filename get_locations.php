<?php
// htdocs/get_locations.php
require_once 'api_config.php'; 
header('Content-Type: application/json');

// Validasi API Key
if (!defined('RAJAONGKIR_API_KEY') || RAJAONGKIR_API_KEY === 'cks7Fapg88019e7b643093f3PbQjjDNG' || RAJAONGKIR_API_KEY === 'cks7Fapg88019e7b643093f3PbQjjDNG') {
    http_response_code(500);
    echo json_encode(['error' => 'API Key RajaOngkir belum diatur dengan benar di dalam file api_config.php.']);
    exit;
}

$endpoint = 'province';
$params = '';

if (isset($_GET['province_id']) && is_numeric($_GET['province_id'])) {
    $endpoint = 'city';
    $params = '?province=' . $_GET['province_id'];
}

$curl = curl_init();
curl_setopt_array($curl, [
  CURLOPT_URL => "https://api.rajaongkir.com/starter/" . $endpoint . $params,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => ["key: " . RAJAONGKIR_API_KEY],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
  http_response_code(500);
  echo json_encode(['error' => 'cURL Error: ' . $err]);
} else {
  // Cek apakah response valid
  $responseData = json_decode($response, true);
  if (json_last_error() !== JSON_ERROR_NONE || !isset($responseData['rajaongkir'])) {
      http_response_code(500);
      echo json_encode(['error' => 'Respons tidak valid dari RajaOngkir. Cek API Key Anda.', 'details' => $response]);
  } else {
      echo $response;
  }
}
?>