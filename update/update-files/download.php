<?php 
function download($location, $filename,$extension){
  $file = uniqid() . '.'.$extension;

  file_put_contents($file,file_get_contents($location));

  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Length: ' . filesize($file));
  header('Content-Disposition: attachment; filename=' . basename($filename));

  readfile($file);
}

download($_GET['location'], $_GET['filename'],$_GET['extension']);