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
        die("<div class='alert alert-danger'>Invalid Ollama URL.</div>");
    }

    // Set HTTP headers for streaming output
    header('Content-Type: text/html; charset=UTF-8');
    ob_implicit_flush(true);
    ob_end_flush();

    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <title>Ollama AI Benchmark</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <style>
            body { background-color: #f8f9fa; }
            .container { max-width: 700px; margin-top: 50px; }
            .card { border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
            .alert { border-radius: 5px; }
            .result-box { background: #fff; padding: 10px; border-radius: 5px; margin-top: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        </style>
    </head>
    <body>
    <div class='container'>
        <div class='card p-4'>
            <h2 class='mb-4 text-center'>Ollama AI Benchmark</h2>
            <h4>Processing requests...</h4>
            <div id='results'></div>
        </div>
    </div>
    <script>
        function appendResult(model, response, timeTaken) {
            let resultDiv = document.getElementById('results');
            let newResult = document.createElement('div');
            newResult.classList.add('result-box');
            newResult.innerHTML = '<strong>Model:</strong> ' + model + '<br><strong>Response:</strong> <pre>' + response + '</pre><br><strong>Time Taken:</strong> ' + timeTaken + 's';
            resultDiv.appendChild(newResult);
        }
    </script>
    ";

    // Process each model sequentially
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $start_time = microtime(true);
        $response = curl_exec($ch);
        $response_time = round(microtime(true) - $start_time, 2);
        curl_close($ch);

        $decoded_response = json_decode($response, true);
        $output_text = $decoded_response['response'] ?? 'No response received';

        echo "<script>appendResult(" . json_encode($model) . ", " . json_encode($output_text) . ", " . json_encode($response_time) . ");</script>";
        flush();
        usleep(500000); // Slight delay to prevent overloading the server
    }

    echo "</body></html>";
    exit;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="card p-4">
            <h2 class="mb-4 text-center">Ollama AI Benchmark</h2>
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
