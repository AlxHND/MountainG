<?php

final class Logger
{

  public function __construct(
    string $message,
    bool $error = false,
    bool $cronjobs = false,
    bool $listing = false
  ) {
    self::handle($message, $error, $cronjobs, $listing);
  }

  /**
   * Универсальный метод, максимально близкий к старой сигнатуре.
   */
  public static function handle(
    string $message,
    bool $error = false,
    bool $cronjobs = false,
    bool $listing = false
  ): void {
    $prefix = self::resolvePrefix($cronjobs, $listing);

    if ($error) {
      $prefix .= 'error-';
    }

    self::write($message, $prefix);
  }

  /**
   * Обычный лог.
   */
  public static function log(
    string $message,
    bool $cronjobs = false,
    bool $listing = false
  ): void {
    self::handle($message, false, $cronjobs, $listing);
  }

  /**
   * Лог ошибок.
   */
  public static function error(
    string $message,
    bool $cronjobs = false,
    bool $listing = false
  ): void {
    self::handle($message, true, $cronjobs, $listing);
  }

  /**
   * Лог для cron-задач.
   */
  public static function cron(string $message): void
  {
    self::log($message, true);
  }

  /**
   * Ошибка cron-задачи.
   */
  public static function cronError(string $message): void
  {
    self::error($message, true);
  }

  /**
   * Лог для listings.
   */
  public static function listing(string $message): void
  {
    self::log($message, false, true);
  }

  /**
   * Ошибка listings.
   */
  public static function listingError(string $message): void
  {
    self::error($message, false, true);
  }

  private static function write(string $message, string $prefix = ''): void
  {
    $message = trim($message);

    if ($message === '') {
      return;
    }

    if (!defined('LOG_FOLDER')) {
      return;
    }

    if (!is_dir(LOG_FOLDER)) {
      return;
    }

    $fileLog = rtrim(LOG_FOLDER, '/\\') . '/' . $prefix . date('Y-m-d') . '.log';

    $isNewFile = !is_file($fileLog);

    $file = @fopen($fileLog, 'ab');

    if (!$file) {
      return;
    }

    try {
      if (@flock($file, LOCK_EX)) {
        @fwrite($file, date('d-m-Y, H:i:s') . ' > ' . $message . PHP_EOL);
        @fflush($file);
        @flock($file, LOCK_UN);
      }
    } finally {
      @fclose($file);
    }

    if ($isNewFile && is_file($fileLog)) {
      @chmod($fileLog, 0666);
    }
  }

  private static function resolvePrefix(bool $cronjobs, bool $listing): string
  {
    if ($cronjobs) {
      return 'crons-';
    }

    if ($listing) {
      return 'listings-';
    }

    return '';
  }
}
