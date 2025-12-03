<?php include('config.php'); ?>
<?php
$recordCssPath = __DIR__ . '/../css/record_style.css';
$modernCssPath = __DIR__ . '/../css/modern-example.css';
$recordCssVersion = file_exists($recordCssPath) ? filemtime($recordCssPath) : time();
$modernCssVersion = file_exists($modernCssPath) ? filemtime($modernCssPath) : time();

$uiVersionTimestamp = max($recordCssVersion, $modernCssVersion);
$uiVersionFiles = array(
    __DIR__ . '/../index.php',
    __DIR__ . '/functions.php',
    __DIR__ . '/../search.php',
    __DIR__ . '/../login.php',
);

foreach ($uiVersionFiles as $versionFile) {
    if (!file_exists($versionFile)) {
        continue;
    }

    $mtime = @filemtime($versionFile);

    if ($mtime === false) {
        continue;
    }

    if ($mtime > $uiVersionTimestamp) {
        $uiVersionTimestamp = $mtime;
    }
}

$uiTimezone = new DateTimeZone(date_default_timezone_get());
$uiVersionLabel = (new DateTimeImmutable('@' . $uiVersionTimestamp))
    ->setTimezone($uiTimezone)
    ->format('d M Y h:i A T');

$recordingSyncLabel = null;
$lastRecordingSync = $model->getRecordingIndexLastSyncedAt();

if ($lastRecordingSync instanceof DateTimeInterface) {
    $recordingSyncLabel = $lastRecordingSync->format('d M Y h:i A T');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recorded Calls</title>
<link rel="stylesheet" href="css/record_style.css?v=<?php echo $recordCssVersion; ?>">
<link rel="stylesheet" href="css/modern-example.css?v=<?php echo $modernCssVersion; ?>">
</head>
<body class="modern-body">
  <div class="app-shell modern-shell">
    <header class="app-header modern-header">
      <div class="brand">
        <div class="logo">CR</div>
        <div class="brand-text">
          <span class="eyebrow">Call Recorder</span>
          <strong>Operations Console</strong>
        </div>
      </div>
      <div class="header-actions">
        <div class="sync-pill" aria-live="polite">
          <span class="dot <?php echo $recordingSyncLabel !== null ? 'live' : ''; ?>"></span>
          <span class="label"><?php echo $recordingSyncLabel !== null ? 'Index healthy' : 'Index unavailable'; ?></span>
        </div>
        <div class="header-meta">
          <p class="eyebrow">UI updated</p>
          <strong><?php echo htmlspecialchars($uiVersionLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>
        <a class="ghost" href="logout.php">Logout</a>
      </div>
    </header>
    <main class="app-main modern-main">
