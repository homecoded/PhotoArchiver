<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json; charset=utf-8');

$uploadDir = 'uploads/';
$optimizedDir = 'optimized/';
$backupTarget = 'backup/Media/Bilder/Manuel Handy/PhotoArchiver/';

const MAX_SIDE_LENGTH = 2000;

function correctImageOrientation($filename): void
{
    if (function_exists('exif_read_data')) {
        $exif = exif_read_data($filename);
        if ($exif && isset($exif['Orientation'])) {
            $orientation = $exif['Orientation'];
            if ($orientation != 1) {
                $img = imagecreatefromjpeg($filename);
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
    $dst = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
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

    return $optimizedDir . implode('.', $parts);
}

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!is_dir($optimizedDir)) {
    mkdir($optimizedDir, 0755, true);
}

$optimizedFiles = [];

if (isset($_FILES['files'])) {
    $files = $_FILES['files'];

    foreach ($files['name'] as $index => $fileName) {
        $targetPath = $uploadDir . $files['full_path'][$index];
        if (!is_dir(dirname($targetPath))) {
            mkdir(dirname($targetPath), 0755, true);
        }

        if (move_uploaded_file($files['tmp_name'][$index], $targetPath)) {
            $optimizedPath = getOptimizedPath($files['full_path'][$index]);

            if (!is_dir(dirname($optimizedPath))) {
                mkdir(dirname($optimizedPath), 0755, true);
            }
            copy($targetPath, $optimizedPath);

            $backupFilePath = $backupTarget . $files['full_path'][$index];
            if (!is_dir(dirname($backupFilePath))) {
                mkdir(dirname($backupFilePath), 0755, true);
            }
            copy($targetPath, $backupFilePath);
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
            copyExifData($targetPath, $optimizedPath);
            $bestFile = $optimizedPath;
            $fileSizeOriginal = round( filesize($targetPath) / (1024 * 1024), 2);
            $fileSizeOptimized = round( filesize($optimizedPath) / (1024 * 1024), 2);

            if ($fileSizeOriginal < $fileSizeOptimized) {
                $bestFile = $targetPath;
            }

            $optimizedFiles[] = [
                'originalFile' => $files['full_path'][$index],
                'optimizedFile' => $optimizedPath,
                'originalSize' => $fileSizeOriginal,
                'optimizedSize' => $fileSizeOptimized,
                'optimizedImage' => base64_encode(file_get_contents($bestFile))
            ];
            unlink($targetPath);
        }
    }
}
echo json_encode($optimizedFiles);
