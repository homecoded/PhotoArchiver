let sumOriginalData = 0;
let sumOptimizedData = 0;
let permission;

async function selectFolder() {
    const folderHandle = await window.showDirectoryPicker();
    permission = await folderHandle.requestPermission({mode: "readwrite"});

    const files = [];

    document.getElementById('selectFolderButton').setAttribute("disabled", "disabled");
    setProgressBar(0);
    resetError();

    for await (const [name, handle] of folderHandle.entries()) {
        if (handle.kind === "file") {
            if (name.toLowerCase().endsWith(".jpg") || name.toLowerCase().endsWith(".jpeg")) {
                if (!name.includes('.optimized')) {
                    files.push(handle);
                }
            }
        }
    }

    if (files.length > 0) {
        logDataCenter('numfiles', files.length);
        let filesDone = 0;
        for (const file of files) {
            logDataCenter('currentfile', file.name);
            await uploadFileToServer(file, folderHandle);
            logDataCenter('numfilesdone', ++filesDone);
            setProgressBar(Math.round(filesDone / files.length * 100));
        }
    } else {
        logError('Tut mir leid. Ich habe in dem Ordner keine Dateien zum Optimieren gefunden!', folderHandle.name);
    }

    document.getElementById('selectFolderButton').removeAttribute("disabled");
}

function resetError() {
    document.getElementById('warn').innerHTML = "";
}

function logDataCenter(type, data) {
    document.getElementById('data-center').style.display = 'block';
    document.getElementById('stat-' + type).innerHTML = data;
}

function setProgressBar(percent) {
    document.getElementById('progressbar-indicator').style.width = percent + '%';
}

function logError(msg, subject) {
    const error = document.createElement('div');
    error.classList.add('error');
    error.innerHTML = 'Fehler: (' + subject + ') ' + msg;
    document.getElementById('warn').append(error);
}

async function saveBase64FileToFolder(folderHandle, base64Data, fileName) {
    try {
        const base64WithoutPrefix = base64Data.replace(/^data:image\/[a-z]+;base64,/, '');
        const byteCharacters = atob(base64WithoutPrefix);
        const byteNumbers = new Uint8Array(byteCharacters.length);
        for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        const blob = new Blob([byteNumbers], {type: 'image/jpeg'});

        const fileHandle = await folderHandle.getFileHandle(fileName, {create: true});
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
    formData.append('xtoken', localStorage.getItem('xtoken'));
    formData.append('folder', folderHandle.name);
    formData.append('csrf_token', csrf_token);

    try {
        const response = await fetch('optimize.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (response.ok) {
            if (Array.isArray(result)) {
                for (const fileData of result) {
                    sumOriginalData += fileData['originalSize'];
                    sumOptimizedData += fileData['optimizedSize'];
                    let savedPercent = 100 - (sumOptimizedData * 100 / sumOriginalData);
                    logDataCenter('sizebackup', sumOriginalData.toFixed(2));
                    logDataCenter('sizeoptimized', sumOptimizedData.toFixed(2));
                    logDataCenter('savedPercent', savedPercent.toFixed(2));
                    await saveBase64FileToFolder(folderHandle, fileData['optimizedImage'], fileData['optimizedFile'].split('/').pop());
                    await deleteFile(file);
                }
            }
            if (result instanceof Object) {
                if (result.error) {
                    logError(result.error, file.name);
                }
            }
        } else {
            logError("Fehler beim Hochladen der Datei. Datei wurde nicht optimiert.", file.name);
        }

    } catch (error) {
        logError("Fehler beim Hochladen der Datei. Datei wurde nicht optimiert.", file.name);
    }
}

document.getElementById('selectFolderButton').addEventListener('click', selectFolder);
