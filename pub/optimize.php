<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json; charset=utf-8');

include '../library/sessionHandling.php';

$uploadDir = 'data/uploads/';
$optimizedDir = 'data/optimized/';
$backupTarget = 'data/backup/';

const MAX_SIDE_LENGTH = 2000;

function correctImageOrientation($filename): void
{
    if (function_exists('exif_read_data')) {
        $exif = exif_read_data($filename);
        if ($exif && isset($exif['Orientation'])) {
            $orientation = $exif['Orientation'];
            if ($orientation != 1) {
                $img = imagecreatefromjpeg($filename);
                if (!$img) {
                    echo json_encode(['error' => 'Fehler bei der Ausrichtungskorrektur: ' . $filename]);
                    exit();
                }

                $deg = 0;
                switch ($orientation) {
                    case 3:
                        $deg = 180;
                        break;
                    case 6:
                        $deg = 270;
                        break;
                    case 8:
                        $deg = 90;
                        break;
                }
                if ($deg) {
                    $img = imagerotate($img, $deg, 0);
                }
                imagejpeg($img, $filename, 95);
            }
        }
    }
}

function resizeImage($file, $w, $h): void
{
    list($width, $height) = getimagesize($file);
    $r = $width / $height;
    if ($w / $h > $r) {
        $newWidth = $h * $r;
        $newHeight = $h;
    } else {
        $newHeight = $w / $r;
        $newWidth = $w;
    }

    $src = imagecreatefromjpeg($file);
    $dst = imagecreatetruecolor((int)$newWidth, (int)$newHeight);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, (int)$newWidth, (int)$newHeight, (int)$width, (int)$height);
    imagejpeg($dst, $file);
}

function copyExifData($originalFile, $optimizedFile): void
{
    $originalFile = escapeshellarg($originalFile);
    $optimizedFile = escapeshellarg($optimizedFile);
    shell_exec("exiftool -tagsfromfile $originalFile -exif:all --subifd:all $optimizedFile");
}

function getOptimizedPath($file): string
{
    global $optimizedDir;
    $parts = explode('.', $file);
    $extension = array_pop($parts);
    $parts[] = 'optimized';
    $parts[] = $extension;

    return $optimizedDir . getUserFolder() . implode('.', $parts);
}

function getUserFolder(): string
{
    $xtoken = $_POST['xtoken'] ?? '';
    if (empty($xtoken)) {
        echo json_encode(['error' => 'Ungültige Nutzerkennung.']);
        exit();
    }

    $userFolder = $xtoken . DIRECTORY_SEPARATOR;

    $folder = $_POST['folder'] ?? '';
    if (!empty($folder)) {
        $userFolder .= $folder . DIRECTORY_SEPARATOR;
    }

    return $userFolder;
}

function getDateFolder($path): string
{
    if (!is_file($path)) {
        echo json_encode(['error' => 'Konnte Datei nicht finden (exif):' . $path]);
        exit();
    }

    $exif = exif_read_data($path, null, true);
    if (!$exif) {
        echo json_encode(['error' => 'Konnte Datei nicht lesen (exif):' . $path]);
        exit();
    }
    $fileDatePath = '';
    if (isset($exif['EXIF']['DateTimeOriginal'])) {
        $dateTimeOriginal = $exif['EXIF']['DateTimeOriginal'];
        $parts = explode(':', $dateTimeOriginal);
        $fileDatePath = $parts[0] . DIRECTORY_SEPARATOR . $parts[1] . DIRECTORY_SEPARATOR;
    }
    if (empty($fileDatePath)) {
        $matches = [[]];
        $file = basename($path);
        preg_match_all('/\d{8}/', $file, $matches);
        if (count($matches[0]) > 0) {
            $dateString = $matches[0][0];
            $year = substr($dateString, 0, 4);
            $month = substr($dateString, 4, 2);
            $fileDatePath = $year . DIRECTORY_SEPARATOR . $month . DIRECTORY_SEPARATOR;
        }
    }

    if (empty($fileDatePath)) {
        $fileDatePath = 'Unbekanntes_Erstellungsdatum' . DIRECTORY_SEPARATOR;
    }

    return $fileDatePath;
}


function sanitizePath(string $inputPath, string $baseDir): string
{
    // Check if the sanitized path is within the allowed directory
    if (!str_starts_with($inputPath, $baseDir)) {
        echo json_encode(['error' => 'Pfad außerhalb des gültigen Bereichs']);
        exit();
    }

    if (str_contains($inputPath, '..')) {
        echo json_encode(['error' => 'Mögliche Verzeichnis-Überquerungs-Attacke: ".." ' .
            'ist nicht im Dateinamen erlaubt']);
        exit();
    }

    $inputPath = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR, $inputPath);

    if (substr($inputPath, -1) !== DIRECTORY_SEPARATOR) {
        $inputPath .= DIRECTORY_SEPARATOR;
    }

    return $inputPath;
}

