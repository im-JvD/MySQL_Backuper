<?php

/* ================== CONFIG ================== */

// ✅ تلگرام
$botToken = "$BOT_TOKEN";
$chatId   = "$ADMIN_CHATID";

// ✅ دایرکتوری که میخواهید از آن بکاپ بگیرید
$sourceDir = "/home/$HOSTPATH/public_html/backup";

// ✅ محل ذخیره بکاپ
$backupDir = "/home/$HOSTPATH/public_html";

// ✅ تعداد بکاپ هایی که نگه داشته می شوند
$maxBackups = 1;

/* ================== CHECKS ================== */

if (!extension_loaded('zip')) {
    exit("ZipArchive not enabled");
}

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

/* ================== ZIP ================== */

$date = date("Y-m-d_H-i");
$zipFile = "$backupDir/MirzaBOT_$date.zip";

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    exit("Cannot create zip file");
}

$sourceDir = realpath($sourceDir);

 /*
 * Recursive zip
 */
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file) {
    if ($file->isDir()) continue;

    $filePath     = $file->getRealPath();
    $relativePath = substr($filePath, strlen($sourceDir) + 1);

    $zip->addFile($filePath, $relativePath);
}

$zip->close();

/* ================== CLEAN OLD BACKUPS ================== */

$backups = glob("$backupDir/MirzaBOT_*.zip");
if (count($backups) > $maxBackups) {
    sort($backups);
    while (count($backups) > $maxBackups) {
        unlink(array_shift($backups));
    }
}

/* ================== SEND TO TELEGRAM ================== */

$url = "https://api.telegram.org/bot$botToken/sendDocument";

$postFields = [
    'chat_id' => $chatId,
    'document' => new CURLFile($zipFile),
    'caption' => "✅ #MirzaBOT Backup Created...!\n   - https://github.com/im-JvD/MirzaBOT-hostBackuper" 
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => $postFields
]);

$response = curl_exec($ch);
curl_close($ch);

/* ================== DONE ================== */

