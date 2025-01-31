<?php
ob_implicit_flush(true);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ollama_url = filter_var(trim($_POST['ollama_url']), FILTER_SANITIZE_URL);
    $auth_key = trim($_POST['auth_key']);
    $ai_models = array_map('trim', explode(',', $_POST['ai_models']));
    $ai_role = htmlspecialchars(trim($_POST['ai_role']), ENT_QUOTES, 'UTF-8');
    $ai_instruction = htmlspecialchars(trim($_POST['ai_instruction']), ENT_QUOTES, 'UTF-8');

    if (!filter_var($ollama_url, FILTER_VALIDATE_URL)) {
        $error_message = "Invalid Ollama URL.";
    } else {
        echo '<div class="container mt-4"><h3 class="text-center">Results</h3><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Model</th><th>Response</th><th>Time Taken</th></tr></thead><tbody id="results"></tbody></table></div></div>';
        ob_flush(); flush();

        foreach ($ai_models as $model) {
            $data = [
                'model' => $model,
                'role' => $ai_role,
                'prompt' => $ai_instruction,
                'stream' => false
            ];

            $headers = ["Content-Type: application/json"];
            if (!empty($auth_key)) {
                $headers[] = "Authorization: Bearer $auth_key";
            }

            $ch = curl_init("$ollama_url/api/generate");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);

            $start_time = microtime(true);
            $response = curl_exec($ch);
            $response_time = round(microtime(true) - $start_time, 2);
            curl_close($ch);

            $decoded_response = json_decode($response, true);
            $output_text = $decoded_response['response'] ?? 'No response received';
            echo "<script>document.getElementById('results').innerHTML += '<tr><td>" . htmlspecialchars($model) . "</td><td><pre>" . htmlspecialchars($output_text) . "</pre></td><td>" . htmlspecialchars($response_time . 's') . "</td></tr>';</script>";
            ob_flush(); flush();
            usleep(300000);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ollama AI Benchmark</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="card p-4">
            <h2 class="mb-4 text-center">Ollama AI Benchmark</h2>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Ollama URL</label>
                    <input type="url" name="ollama_url" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Auth Key (if any)</label>
                    <input type="text" name="auth_key" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">AI Models (comma-separated)</label>
                    <input type="text" name="ai_models" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">AI Role</label>
                    <input type="text" name="ai_role" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">AI Instruction</label>
                    <textarea name="ai_instruction" class="form-control" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Run Benchmark</button>
            </form>
        </div>
    </div>
</body>
</html>
