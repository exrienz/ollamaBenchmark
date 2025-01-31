<?php
error_reporting = E_ALL & ~E_WARNING
display_errors = Off
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Previous sanitization code remains the same
    $ollama_url = filter_var(rtrim(trim($_POST['ollama_url']), '/'), FILTER_SANITIZE_URL);
    $auth_key = trim($_POST['auth_key']);
    $ai_models = array_filter(array_map('trim', explode(',', $_POST['ai_models'])));
    $ai_role = htmlspecialchars(trim($_POST['ai_role']), ENT_QUOTES, 'UTF-8');
    $ai_instruction = htmlspecialchars(trim($_POST['ai_instruction']), ENT_QUOTES, 'UTF-8');

    if (!filter_var($ollama_url, FILTER_VALIDATE_URL)) {
        $error_message = "Invalid Ollama URL.";
    } else {
        // Output buffering and HTML header code remains the same
        ob_start();
        echo "<html><head><title>Ollama AI Benchmark</title>";
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo "<style>
                body { background-color: #f8f9fa; }
                .container { max-width: 800px; margin-top: 50px; }
                .card { border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); padding: 20px; }
                .response-card { margin-top: 10px; padding: 15px; border-radius: 5px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .spinner { margin-left: 10px; display: inline-block; }
                .error-text { color: #dc3545; }
                .success-text { color: #198754; }
              </style>";
        echo "</head><body>";
        echo "<div class='container'><div class='card'><h2 class='text-center'>Ollama AI Benchmark</h2>";
        echo "<p>Processing models... Please wait.</p></div></div>";
        echo "<div class='container' id='results'></div>";
        ob_flush();
        flush();

        foreach ($ai_models as $model) {
            $start_time = microtime(true);
            
            try {
                // Use the /api/generate endpoint instead of /api/chat
                $data = [
                    'model' => $model,
                    'prompt' => $ai_instruction,
                    'stream' => false
                ];

                $headers = [
                    "Content-Type: application/json",
                    "Accept: application/json"
                ];
                
                if (!empty($auth_key)) {
                    $headers[] = "Authorization: Bearer $auth_key";
                }

                // Initialize cURL
                $ch = curl_init("$ollama_url/api/generate");
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($data),
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_HEADER => false,
                    CURLOPT_TIMEOUT => 360,
                    CURLOPT_CONNECTTIMEOUT => 90,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_ENCODING => '',
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
                ]);

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                $curl_errno = curl_errno($ch);
                curl_close($ch);

                // Calculate response time
                $response_time = round(microtime(true) - $start_time, 2);

                // Handle response
                if ($curl_errno || $http_code >= 400) {
                    $status_class = 'error-text';
                    $output_text = "Error: " . ($curl_error ?: "HTTP Status $http_code");
                    if ($curl_errno === CURLE_OPERATION_TIMEOUTED) {
                        $output_text = "Request timed out after {$response_time}s. Consider increasing timeout values.";
                    }
                } else {
                    $decoded_response = json_decode($response, true);
                    // Directly access the 'response' field for /api/generate endpoint
                    $output_text = $decoded_response['response'] ?? 'No response content';
                    $status_class = 'success-text';
                }

                // Output the result
                echo "<script>
                        var resultContainer = document.getElementById('results');
                        var newCard = document.createElement('div');
                        newCard.classList.add('response-card');
                        newCard.innerHTML = `
                            <div class='mb-2'><strong>Model:</strong> " . htmlspecialchars($model) . "</div>
                            <div class='mb-2'><strong>Prompt:</strong><pre class='mt-2'>" . 
                                htmlspecialchars($ai_instruction) . "</pre></div>
                            <div class='mb-2 ${status_class}'><strong>Status:</strong> " . 
                                ($curl_errno || $http_code >= 400 ? 'Failed' : 'Success') . "</div>
                            <div class='mb-2'><strong>Response Time:</strong> {$response_time}s</div>
                            <div><strong>Response:</strong><pre class='mt-2'>" . 
                                htmlspecialchars($output_text) . "</pre></div>
                        `;
                        resultContainer.appendChild(newCard);
                      </script>";
                ob_flush();
                flush();
                
                // Add a small delay between requests
                usleep(500000); // 0.5 second delay

            } catch (Exception $e) {
                echo "<script>
                        var resultContainer = document.getElementById('results');
                        var newCard = document.createElement('div');
                        newCard.classList.add('response-card');
                        newCard.innerHTML = '<div class=\"error-text\"><strong>Error processing " . 
                            htmlspecialchars($model) . ":</strong> " . 
                            htmlspecialchars($e->getMessage()) . "</div>';
                        resultContainer.appendChild(newCard);
                      </script>";
                ob_flush();
                flush();
            }
        }

        echo "</body></html>";
    }
}
?>
<!DOCTYPE html>
<!-- The HTML form part remains exactly the same as before -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ollama AI Benchmark</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 800px; margin-top: 50px; }
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
                    <input type="url" name="ollama_url" class="form-control" placeholder="http://localhost:11434" 
                           value="<?= isset($ollama_url) ? htmlspecialchars($ollama_url) : '' ?>" required>
                    <div class="form-text">Example: http://localhost:11434</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Auth Key (if any)</label>
                    <input type="text" name="auth_key" class="form-control" 
                           value="<?= isset($auth_key) ? htmlspecialchars($auth_key) : '' ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">AI Models (comma-separated)</label>
                    <input type="text" name="ai_models" class="form-control" placeholder="llama2,mistral,neural-chat" 
                           value="<?= isset($_POST['ai_models']) ? htmlspecialchars($_POST['ai_models']) : '' ?>" required>
                    <div class="form-text">Example: llama2,mistral,neural-chat</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">AI Role</label>
                    <input type="text" name="ai_role" class="form-control" placeholder="user" 
                           value="<?= isset($ai_role) ? htmlspecialchars($ai_role) : '' ?>" required>
                    <div class="form-text">Example: user, system, assistant</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">AI Instruction</label>
                    <textarea name="ai_instruction" class="form-control" rows="3" placeholder="Enter your prompt here" 
                              required><?= isset($ai_instruction) ? htmlspecialchars($ai_instruction) : '' ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Run Benchmark</button>
            </form>
        </div>
    </div>
</body>
</html>
