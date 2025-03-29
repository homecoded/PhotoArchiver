<?php
include '../library/sessionHandling.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilder Backup Service</title>
    <link rel="stylesheet" href="resources/styles.css">
</head>
<body>
<h1>
    Wir machen den Speicher wieder groß!
</h1>
<p>Drücke auf Start um mit dem Scan zu beginnen!</p>

<div class="center">
    <button id="selectFolderButton">Start</button>
</div>

<div id="data-center" class="data-center">
    <h2>Informationen</h2>
    <table>
        <tr>
            <td><span class="table-label">Aktuelle Datei:</span></td>
            <td><span class="tabel-data" id="stat-currentfile">keine</span></td>
        </tr>
        <tr>
            <td><span class="table-label">Dateien (ingesamt):</span></td>
            <td><span class="tabel-data" id="stat-numfiles">0</span></td>
        </tr>
        <tr>
            <td><span class="table-label">Dateien (bearbeitet):</span></td>
            <td><span class="tabel-data" id="stat-numfilesdone">0</span></td>
        </tr>
        <tr>
            <td><span class="table-label">Backupgröße:</span></td>
            <td><span class="tabel-data" id="stat-sizebackup">0</span> MB</td>
        </tr>
        <tr>
            <td><span class="table-label">Optimierte Größe:</span></td>
            <td><span class="tabel-data" id="stat-sizeoptimized">0</span> MB</td>
        </tr>
        <tr>
            <td><span class="table-label">Datenmenge (Anteil eingespart):</span></td>
            <td><span class="tabel-data" id="stat-savedPercent">0</span>%</td>
        </tr>
    </table>

    <div class="progressbar">
        <div id="progressbar-indicator" class="bar"></div>
    </div>
</div>

<div class="warn" id="warn"></div>

<script>
    let csrf_token = '<?php echo $_SESSION['csrf_token']; ?>';
</script>
<script src="resources/app.js"></script>
</body>
</html>
