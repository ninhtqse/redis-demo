<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd'])) {
    header('Content-Type: application/json');
    $cmd = trim($_POST['cmd']);
    if ($cmd === '') {
        echo json_encode(['result' => '']);
        exit;
    }
    // Pass command via stdin to keep JSON and special characters
    $fullCmd = 'redis-cli -c -h redis-node1 -p 7000 --raw 2>&1';
    $descriptorspec = [
        0 => ["pipe", "r"], // stdin
        1 => ["pipe", "w"], // stdout
        2 => ["pipe", "w"]  // stderr
    ];
    $process = proc_open($fullCmd, $descriptorspec, $pipes);
    if (is_resource($process)) {
        fwrite($pipes[0], $cmd . "\n");
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        proc_close($process);
        $result = trim($output . $error);
        // Hide redirect line
        $result = preg_replace('/^-> Redirected to slot.*\n?/m', '', $result);
        if ($result === '' || strtolower($result) === 'null') {
            $result = "(nil)";
        }
        echo json_encode(['result' => $result]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redis CLI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="images/icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #181c20;
            color: #eee;
            font-family: 'Space Grotesk',ui-sans-serif,system-ui,sans-serif,apple color emoji,segoe ui emoji,segoe ui symbol,noto color emoji;
            font-size: 1.08rem;
        }
        .terminal {
            background: #181c20;
            color: #eee;
            font-family: 'Space Grotesk',ui-sans-serif,system-ui,sans-serif,apple color emoji,segoe ui emoji,segoe ui symbol,noto color emoji;
            padding: 1.5rem 1rem 1rem 1rem;
            border-radius: 12px;
            min-height: 400px;
            max-height: 70vh;
            overflow-y: auto;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 24px 0 #0008;
            border: 1.5px solid #23272e;
            font-size: 1.04rem;
        }
        .prompt {
            color: #00ff00;
            font-weight: bold;
            width: 9%;
            font-size: 1.08rem;
        }
        .cmd {
            color: #fff;
            font-weight: 500;
        }
        .result {
            color: #7fffd4;
            white-space: pre-wrap;
            margin-left: 0.5em;
        }
        .result.ok, .result.pong, .result.number {
            color: #00ff00;
        }
        .result.null {
            color: #888;
        }
        .error {
            color: #ff5555;
            white-space: pre-wrap;
            margin-left: 1.5em;
        }
        .input-line {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #23272e;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            box-shadow: 0 2px 8px #0004;
        }
        #cli-input {
            background: transparent;
            color: #fff;
            border: none;
            outline: none;
            font-family: 'Space Grotesk',ui-sans-serif,system-ui,sans-serif,apple color emoji,segoe ui emoji,segoe ui symbol,noto color emoji;
            font-size: 1.08rem;
            width: 100%;
            padding: 0.4rem 0;
            padding-left: 1rem;
        }
        #cli-input:focus {
            background: #181c20;
        }
        .btn-clear {
            float: right;
            margin-bottom: 1rem;
        }
        .usage {
            font-size: 0.98rem;
            color: #aaa;
            margin-bottom: 1rem;
        }
        @media (max-width: 600px) {
            .terminal { font-size: 0.93rem; padding: 1rem 0.5rem; }
            .input-line { padding: 0.5rem 0.5rem; }
            #cli-input { font-size: 0.98rem; }
            .prompt { font-size: 0.98rem; }
        }
    </style>
</head>
<body class="container py-5">
    <h1 class="mb-4" style="font-weight:700;letter-spacing:1px;">redis-cli</h1>
    <div class="usage">
        Type Redis commands like <b>redis-cli</b> (e.g. <code>SET foo bar</code>, <code>GET foo</code>, <code>KEYS *</code>, ...).<br>
        <b>Arrow ↑/↓</b> to navigate history, <b>Ctrl+L</b> to clear terminal.
    </div>
    <div class="terminal" id="terminal"></div>
    <form id="cli-form" autocomplete="off" onsubmit="return false;">
        <div class="input-line">
            <span class="prompt">redis-cli&gt;</span>
            <input type="text" id="cli-input" autofocus autocomplete="off" spellcheck="false" placeholder="Type redis-cli command, e.g. KEYS *">
        </div>
    </form>
    <button class="btn btn-sm btn-danger btn-clear mt-1" onclick="clearTerminal()">Clear</button>
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
                if (isError) {
                    resLine.className = 'error';
                } else {
                    resLine.className = 'result';
                    // Highlight OK, PONG, number, NULL, (nil)
                    if (/^ok$/i.test(result.trim())) resLine.classList.add('ok');
                    if (/^pong$/i.test(result.trim())) resLine.classList.add('pong');
                    if (/^-?\d+(\.\d+)?$/.test(result.trim())) resLine.classList.add('number');
                    if (/^null$/i.test(result.trim()) || /^\(nil\)$/i.test(result.trim())) resLine.classList.add('null');
                }
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
            } else if (e.ctrlKey && e.key.toLowerCase() === 'l') {
                clearTerminal();
                e.preventDefault();
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
