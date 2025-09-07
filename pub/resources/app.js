let sumOriginalData = 0;
let sumOptimizedData = 0;
let permission;
let wakeLock = null;

async function requestWakeLock() {
    try {
        wakeLock = await navigator.wakeLock.request('screen');
        console.log('Wake lock active');
    } catch (err) {
        console.error('Wake lock failed:', err);
    }
}

function releaseWakeLock() {
    if (wakeLock) {
        wakeLock.release();
        wakeLock = null;
    }
}

async function selectFolder() {
    const folderHandle = await window.showDirectoryPicker();
    permission = await folderHandle.requestPermission({mode: "readwrite"});

    const files = [];

    document.getElementById('selectFolderButton').setAttribute("disabled", "disabled");
    setProgressBar(0);
    resetError();
    await requestWakeLock();

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

        // Process files in parallel batches
        const BATCH_SIZE = 3; // Upload 3 files simultaneously
        let filesDone = 0;

        for (let i = 0; i < files.length; i += BATCH_SIZE) {
            const batch = files.slice(i, i + BATCH_SIZE);

            // Process this batch in parallel
            const batchPromises = batch.map(async (file) => {
                logDataCenter('currentfile', file.name);
                await uploadFileToServer(file, folderHandle);
                filesDone++;
                logDataCenter('numfilesdone', filesDone);
                setProgressBar(Math.round(filesDone / files.length * 100));
            });

            // Wait for all files in this batch to complete
            await Promise.all(batchPromises);
        }
    } else {
        logError('Tut mir leid. Ich habe in dem Ordner keine Dateien zum Optimieren gefunden!', folderHandle.name);
    }

    releaseWakeLock();
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
                    logDataCenter('savedPercent', savedPercent.toFixed(2)
                        + '% (' + (sumOriginalData - sumOptimizedData).toFixed(2) + 'MB frei geworden)');
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

