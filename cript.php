<?php

declare(strict_types=1);

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
