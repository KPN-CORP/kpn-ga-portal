<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View PDF</title>
    <style>
        body { margin: 0; padding: 0; height: 100vh; display: flex; align-items: center; justify-content: center; background: #f1f5f9; }
        .pdf-container { width: 100%; height: 100vh; }
        embed { width: 100%; height: 100%; border: none; }
    </style>
</head>
<body>
    <div class="pdf-container">
        <embed src="data:{{ $mime }};base64,{{ $content }}" type="{{ $mime }}" width="100%" height="100%">
    </div>
</body>
</html>