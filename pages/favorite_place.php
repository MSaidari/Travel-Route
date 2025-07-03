<?php
session_start();
header("Content-Type: application/json");

$user_id = $_SESSION['user_id'] ?? null;
if(!$user_id){
  echo json_encode(["success"=>false,"error"=>"Giriş yapmalısınız."]);
  exit;
}

$place_id = isset($_POST['place_id']) ? intval($_POST['place_id']) : 0;
$action   = $_POST['action'] ?? "";

if(!$place_id || !in_array($action,["like","unlike"])){
  echo json_encode(["success"=>false,"error"=>"Geçersiz parametre."]);
  exit;
}

$conn = new mysqli("localhost","root","12345678","gezirotasi");
if($conn->connect_error){
  echo json_encode(["success"=>false,"error"=>"DB hatası."]);
  exit;
}

if($action==="like"){
  // beğeni ekle
  $stmt = $conn->prepare("INSERT IGNORE INTO favorite_places (user_id, place_id) VALUES (?,?)");
  $stmt->bind_param("ii",$user_id,$place_id);
  $ok = $stmt->execute();
  $stmt->close();
  echo json_encode(["success"=>$ok]);
} else {
  // beğeni kaldır
  $stmt = $conn->prepare("DELETE FROM favorite_places WHERE user_id=? AND place_id=?");
  $stmt->bind_param("ii",$user_id,$place_id);
  $ok = $stmt->execute();
  $stmt->close();
  echo json_encode(["success"=>$ok]);
}
?>
