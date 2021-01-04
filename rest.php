<?php
  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\Exception;
  require '/usr/share/php/libphp-phpmailer/autoload.php';

//file_put_contents("/tmp/sql2", var_export($_REQUEST, true).PHP_EOL, FILE_APPEND);
//file_put_contents("/tmp/sql", var_export($_FILES, true).PHP_EOL, FILE_APPEND);
if(
  !isset($_REQUEST["rest"]) ||
  !isset($_REQUEST["id"]) ||
  $_REQUEST["id"] != "259e88811b229050b08679b67147b4ab"
){
  header("HTTP/1.0 404 Not Found");
?>
<html>
<head><title>404 Not Found</title></head>
<body bgcolor="white">
<center><h1>404 Not Found</h1></center>
<hr><center>nginx/1.10.0 (Ubuntu)</center>
</body>
</html>
<?php
  die;
}
?>
<?php
// #####################################################################
define("DATA_PATH", "/var/www/koegler-reisedienst.de/data/");
define("PREVIEW", "/var/www/usr/preview.sh");
// #####################################################################
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// #####################################################################
//file_put_contents("/tmp/sql", "ALL: ".var_export($_REQUEST, true).PHP_EOL, FILE_APPEND);
//file_put_contents("/tmp/sql", "REST: ".$_REQUEST["rest"].PHP_EOL, FILE_APPEND);
switch($_REQUEST["rest"]){
  case "sql":
    Sql();
    break;
  case "dir":
    LsDir();
    break;
  case "sto":
    Sto();
    break;
  case "loa":
    Loa();
    break;
  case "zip":
    Zip();
    break;
  case "unl":
    Unl();
    break;
  case "eml":
    Eml();
    break;
  default:
    echo $_REQUEST["rest"];
}
// #####################################################################
function LsDir(){
  $A = array_filter(scandir($_REQUEST["path"]), function($V, $K){
    return $V != "." && $V != "..";
  }, ARRAY_FILTER_USE_BOTH);
  echo implode("\n", $A);
}
// #####################################################################
function Sql(){
  global $R, $P, $PDO;
  $P = $_REQUEST;
  $PDO = new PDO(
    "mysql:host=localhost;dbname=".$P["base"].";charset=utf8",
    "krocka",
    "miso62krocka"
  );
  $PDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
  $PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  // -------------------------------------------------------------------
  function sql_exec($iA){
    global $R, $P, $PDO;
    if(!isset($P["cmd"]) || $iA >= sizeof($P["cmd"])){
      sql_end();
      return;
    }
    if(isset($P["cmd"][$iA]["para"]))
      $para = $P["cmd"][$iA]["para"];
    else
      $para = array();
    $cmd = $P["cmd"][$iA];
//file_put_contents("/tmp/sql", "SGN ${iA}: ".var_export($cmd["sgn"], true).PHP_EOL."\n".var_export($cmd, true), FILE_APPEND);
    // .................................................................
    if($P["cmd"][$iA]["sgn"] == "ID2IX"){
      // Filter Records ................................................
      $SQL = sprintf(
        "SELECT COUNT(*) AS Filter FROM %s %s",
        $cmd["table"], $cmd["WHERE"]
      );
      $A = $PDO->query($SQL);
      if($A != false){
        $A = $A->fetchAll();
        $R[$cmd["sgn"]]["Filter"] = $A[0]["Filter"];
      } else {
//file_put_contents("/tmp/sql", $SQL.PHP_EOL, FILE_APPEND);
        $R[$cmd["sgn"]] = array(
          "rowIx"  => 0,
          "recId"  => "",
          "Filter" => 0
        );
      }
      // check id ......................................................
      if(!isset($cmd["ID"])){
        $R[$cmd["sgn"]]["rowIx"] = 0;
        $R[$cmd["sgn"]]["recId"] = "";
        sql_exec($iA + 1);
        return;
      }
      $SQL = sprintf(
        "SELECT COUNT(*) AS Test FROM %s %s AND %s=?",
        $cmd["table"], $cmd["WHERE"], $cmd["ID"]
      );
//file_put_contents("/tmp/sql", var_export($cmd, true).PHP_EOL, FILE_APPEND);

      $para = array($cmd["recId"]);
      $ST = $PDO->prepare($SQL);
      if($ST && $ST->execute($para)){
        $A = $ST->fetchAll();
      } else {
        $R = array("err" => $PDO->errorInfo());
        sql_end();
        return;
      }
      if($A[0]["Test"] > 0){
        // sortValue ...................................................
        $SQL = sprintf(
          "SELECT %s AS V FROM %s WHERE %s='%s' LIMIT 1",
          $cmd["orderBy"], $cmd["table"], $cmd["ID"], $cmd["recId"]
        );
        $A = $PDO->query($SQL)->fetchAll();
        if($A == false || $A[0]["V"]  === null)
          $sortValue = null;
        else if(is_int($A[0]["V"]))
          $sortValue = $A[0]["V"];
        else
          $sortValue = "'".$A[0]["V"]."'";
        if($sortValue === null){
          if($cmd["orderType"] == "ASC")
            $W = sprintf(
              "(%s < NULL) OR ((%s IS NULL) AND %s <= '%s')",
              $cmd["orderBy"],
              $cmd["orderBy"],
              $cmd["ID"],
              $cmd["recId"]
            );
          else
            $W = sprintf(
              "(%s IS NOT NULL) OR ((%s IS NULL) AND %s >= '%s')",
              $cmd["orderBy"],
              $cmd["orderBy"],
              $cmd["ID"],
              $cmd["recId"]
            );
        } else {
          if($cmd["orderType"] == "ASC")
            $W = sprintf(
              "(%s < %s OR %s IS NULL) OR (%s = %s AND %s <= '%s')",
              $cmd["orderBy"],
              $sortValue,
              $cmd["orderBy"],
              $cmd["orderBy"],
              $sortValue,
              $cmd["ID"],
              $cmd["recId"]
            );
          else
            $W = sprintf(
              "(%s > %s AND %s IS NOT NULL) OR (%s = %s AND %s >= '%s')",
              $cmd["orderBy"],
              $sortValue,
              $cmd["orderBy"],
              $cmd["orderBy"],
              $sortValue,
              $cmd["ID"],
              $cmd["recId"]
            );
        }
        $R[$cmd["sgn"]]["sortValue"] = $sortValue;
        // rowIx .......................................................
        $SQL = sprintf(
          "SELECT COUNT(*) - 1 as N FROM %s %s AND  (%s)",
          $cmd["table"], $cmd["WHERE"], $W
        );
        $A = $PDO->query($SQL)->fetchAll();
        if($A[0]["N"] >= 0){
          $R[$cmd["sgn"]]["rowIx"] = $A[0]["N"];
          $R[$cmd["sgn"]]["recId"] = $cmd["recId"];
          sql_exec($iA + 1);
          return;
        } else {
          $R[$cmd["sgn"]]["rowIx"] = 0;
        }
      } else {
        $R[$cmd["sgn"]]["rowIx"] = 0;
      }
      // recId .........................................................
      $SQL = sprintf(
        "SELECT %s AS  RecId FROM %s %s ORDER BY %s %s,%s %s LIMIT %s,1",
        $cmd["ID"], $cmd["table"], $cmd["WHERE"],
        $cmd["orderBy"], $cmd["orderType"],
        $cmd["ID"], $cmd["orderType"],
        $R[$cmd["sgn"]]["rowIx"]
      );
      $A = $PDO->query($SQL)->fetchAll();
      $R[$cmd["sgn"]]["recId"] = sizeof($A) == 0 ? null : $A[0]["RecId"];
    // .................................................................
    } else if($P["cmd"][$iA]["sgn"] == "AUTO"){
      $SQL = sprintf(
        "SELECT MAX(%s)+1 AS ID FROM %s",
        $cmd["ID"], $cmd["table"]
      );
      $A = $PDO->query($SQL)->fetchAll();
      $SQL = sprintf(
        "ALTER TABLE %s AUTO_INCREMENT=%s",
        $cmd["table"], $A[0]["ID"]
      );
      $A = $PDO->query($SQL);
    // .................................................................
    } else if(preg_match("/^INSERT|^DELETE|^UPDATE|^REPLACE/", $cmd["query"])){
//file_put_contents("/tmp/sql", "\nALL: ".var_export($P, true).PHP_EOL, FILE_APPEND);
      $ST = $PDO->prepare($cmd["query"]);
      if($ST && $ST->execute($para) !== null){
        $R[$P["cmd"][$iA]["sgn"]]["lastID"] = $PDO->lastInsertId();
//file_put_contents("/tmp/sql", "\nR: ".var_export($R, true).PHP_EOL, FILE_APPEND);
      } else {
        $R = array("err" => $PDO->errorInfo());
//file_put_contents("/tmp/sql", "ERR: ".var_export($PDO->errorInfo(), true).PHP_EOL, FILE_APPEND);
        sql_end();
        return;
      }
    // .................................................................
    } else {
      $ST = $PDO->prepare($P["cmd"][$iA]["query"]);

      if($ST && $ST->execute($para)){
        $R[$P["cmd"][$iA]["sgn"]] = $ST->fetchAll();
      } else {
        $R = array("err" => $P["cmd"][$iA]["query"]);
        sql_end();
        return;
      }
    }
    sql_exec($iA + 1);
  }
  // -------------------------------------------------------------------
  function sql_end(){
    global $R, $PDO;
    header(
      "Content-Type: application/json; charset=utf-8".
      "Cache-Control: no-cache, no-store, must-revalidate".
      "Pragma: no-cache".
      "Expires: 0"
      );
//file_put_contents("/tmp/sql", "END ".var_export($R, true).PHP_EOL, FILE_APPEND);
//file_put_contents("/tmp/sql", "JSON\n".json_encode($R, JSON_HEX_QUOT)."\n". json_last_error().PHP_EOL, FILE_APPEND);
    echo json_encode($R);
    $PDO = null;
  }
  // -------------------------------------------------------------------
  sql_exec(0);
}
// #####################################################################
function Sto(){
  $file_name = DATA_PATH.$_REQUEST["path"];
  if(isset($_REQUEST["d"])){
    $A = json_decode($_REQUEST["d"]);
    $B = "";
    for($i =  0;$i < sizeof($A);$i++)
      $B .= chr($A[$i]);
    file_put_contents($file_name, $B);
  } else {
    file_put_contents($file_name, file_get_contents($_FILES["file"]["tmp_name"]));
  }
  chmod($file_name, 0666);
  if(isset($_REQUEST["preview"]) && $_REQUEST["preview"] == 1){
    exec(PREVIEW." ".$file_name." > /dev/null &");
  };
  header(
    "Content-Type: application/json; charset=utf-8".
    "Cache-Control: no-cache, no-store, must-revalidate".
    "Pragma: no-cache".
    "Expires: 0"
    );
  echo json_encode("OK");
}
// #####################################################################
function Loa(){
  $file = DATA_PATH.$_REQUEST["path"];
  if(isset($_REQUEST["name"]))
    $filename = $_REQUEST["name"];
  else
    $filename = basename($file);
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length: ' . filesize($file));
  readfile($file);
  exit;
}
// #####################################################################
//printf "@ myfile.txt\n@=myfile2.txt\n" | zipnote -w archive.zip
function Zip(){
  $FILES = json_decode($_REQUEST["files"]);
  $ZIPFILE = tempnam("/tmp", "ZIP");
  $CMD = "zip -j -D ";
  if(isset($_REQUEST["pswd"]) && $_REQUEST["pswd"] != "")
    $CMD .= "-P ".$_REQUEST["pswd"]." ";
  $CMD .= $ZIPFILE;
  if(isset($_REQUEST["names"])){
    $NAMES = json_decode($_REQUEST["names"]);
    $TMPDIR = tempnam('/tmp', '');
    mkdir($TMPDIR."dir");
    for($i =  0;$i < sizeof($FILES);$i++){
      copy(DATA_PATH.$FILES[$i], $TMPDIR."dir/".$NAMES[$i]);
    }
    $CMD  .= " ".$TMPDIR."dir/*";
    @exec($CMD);
    @exec("rm -f ".$TMPDIR."* ".$TMPDIR."dir/* ".$TMDIR."dir");
    @exec("rmdir --ignore-fail-on-non-empty ".$TMPDIR."dir");
  } else {
    for($i =  0;$i < sizeof($FILES);$i++)
      $CMD .= " ".DATA_PATH.$FILES[$i];
    @exec($CMD);
  }

  header('Content-disposition: attachment; filename='.date('Y.m.d_H_i_s').'.zip');
  header('Content-type: application/zip');
  readfile($ZIPFILE.".zip");
  @exec("rm -f ".$ZIPFILE."*");
  exit;
}
// #####################################################################
function Unl(){
  array_map('unlink', glob(DATA_PATH.$_REQUEST["path"]));
  header(
    "Content-Type: application/json; charset=utf-8".
    "Cache-Control: no-cache, no-store, must-revalidate".
    "Pragma: no-cache".
    "Expires: 0"
    );
  echo json_encode("OK");
}
// #####################################################################
function Eml(){
  //file_put_contents("/tmp/sql", var_export($_REQUEST, true).PHP_EOL, FILE_APPEND);
  header(
    "Content-Type: application/json; charset=utf-8".
    "Cache-Control: no-cache, no-store, must-revalidate".
    "Pragma: no-cache".
    "Expires: 0"
    );

  $mail = new PHPMailer(true);

  try {
    //Server settings
    $mail->SMTPDebug  = 0;
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'baubermuda@gmail.com';
    $mail->Password   = 'BerBau19!';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->CharSet    = 'utf-8';
    ini_set('default_charset', 'UTF-8');

    //Recipients
    $mail->setFrom('baubermuda@gmail.com', 'Bermuda');
    if(isset($_REQUEST["from"]))
      $mail->addReplyTo($_REQUEST["from"]);
    $to_array = explode(',', $_REQUEST["to"]);
    foreach($to_array as $address){
      $mail->addAddress($address);
    }
    $mail->addBCC('michael.krocka@gmail.com');

    // Attachments
    //$mail->addAttachment('/var/tmp/file.tar.gz');
    //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');

    // Content
    $mail->isHTML(true);
    $mail->Subject = $_REQUEST["subject"];
    $mail->Body    = $_REQUEST["text"];
    $mail->AltBody = strip_tags($mail->Body);

    //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    echo 'OK';
  } catch (Exception $e) {
    echo json_encode($mail->ErrorInfo);
  }
}
// #####################################################################
?>
