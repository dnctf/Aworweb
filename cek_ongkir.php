<?php
// htdocs/check_ongkir.php
include 'api_config.php';
header('Content-Type: application/json');

$destination = $_POST['destination'] ?? 0;
$courier = $_POST['courier'] ?? '';

if ($destination && $courier) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.rajaongkir.com/starter/cost",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "origin=".RAJAONGKIR_ORIGIN_CITY_ID."&destination=".$destination."&weight=1000&courier=".$courier, // Berat diasumsikan 1kg (1000gr)
      CURLOPT_HTTPHEADER => array(
        "content-type: application/x-www-form-urlencoded",
        "key: " . RAJAONGKIR_API_KEY
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo json_encode(['error' => 'cURL Error #:' . $err]);
    } else {
        echo $response;
    }
} else {
    echo json_encode(['error' => 'Parameter tidak lengkap.']);
}
?>