<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd'])) {
    header('Content-Type: application/json');
    $cmd = trim($_POST['cmd']);
    if ($cmd === '') {
        echo json_encode(['result' => '']);
        exit;
    }
    // Escape đầu vào để tránh injection
    $escaped = escapeshellcmd($cmd);
    // Thêm -c để redis-cli tự động follow MOVED/ASK
    $fullCmd = 'redis-cli -c -h redis-node1 -p 7000 ' . $escaped . ' 2>&1';
    $output = shell_exec($fullCmd);
    echo json_encode(['result' => $output]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>redis-cli Web</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #181c20; color: #eee; }
        .terminal {
            background: #181c20;
            color: #eee;
            font-family: 'Fira Mono', 'Consolas', monospace;
            padding: 1rem;
            border-radius: 8px;
            min-height: 400px;
            max-height: 70vh;
            overflow-y: auto;
            margin-bottom: 1rem;
        }
        .prompt { color: #00ff00; }
        .cmd { color: #fff; }
        .result { color: #ffd700; white-space: pre-wrap; }
        .error { color: #ff5555; white-space: pre-wrap; }
        .input-line {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        #cli-input {
            background: #181c20;
            color: #fff;
            border: none;
            outline: none;
            font-family: 'Fira Mono', 'Consolas', monospace;
            font-size: 1rem;
            width: 100%;
        }
        .btn-clear {
            float: right;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="container py-5">
    <h1 class="mb-4">redis-cli Web</h1>
    <div class="terminal" id="terminal"></div>
    <form id="cli-form" autocomplete="off" onsubmit="return false;">
        <div class="input-line">
            <span class="prompt">redis-cli&gt;</span>
            <input type="text" id="cli-input" autofocus autocomplete="off" spellcheck="false" placeholder="Nhập lệnh redis-cli, ví dụ: KEYS *">
        </div>
    </form>
    <button class="btn btn-sm btn-danger btn-clear" onclick="clearTerminal()">Xóa terminal</button>
    <script>
        const terminal = document.getElementById('terminal');
        const cliInput = document.getElementById('cli-input');
        let history = [];
        let historyIndex = 0;

        function appendLine(cmd, result, isError = false) {
            const cmdLine = document.createElement('div');
            cmdLine.innerHTML = `<span class='prompt'>redis-cli&gt; </span><span class='cmd'>${escapeHtml(cmd)}</span>`;
            terminal.appendChild(cmdLine);
            if (result !== undefined && result !== null && result !== '') {
                const resLine = document.createElement('div');
                resLine.className = isError ? 'error' : 'result';
                resLine.innerHTML = escapeHtml(result);
                terminal.appendChild(resLine);
            }
            terminal.scrollTop = terminal.scrollHeight;
        }

        function escapeHtml(text) {
            return text.replace(/[&<>"]/g, function(m) {
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]);
            });
        }

        document.getElementById('cli-form').addEventListener('submit', sendCmd);
        cliInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                sendCmd();
            } else if (e.key === 'ArrowUp') {
                if (history.length > 0 && historyIndex > 0) {
                    historyIndex--;
                    cliInput.value = history[historyIndex];
                    setTimeout(() => cliInput.setSelectionRange(cliInput.value.length, cliInput.value.length), 0);
                }
            } else if (e.key === 'ArrowDown') {
                if (history.length > 0 && historyIndex < history.length - 1) {
                    historyIndex++;
                    cliInput.value = history[historyIndex];
                    setTimeout(() => cliInput.setSelectionRange(cliInput.value.length, cliInput.value.length), 0);
                } else if (historyIndex === history.length - 1) {
                    historyIndex++;
                    cliInput.value = '';
                }
            }
        });

        function sendCmd() {
            const cmd = cliInput.value.trim();
            if (!cmd) return;
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'cmd=' + encodeURIComponent(cmd)
            })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    appendLine(cmd, data.error, true);
                } else {
                    appendLine(cmd, data.result);
                }
            });
            history.push(cmd);
            if (history.length > 50) history = history.slice(-50);
            historyIndex = history.length;
            cliInput.value = '';
        }

        function clearTerminal() {
            terminal.innerHTML = '';
            history = [];
            historyIndex = 0;
            cliInput.value = '';
            cliInput.focus();
        }
    </script>
</body>
</html>
