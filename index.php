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
        // Start output buffering for real-time updates
        ob_start();
        echo "<html><head><title>Ollama AI Benchmark</title>";
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo "<style>
                body { background-color: #f8f9fa; }
                .container { max-width: 700px; margin-top: 50px; }
                .card { border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); padding: 20px; }
                .response-card { margin-top: 10px; padding: 15px; border-radius: 5px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .spinner { margin-left: 10px; display: inline-block; }
              </style>";
        echo "</head><body>";
        echo "<div class='container'><div class='card'><h2 class='text-center'>Ollama AI Benchmark</h2>";
        echo "<p>Processing models... Please wait.</p></div></div>";
        echo "<div class='container' id='results'></div>";
        ob_flush();
        flush();

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

            // Initialize cURL
            $ch = curl_init("$ollama_url/api/generate");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Maximum execution time per request
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); // Connection timeout

            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            // Calculate response time
            $response_time = round(microtime(true) - $start_time, 2);

            // Handle error or process response
            if ($error) {
                $output_text = "Error: " . htmlspecialchars($error);
            } else {
                $decoded_response = json_decode($response, true);
                $output_text = $decoded_response['response'] ?? 'No response received';
            }

            // Output the result immediately
            echo "<script>
                    var resultContainer = document.getElementById('results');
                    var newCard = document.createElement('div');
                    newCard.classList.add('response-card');
                    newCard.innerHTML = '<strong>Model:</strong> " . htmlspecialchars($model) . 
                                        "<br><strong>Response Time:</strong> {$response_time}s" . 
                                        "<br><strong>Response:</strong><pre>" . htmlspecialchars($output_text) . "</pre>';
                    resultContainer.appendChild(newCard);
                  </script>";
            ob_flush();
            flush();
        }

        echo "</body></html>";
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
</body>
</html>
