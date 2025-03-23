let sumOriginalData = 0;
let sumOptimizedData = 0;

// Funktion zum Auswählen und Anzeigen der Dateien eines Ordners
async function selectFolder() {
    const folderHandle = await window.showDirectoryPicker(); // Nutzer wählt Ordner aus

    // Berechtigung zum Lesen + Schreiben anfordern
    const permission = await folderHandle.requestPermission({mode: "readwrite"});

    for await (const [name, handle] of folderHandle.entries()) {
        if (handle.kind === "file") {
            if (name.toLowerCase().endsWith(".jpg") || name.toLowerCase().endsWith(".jpeg")) {
                if (!name.includes('.optimized')) {
                    await uploadFileToServer(handle, folderHandle);
                }
            }
        }
    }
    log('&nbsp;');
    log('Originale Daten: ' + sumOriginalData + ' Megabytes');
    log('Optimierte Daten: ' + sumOptimizedData + ' Megabytes');
    log('Ersparnis: <strong>' + (100 - sumOptimizedData / sumOriginalData * 100).toFixed(1) + '%</strong>');
}

async function saveBase64FileToFolder(folderHandle, base64Data, fileName) {
    try {
        const base64WithoutPrefix = base64Data.replace(/^data:image\/[a-z]+;base64,/, '');
        const byteCharacters = atob(base64WithoutPrefix);
        const byteNumbers = new Uint8Array(byteCharacters.length);
        for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        const blob = new Blob([byteNumbers], { type: 'image/jpeg' });

        const fileHandle = await folderHandle.getFileHandle(fileName, { create: true });
        const writable = await fileHandle.createWritable();
        await writable.write(blob);
        await writable.close();
    } catch (error) {
        console.error('Error saving file:', error);
    }
}

async function deleteFile(fileHandle) {
    try {
        if (fileHandle.kind === "file") {
            await fileHandle.remove();
        }
    } catch (error) {
        console.error("Error deleting file:", error);
    }
}

// Funktion zum Hochladen der Dateien an das PHP-Script
async function uploadFileToServer(file, folderHandle) {
    const formData = new FormData();
    formData.append('files[]', await file.getFile());

    try {
        const response = await fetch('optimize.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (response.ok) {
            if (Array.isArray(result)) {
                for (const fileData of result) {
                    log(fileData['originalFile']);
                    sumOriginalData += fileData['originalSize'];
                    sumOptimizedData += fileData['optimizedSize'];
                    await saveBase64FileToFolder(folderHandle, fileData['optimizedImage'], fileData['optimizedFile'].split('/').pop());
                    await deleteFile(file);
                }
            }
        } else {
            console.error('Fehler beim Hochladen:', response.statusText);
        }

    } catch (error) {
        console.error('Fehler beim Hochladen der Dateien:', error);
    }
}

// Funktion zum Aktualisieren der Dateiliste in der Anzeige
function log(msg) {
    const folderList = document.getElementById('protocol');
    const fileItem = document.createElement('div');
    fileItem.innerHTML = msg;
    folderList.appendChild(fileItem);
}

// Event Listener für den Button
document.getElementById('selectFolderButton').addEventListener('click', selectFolder);

//////////////////////////////////
// SETTINGS
//////////////////////////////////

function openSettings(e) {
    e.preventDefault();
    const popup = document.getElementById('settings-popup');
    popup.classList.add('open');
}

function closeSettings(e) {
    e.preventDefault();
    const popup = document.getElementById('settings-popup');
    popup.classList.remove('open');
}

async function checkUrl(testUrl) {
    const response = await fetch(testUrl, { signal: AbortSignal.timeout(500) });
    if (response.status == 200) {
        return true;
    }
    return false;
};

async function searchServer() {

    const protocol = document.getElementById('server-search-protocol');
    for (let thirdOcted = 1; thirdOcted < 256; thirdOcted++) {
        for (let fourthOcted = 1; fourthOcted < 256; fourthOcted++) {
            let IP = '192.168.' + thirdOcted + '.' + fourthOcted;
            const testUrl = 'https://' + IP + '/optimize.php';

            protocol.innerText = 'Das wird jetzt leider etwas dauern ...\n' + 'Untersuche ' + testUrl;
            try {
                let result = await checkUrl(testUrl);
                if (result) {
                    return testUrl;
                }
            } catch (error) {
                console.warn('Error testing', testUrl, error);
            }
        }
    }
}

async function automateServerSetting() {
    const protocol = document.getElementById('server-search-protocol');
    let serverUrl = await searchServer();
    console.log(serverUrl);
    protocol.innerText = ' Gefunden ' + serverUrl + '!';
    document.getElementById('server').value = serverUrl;
}

document.getElementById('settings-open').addEventListener('click', openSettings);
document.getElementById('settings-close').addEventListener('click', closeSettings);
document.getElementById('settings-search-server').addEventListener('click', automateServerSetting);
