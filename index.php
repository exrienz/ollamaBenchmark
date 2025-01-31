<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $ollama_url = filter_var(trim($_POST['ollama_url']), FILTER_SANITIZE_URL);
    $auth_key = trim($_POST['auth_key']);
    $ai_models = array_map('trim', explode(',', $_POST['ai_models']));
    $ai_role = htmlspecialchars(trim($_POST['ai_role']), ENT_QUOTES, 'UTF-8');
    $ai_instruction = htmlspecialchars(trim($_POST['ai_instruction']), ENT_QUOTES, 'UTF-8');
    
    $results = [];

    // Validate URL format
    if (!filter_var($ollama_url, FILTER_VALIDATE_URL)) {
        $error_message = "Invalid Ollama URL.";
    } else {
        foreach ($ai_models as $model) {
            $start_time = microtime(true);

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

            $response = curl_exec($ch);
            $end_time = microtime(true);

            $results[] = [
                'model' => $model,
                'response' => $response,
                'time_taken' => round(($end_time - $start_time) * 1000, 2) . ' ms'
            ];

            curl_close($ch);
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
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 700px; margin-top: 50px; }
        .card { border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .btn-primary { background-color: #007bff; border: none; }
        .btn-primary:hover { background-color: #0056b3; }
        .alert { border-radius: 5px; }
    </style>
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
                    <input type="url" name="ollama_url" class="form-control" value="<?= isset($ollama_url) ? htmlspecialchars($ollama_url) : '' ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Auth Key (if any)</label>
                    <input type="text" name="auth_key" class="form-control" value="<?= isset($auth_key) ? htmlspecialchars($auth_key) : '' ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">AI Models (comma-separated)</label>
                    <input type="text" name="ai_models" class="form-control" value="<?= isset($_POST['ai_models']) ? htmlspecialchars($_POST['ai_models']) : '' ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">AI Role</label>
                    <input type="text" name="ai_role" class="form-control" value="<?= isset($ai_role) ? htmlspecialchars($ai_role) : '' ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">AI Instruction</label>
                    <textarea name="ai_instruction" class="form-control" rows="3" required><?= isset($ai_instruction) ? htmlspecialchars($ai_instruction) : '' ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Run Benchmark</button>
            </form>
        </div>
    </div>
    
    <?php if (!empty($results)): ?>
        <div class="container mt-4">
            <h3 class="text-center">Results</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Model</th>
                        <th>Response</th>
                        <th>Time Taken</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                        <tr>
                            <td><?= htmlspecialchars($result['model']) ?></td>
                            <td><pre><?= htmlspecialchars($result['response']) ?></pre></td>
                            <td><?= htmlspecialchars($result['time_taken']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</body>
</html>
