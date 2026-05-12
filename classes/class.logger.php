<?php
// Класс логгера
class Logger {
  private $fileLog;
  function __construct ($message, $error = false, $cronjobs = false, $listing = false) {
    trim($message);
    if($cronjobs) $cronjobs = "crons-";
    elseif($listing) $cronjobs = "listings-";
    else $cronjobs = "";
    $error = ($error) ? "error-" : "";
    $fileLog = LOG_FOLDER . "/".$cronjobs.$error. date("Y-m-d") . ".log";
    if (!is_file($fileLog)) $chmod = true;
    if ($file = @fopen ($fileLog, "a+")) {
      if (@flock($file, LOCK_EX)) {
        @fwrite($file, date ("d-m-Y, H:i:s") ." > " . $message ."\n");
        @fflush($file);
        @flock($file, LOCK_UN);
        if (isset($chmod)) chmod($fileLog, 0777);
      }
    }
  }
}