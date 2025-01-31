<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $ollama_url = filter_var(trim($_POST['ollama_url']), FILTER_SANITIZE_URL);
    $auth_key = trim($_POST['auth_key']);
    $ai_models = array_map('trim', explode(',', $_POST['ai_models']));
    $ai_role = htmlspecialchars(trim($_POST['ai_role']), ENT_QUOTES, 'UTF-8');
    $ai_instruction = htmlspecialchars(trim($_POST['ai_instruction']), ENT_QUOTES, 'UTF-8');

    // Validate URL format
    if (!filter_var($ollama_url, FILTER_VALIDATE_URL)) {
        $error_message = "Invalid Ollama URL.";
    } else {
        $multi_handle = curl_multi_init();
        $handles = [];
        $responses = [];

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
            curl_setopt($ch, CURLOPT_HEADER, false);
            
            // Increase timeout settings
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Max execution time
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout

            // Store start time
            $start_time = microtime(true);
            curl_setopt($ch, CURLOPT_PRIVATE, $start_time);

            // Store the handle
            curl_multi_add_handle($multi_handle, $ch);
            $handles[$model] = $ch;
        }

        // Execute all requests asynchronously
        do {
            $status = curl_multi_exec($multi_handle, $active);
        } while ($status === CURLM_CALL_MULTI_PERFORM);

        while ($active && $status === CURLM_OK) {
            if (curl_multi_select($multi_handle, 5) !== -1) { // Use select with a timeout
                do {
                    $status = curl_multi_exec($multi_handle, $active);
                } while ($status === CURLM_CALL_MULTI_PERFORM);
            }
        }

        // Fetch responses
        foreach ($handles as $model => $ch) {
            $response = curl_multi_getcontent($ch);
            $decoded_response = json_decode($response, true);
            $output_text = $decoded_response['response'] ?? 'No response received';

            // Calculate response time
            $start_time = curl_getinfo($ch, CURLINFO_PRIVATE);
            $response_time = round(microtime(true) - $start_time, 2);

            $responses[] = [
                'model' => $model,
                'response' => $output_text,
                'time_taken' => $response_time . 's'
            ];

            curl_multi_remove_handle($multi_handle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi_handle);
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
        .table-responsive { max-height: 400px; overflow-y: auto; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
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
    
    <?php if (!empty($responses)): ?>
        <div class="container mt-4">
            <h3 class="text-center">Results</h3>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th>Response</th>
                            <th>Time Taken</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($responses as $result): ?>
                            <tr>
                                <td><?= htmlspecialchars($result['model']) ?></td>
                                <td><pre><?= htmlspecialchars($result['response']) ?></pre></td>
                                <td><?= htmlspecialchars($result['time_taken']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
