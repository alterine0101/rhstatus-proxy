<?php
require_once('../vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

switch ($_SERVER["REQUEST_METHOD"]){
  case "POST":
    submit_report();
    break;
  case "GET":
    get_report();
}

function submit_report(){
  if (!isset($_POST["secret"]) || !isset($_POST["deviceId"]) || !isset($_POST["reportingSource"])){
    http_response_code(400);
    return;
  }
  $compare = hash("sha512", $_ENV["SECRET_KEY"] . $_POST["reportingSource"]);
  if ($compare != $_POST["secret"]){
    http_response_code(401);
    return;
  }
  $device_id = $_POST["deviceId"];
  $file = fopen("../data/$device_id.json", "w");
  $json = json_encode([
    "reportingSource" => $_POST["reportingSource"],
    "lastUpdated" => time(),
    "currentExternalIP" => $_POST["currentExternalIP"],
    "currentInternalIP" => $_POST["currentInternalIP"],
    "currentSSID" => $_POST["currentSSID"],
  ]);
  fwrite($file, $json);
  fclose($file);
  header('Content-Type: application/json');
  echo $json;
}

function get_report(){
  if (!isset($_GET["deviceId"])){
    http_response_code(400);
    return;
  }
  $device_id = $_GET["deviceId"];
  header('Content-Type: application/json');
  if (!file_exists("../data/$device_id.json")){
    http_response_code(404);
    echo "{\"error\":\"No reports available.\"}";
    return;
  }
  $raw = file_get_contents("../data/$device_id.json");
  $json = json_decode($raw, true);
  if (time() - $json["lastUpdated"] > 15 * 60){
    http_response_code(404);
  };
  echo $raw;
}