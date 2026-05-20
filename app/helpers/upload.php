<?php

if (!function_exists('upload_file')) {
    function upload_file(array $file, string $folder = 'uploads', array $allowed = ['jpg','jpeg','png','webp','gif','pdf','mp4']): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Fichier uploade invalide.');
        }

        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            throw new RuntimeException('Type de fichier non autorise.');
        }

        $size = (int) ($file['size'] ?? 0);
        $maxSizes = [
            'jpg' => 8 * 1024 * 1024,
            'jpeg' => 8 * 1024 * 1024,
            'png' => 8 * 1024 * 1024,
            'webp' => 8 * 1024 * 1024,
            'gif' => 8 * 1024 * 1024,
            'pdf' => 12 * 1024 * 1024,
            'mp4' => 60 * 1024 * 1024,
        ];
        if ($size <= 0 || $size > ($maxSizes[$ext] ?? 8 * 1024 * 1024)) {
            throw new RuntimeException('Taille de fichier non autorisee.');
        }

        $mimeMap = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'mp4' => ['video/mp4', 'application/mp4', 'audio/mp4'],
        ];

        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = strtolower((string) $finfo->file($tmpName));
            if ($mime === '' || !in_array($mime, $mimeMap[$ext] ?? [], true)) {
                throw new RuntimeException('Signature MIME invalide pour ce fichier.');
            }
        }

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) && @getimagesize($tmpName) === false) {
            throw new RuntimeException('Image invalide ou corrompue.');
        }

        $targetDir = public_path('assets/' . trim($folder, '/'));
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $target = $targetDir . '/' . $filename;
        if (!move_uploaded_file($tmpName, $target)) {
            throw new RuntimeException('Impossible de deplacer le fichier uploade.');
        }
        return 'assets/' . trim($folder, '/') . '/' . $filename;
    }
}