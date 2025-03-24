<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilder Backup Service</title>
    <link rel="stylesheet" href="resources/styles.css">
</head>
<body>
<div class="settings"><a id="settings-open" href="#settings">&#9881;</a></div>
<h1>
    Wir machen den Speicher wieder groß!
</h1>
<p>Drücke auf Start um mit dem Scan zu beginnen!</p>

<div class="center">
    <button id="selectFolderButton">Start</button>
</div>

<h2>Protokoll</h2>
<div id="protocol"></div>

<div class="settings-popup" id="settings-popup">
    <div class="settings-close"><a id="settings-close" href="#settings">&#9932;</a></div>
    <h2>Einstellungen</h2>
    <div class="entry">
        <label for="server">Server</label>
        <input name="server" id="server">
    </div>
    <a id="settings-search-server" href="#search-server">Nach Server suchen</a>
    <button id="settings-save-server">Speichern</button>
    <div id="server-search-protocol"></div>
</div>

<script src="resources/app.js"></script>
</body>
</html>
