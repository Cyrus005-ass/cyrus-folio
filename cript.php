<?php

declare(strict_types=1);

$hostHeader = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
$host = strtolower(trim((string) explode(',', $hostHeader)[0]));
if (str_starts_with($host, '[') && str_contains($host, ']')) {
    $host = substr($host, 1, max(0, strpos($host, ']') - 1));
} else {
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;
}

$remoteIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
$isPrivateIpv4 = $remoteIp !== '' && preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $remoteIp) === 1;
$isLocalRequest = PHP_SAPI === 'cli'
    || in_array($host, ['localhost', '127.0.0.1', '::1', 'host.docker.internal'], true)
    || str_ends_with($host, '.local')
    || str_ends_with($host, '.test')
    || str_ends_with($host, '.localhost')
    || str_ends_with($host, '.internal')
    || in_array($remoteIp, ['127.0.0.1', '::1'], true)
    || $isPrivateIpv4;

if (!$isLocalRequest) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Not Found');
}

$password = trim((string) ($_POST['password'] ?? $_GET['password'] ?? ''));
$hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cript Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #0f172a;
            color: #e2e8f0;
        }

        .card {
            width: min(680px, calc(100vw - 32px));
            background: #111827;
            border: 1px solid #334155;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
        }

        h1 {
            margin-top: 0;
            font-size: 1.6rem;
        }

        p {
            color: #94a3b8;
            line-height: 1.5;
        }

        form {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }

        input,
        textarea,
        button {
            width: 100%;
            box-sizing: border-box;
            border-radius: 12px;
            border: 1px solid #475569;
            padding: 14px;
            font: inherit;
        }

        input,
        textarea {
            background: #020617;
            color: #f8fafc;
        }

        button {
            background: #2563eb;
            color: #ffffff;
            font-weight: 700;
            cursor: pointer;
        }

        .result {
            margin-top: 18px;
        }

        .hash {
            min-height: 120px;
            white-space: pre-wrap;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>Generateur de hash mot de passe</h1>
        <p>Entre un mot de passe pour obtenir un hash PHP compatible avec ton backend.</p>

        <form method="post">
            <input type="text" name="password" placeholder="Mot de passe a hasher" value="<?= htmlspecialchars($password, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Generer le hash</button>
        </form>

        <?php if ($hash !== null): ?>
            <div class="result">
                <p>Hash genere :</p>
                <textarea class="hash" readonly><?= htmlspecialchars($hash, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>