function validateFileUpload($filePath, $size): bool
{
    $allowedTypes = ['image/jpeg'];
    if (!empty($filePath)) {
        $fileType = mime_content_type($filePath);
    } else {
        $fileType = 'unknown';
    }


    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['error' => 'Ungültiger Dateityp']);
        exit();
    }

    if ($size > 25 * 1024 * 1024) {
        echo json_encode(['error' => 'Datei ist zu groß (> 25MB)']);
        exit();
    }

    if ($size <= 0) {
        echo json_encode(['error' => 'Datei ist zu klein (0MB)']);
        exit();
    }

    $imageInfo = getimagesize($filePath);
    if (!$imageInfo || $imageInfo[2] !== IMAGETYPE_JPEG) {
        echo json_encode(['error' => 'Ungültige Bilddatei']);
        exit();
    }

    return true;
}

// check if user is allowed
$xtoken = $_POST['xtoken'] ?? '';
$isUserAllowed = false;
if (!empty($xtoken)) {
    $authFilePath = __DIR__ . '/../.auth.json';
    if (file_exists($authFilePath)) {
        $string = file_get_contents($authFilePath);
        $authData = json_decode($string, true);
        foreach (($authData['users'] ?? []) as $user) {
            if ($xtoken === $user['token']) {
                $isUserAllowed = true;
            }
        }
    }
}

if (!$isUserAllowed) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

$uploadDir = sanitizePath(getcwd() . DIRECTORY_SEPARATOR . $uploadDir, __DIR__);
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$optimizedDir = sanitizePath(getcwd() . DIRECTORY_SEPARATOR . $optimizedDir, __DIR__);
if (!is_dir($optimizedDir)) {
    mkdir($optimizedDir, 0755, true);
}

$optimizedFiles = [];

if (isset($_FILES['files'])) {
    $files = $_FILES['files'];

    foreach ($files['name'] as $index => $fileName) {
        $fileFullPath = $files['full_path'][$index];
        $targetPath = dirname($uploadDir
            . getUserFolder() . $fileFullPath) . DIRECTORY_SEPARATOR;
        $targetFile = basename($fileFullPath);
        sanitizePath($targetPath, __DIR__);

        if (!$targetPath) {
            echo json_encode(['error' => 'Ungültiges Zielverzeichnis: ' . $targetPath]);
            exit();
        }

        if (!is_dir($targetPath)) {
            echo "targetPath $targetPath \n";
            mkdir($targetPath, 0755, true);
        }

        $validExtensions = ['jpg', 'jpeg'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($fileExt, $validExtensions)) {
            echo json_encode(['error' => 'Ungültiges Dateiformat: ' . $fileName]);
            exit();
        }

        $tempfile = $files['tmp_name'][$index];
        validateFileUpload($tempfile, $files['size'][$index] ?? 0);

        if (move_uploaded_file($tempfile, $targetPath . $targetFile)) {
            $optimizedPath = getcwd() . DIRECTORY_SEPARATOR . getOptimizedPath($targetFile);
            sanitizePath($optimizedPath, __DIR__);

            if (!is_dir(dirname($optimizedPath))) {
                mkdir(dirname($optimizedPath), 0755, true);
            }
            copy($targetPath . DIRECTORY_SEPARATOR . $targetFile, $optimizedPath);

            // no sanitation as the backup folder can be outside the project
            $backupFilePath = $backupTarget . getUserFolder()
                . getDateFolder($targetPath . DIRECTORY_SEPARATOR . $targetFile)
                . $fileFullPath;

            if (!is_dir(dirname($backupFilePath))) {
                mkdir(dirname($backupFilePath), 0755, true);
            }
            copy($targetPath . DIRECTORY_SEPARATOR . $targetFile, $backupFilePath);
            correctImageOrientation($optimizedPath);

            list($width, $height, $type, $attr) = getimagesize($optimizedPath);
            $optWidth = 0;
            $optHeight = 0;
            if ($width > $height) {
                $optWidth = MAX_SIDE_LENGTH;
                $optHeight = ceil($optWidth / $width * $height);
            } else {
                $optHeight = MAX_SIDE_LENGTH;
                $optWidth = ceil($optHeight / $height * $width);
            }

            resizeImage($optimizedPath, $optWidth, $optHeight);
            copyExifData($targetPath . DIRECTORY_SEPARATOR . $targetFile, $optimizedPath);
            $bestFile = $optimizedPath;
            $fileSizeOriginal = round(filesize($targetPath . DIRECTORY_SEPARATOR . $targetFile) / (1024 * 1024), 2);
            $fileSizeOptimized = round(filesize($optimizedPath) / (1024 * 1024), 2);

            if ($fileSizeOriginal < $fileSizeOptimized) {
                $bestFile = $targetPath . DIRECTORY_SEPARATOR . $targetFile;
                $fileSizeOptimized = $fileSizeOriginal;
            }

            $optimizedFiles[] = [
                'originalFile' => $files['full_path'][$index],
                'optimizedFile' => $optimizedPath,
                'originalSize' => $fileSizeOriginal,
                'optimizedSize' => $fileSizeOptimized,
                'optimizedImage' => base64_encode(file_get_contents($bestFile))
            ];
            unlink($targetPath . DIRECTORY_SEPARATOR . $targetFile);
            $optimizedPath = realpath($optimizedPath);
            if ($optimizedPath !== false) {
                unlink($optimizedPath);
            }

        } else {
            echo json_encode(['error' => 'Das Hochladen der Datei wurde abgelehnt.']);
        }
    }
}
echo json_encode($optimizedFiles);
