<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd'])) {
    header('Content-Type: application/json');
    $cmd = trim($_POST['cmd']);
    if ($cmd === '') {
        echo json_encode(['result' => '']);
        exit;
    }
    if (preg_match('/^\s*(SUBSCRIBE|PSUBSCRIBE|SSUBSCRIBE)\b/i', $cmd)) {
        echo json_encode(['result' => '', 'error' => 'SUBSCRIBE command is not supported on web CLI. Please use real redis-cli to receive realtime messages.']);
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
            max-height: 55vh;
            overflow-y: auto;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 24px 0 #0008;
            border: 1.5px solid #23272e;
            font-size: 1.04rem;
        }
        /* Custom scrollbar for terminal */
        .terminal::-webkit-scrollbar {
            width: 10px;
            background: #181c20;
        }
        .terminal::-webkit-scrollbar-thumb {
            background: #23272e;
            border-radius: 8px;
        }
        .terminal::-webkit-scrollbar-track {
            background: #181c20;
        }
        .terminal {
            scrollbar-color: #23272e #181c20;
            scrollbar-width: thin;
        }
        .prompt {
            color: #00ff00;
            font-weight: bold;
            width: 6.5%;
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
    <a href="docs.php" style="margin-bottom:2rem;display:inline-block; color:#7fffd4; text-decoration:underline; font-size:1.01rem;">&rarr; Command Docs</a>
    <div class="terminal" id="terminal"></div>
    <form id="cli-form" autocomplete="off" onsubmit="return false;">
        <div class="input-line" style="position:relative;">
            <span class="prompt">redis-cli&gt;</span>
            <div style="position:relative;flex:1;min-width:0;">
                <span id="cli-ghost" style="position:absolute;left:0;top:0;width:100%;height:100%;color:#aaa;opacity:0.5;pointer-events:none;white-space:pre;overflow:hidden;font-size:1.08rem;font-family:'Space Grotesk',ui-sans-serif,system-ui,sans-serif,apple color emoji,segoe ui emoji,segoe ui symbol,noto color emoji;line-height:1.5;letter-spacing:0.5px;padding:0.4rem 0 0.4rem 1rem;z-index:1;"></span>
                <input type="text" id="cli-input" autofocus autocomplete="off" spellcheck="false" placeholder="Type redis-cli command, e.g. KEYS *" style="background:transparent;color:#fff;position:relative;z-index:2;width:100%;border:none;outline:none;font-size:1.08rem;font-family:'Space Grotesk',ui-sans-serif,system-ui,sans-serif,apple color emoji,segoe ui emoji,segoe ui symbol,noto color emoji;line-height:1.5;letter-spacing:0.5px;padding:0.4rem 0 0.4rem 1rem;">
            </div>
            <ul id="cli-suggest" style="position:absolute;left:110px;top:100%;z-index:10;background:#23272e;color:#fff;border-radius:6px;box-shadow:0 2px 8px #0004;margin:0;padding:0;list-style:none;min-width:180px;max-height:220px;overflow-y:auto;display:none;font-size:1.01rem;border:1px solid #23272e;"></ul>
        </div>
    </form>
    <button class="btn btn-sm btn-danger btn-clear mt-1" onclick="clearTerminal()">Clear</button>
    <script>
        const terminal = document.getElementById('terminal');
        const cliInput = document.getElementById('cli-input');
        const cliSuggest = document.getElementById('cli-suggest');
        let ws;
        let history = [];
        let historyIndex = 0;
        let suggestList = [
            // Core
            'GET','SET','DEL','EXISTS','KEYS','INCR','DECR','EXPIRE','TTL','FLUSHDB','FLUSHALL','HSET','HGET','HDEL','HGETALL','LPUSH','RPUSH','LRANGE','SADD','SMEMBERS','ZADD','ZRANGE','ZREM','ZCARD','PING','ECHO','AUTH','SELECT','MOVE','RENAME','TYPE','SCAN','DBSIZE','INFO','CONFIG','CLIENT','MONITOR','SUBSCRIBE','UNSUBSCRIBE','PUBLISH','PSUBSCRIBE','PUBSUB',
            // RedisJSON
            'JSON.GET','JSON.SET','JSON.DEL','JSON.ARRAPPEND','JSON.OBJKEYS','JSON.NUMINCRBY','JSON.MGET','JSON.STRAPPEND','JSON.STRLEN','JSON.TYPE','JSON.ARRLEN','JSON.ARRPOP','JSON.ARRTRIM','JSON.CLEAR','JSON.DEBUG','JSON.FORGET','JSON.RESP','JSON.TOGGLE',
            // RediSearch
            'FT.CREATE','FT.SEARCH','FT.AGGREGATE','FT.DROPINDEX','FT.INFO','FT.ALTER','FT.ADD','FT.SUGADD','FT.SUGGET','FT.SUGDEL','FT.SUGLEN','FT.EXPLAIN','FT.TAGVALS','FT.SYNUPDATE','FT.SYNDUMP','FT.DICTADD','FT.DICTDEL','FT.DICTDUMP','FT.SPELLCHECK','FT.DEL','FT.GET','FT.BULK','FT.SYNADD','FT.SYNUPDATE','FT.SYNDUMP','FT.SPELLCHECK','FT.EXPLAINCLI','FT.PROFILE','FT.SEARCH','FT.AGGREGATE','FT.DROPINDEX','FT.INFO'
        ];
        let suggestFiltered = [];
        let suggestIndex = -1;

        // Usage mapping for inline suggestion
        const usageMap = {
            'GET': 'key',
            'SET': 'key value',
            'DEL': 'key [key ...]',
            'EXISTS': 'key',
            'KEYS': 'pattern',
            'INCR': 'key',
            'DECR': 'key',
            'EXPIRE': 'key seconds',
            'TTL': 'key',
            'FLUSHDB': '',
            'FLUSHALL': '',
            'HSET': 'key field value',
            'HGET': 'key field',
            'HDEL': 'key field [field ...]',
            'HGETALL': 'key',
            'LPUSH': 'key value [value ...]',
            'RPUSH': 'key value [value ...]',
            'LRANGE': 'key start stop',
            'SADD': 'key member [member ...]',
            'SMEMBERS': 'key',
            'ZADD': 'key score member [score member ...]',
            'ZRANGE': 'key start stop [WITHSCORES]',
            'ZREM': 'key member [member ...]',
            'ZCARD': 'key',
            'PING': '',
            'ECHO': 'message',
            'AUTH': 'password',
            'SELECT': 'index',
            'MOVE': 'key db',
            'RENAME': 'key newkey',
            'TYPE': 'key',
            'SCAN': 'cursor [MATCH pattern] [COUNT count]',
            'DBSIZE': '',
            'INFO': '[section]',
            'CONFIG': 'subcommand [args]',
            'CLIENT': 'subcommand [args]',
            'MONITOR': '',
            'SUBSCRIBE': 'channel [channel ...]',
            'UNSUBSCRIBE': '[channel [channel ...]]',
            'PUBLISH': 'channel message',
            'PSUBSCRIBE': 'pattern [pattern ...]',
            'PUBSUB': 'subcommand [argument [argument ...]]',
            // RedisJSON
            'JSON.GET': 'key [path]',
            'JSON.SET': 'key path value',
            'JSON.DEL': 'key [path]',
            'JSON.ARRAPPEND': 'key path value [value ...]',
            'JSON.OBJKEYS': 'key [path]',
            'JSON.NUMINCRBY': 'key path number',
            'JSON.MGET': 'key [key ...] path',
            'JSON.STRAPPEND': 'key path value',
            'JSON.STRLEN': 'key [path]',
            'JSON.TYPE': 'key [path]',
            'JSON.ARRLEN': 'key [path]',
            'JSON.ARRPOP': 'key path [index]',
            'JSON.ARRTRIM': 'key path start stop',
            'JSON.CLEAR': 'key [path]',
            'JSON.DEBUG': 'subcommand key [path]',
            'JSON.FORGET': 'key [path]',
            'JSON.RESP': 'key [path]',
            'JSON.TOGGLE': 'key path',
            // RediSearch
            'FT.CREATE': 'index [ON HASH|JSON] ...',
            'FT.SEARCH': 'index query [options]',
            'FT.AGGREGATE': 'index query [options]',
            'FT.DROPINDEX': 'index [DD]',
            'FT.INFO': 'index',
            'FT.ALTER': 'index SCHEMA ...',
            'FT.ADD': 'index doc_id score [fields]',
            'FT.SUGADD': 'key string score [INCR] [PAYLOAD payload]',
            'FT.SUGGET': 'key prefix [FUZZY] [MAX num]',
            'FT.SUGDEL': 'key string',
            'FT.SUGLEN': 'key',
            'FT.EXPLAIN': 'index query',
            'FT.TAGVALS': 'index field',
            'FT.SYNUPDATE': 'index group_id term [term ...]',
            'FT.SYNDUMP': 'index',
            'FT.DICTADD': 'dict term [term ...]',
            'FT.DICTDEL': 'dict term [term ...]',
            'FT.DICTDUMP': 'dict',
            'FT.SPELLCHECK': 'index query [options]',
            'FT.DEL': 'index doc_id',
            'FT.GET': 'index doc_id',
            'FT.BULK': 'index ...',
            'FT.SYNADD': 'index group_id term [term ...]',
            'FT.SYNUPDATE': 'index group_id term [term ...]',
            'FT.SYNDUMP': 'index',
            'FT.SPELLCHECK': 'index query [options]',
            'FT.EXPLAINCLI': 'index query',
            'FT.PROFILE': 'index query [options]'
        };
        const cliUsage = document.getElementById('cli-usage');
        const cliGhost = document.getElementById('cli-ghost');

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
        cliInput.addEventListener('input', function(e) {
            showSuggest(this.value);
            showUsageGhost(this.value);
        });
        cliInput.addEventListener('keydown', function(e) {
            if (cliSuggest.style.display === 'block') {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    suggestIndex = (suggestIndex + 1) % suggestFiltered.length;
                    updateSuggestActive();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    suggestIndex = (suggestIndex - 1 + suggestFiltered.length) % suggestFiltered.length;
                    updateSuggestActive();
                } else if (e.key === 'Enter') {
                    if (suggestIndex >= 0 && suggestFiltered[suggestIndex]) {
                        cliInput.value = suggestFiltered[suggestIndex] + ' ';
                        hideSuggest();
                        setTimeout(() => {
                            showSuggest(cliInput.value);
                            showUsageGhost(cliInput.value);
                        }, 0);
                        e.preventDefault();
                        return;
                    }
                } else if (e.key === 'Escape') {
                    hideSuggest();
                }
            } else {
                // Process command history browsing when there is no suggestion
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (history.length > 0 && historyIndex > 0) {
                        historyIndex--;
                        cliInput.value = history[historyIndex];
                        setTimeout(() => cliInput.setSelectionRange(cliInput.value.length, cliInput.value.length), 0);
                    }
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (history.length > 0 && historyIndex < history.length - 1) {
                        historyIndex++;
                        cliInput.value = history[historyIndex];
                        setTimeout(() => cliInput.setSelectionRange(cliInput.value.length, cliInput.value.length), 0);
                    } else if (historyIndex === history.length - 1) {
                        historyIndex++;
                        cliInput.value = '';
                    }
                }
            }
            setTimeout(() => showUsageGhost(cliInput.value), 0);
        });
        cliInput.addEventListener('blur', function() {
            setTimeout(hideSuggest, 150);
        });
        cliSuggest.addEventListener('mousedown', function(e) {
            if (e.target.tagName === 'LI') {
                cliInput.value = e.target.textContent + ' ';
                hideSuggest();
                cliInput.focus();
                setTimeout(() => {
                    showSuggest(cliInput.value);
                    showUsageGhost(cliInput.value);
                }, 0);
            }
        });
        function showSuggest(val) {
            const v = val.trim().toUpperCase();
            if (!v) { hideSuggest(); return; }
            suggestFiltered = suggestList.filter(cmd => cmd.startsWith(v) || cmd.includes(v)).slice(0, 15);
            if (suggestFiltered.length === 0) { hideSuggest(); return; }
            cliSuggest.innerHTML = suggestFiltered.map((cmd, i) => `<li style="padding:0.3em 1em;cursor:pointer;${i===suggestIndex?'background:#444;':''}">${cmd}</li>`).join('');
            cliSuggest.style.display = 'block';
            suggestIndex = -1;
        }
        function hideSuggest() {
            cliSuggest.style.display = 'none';
            suggestFiltered = [];
            suggestIndex = -1;
        }
        function updateSuggestActive() {
            Array.from(cliSuggest.children).forEach((li, i) => {
                li.style.background = (i === suggestIndex) ? '#444' : '';
            });
        }
        function showUsageGhost(val) {
            const v = val.trim().toUpperCase();
            let usage = '';
            if (v && usageMap[v]) {
                usage = usageMap[v];
            } else {
                const firstWord = v.split(' ')[0];
                if (usageMap[firstWord] && (v === firstWord || v === firstWord + '')) {
                    usage = usageMap[firstWord];
                }
            }
            // Chỉ hiện usage nếu user chưa nhập tham số (chỉ có lệnh hoặc lệnh + dấu cách)
            let show = usage && (val.trim().split(/\s+/).length <= 1 || /\s$/.test(val));
            if (show) {
                // Hiện usage ngay sau text user đã nhập
                let prefix = val;
                if (!/\s$/.test(val)) prefix += ' ';
                cliGhost.textContent = prefix + usage;
            } else {
                cliGhost.textContent = '';
            }
        }
        function sendCmd() {
            const cmd = cliInput.value.trim();
            if (!cmd) return;
            // Nếu là SUBSCRIBE thì dùng WebSocket
            if (/^SUBSCRIBE\s+\S+/i.test(cmd)) {
                const channel = cmd.split(/\s+/)[1];
                appendLine('SUBSCRIBE ' + channel, 'Waiting for messages...');
                startWebSocketSubscribe(channel);
                history.push(cmd);
                if (history.length > 50) history = history.slice(-50);
                historyIndex = history.length;
                cliInput.value = '';
                return;
            }
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

        function startWebSocketSubscribe(channel) {
            if (!ws || ws.readyState === WebSocket.CLOSED) {
                ws = new WebSocket('ws://localhost:8089');
                ws.onopen = function() {
                    ws.send(JSON.stringify({cmd: 'SUBSCRIBE', channel: channel}));
                };
                ws.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    if (data.channel && data.message) {
                        appendLine('SUBSCRIBE ' + data.channel, data.message);
                    } else if (data.info) {
                        appendLine('INFO', data.info);
                    }
                };
                ws.onclose = function() {
                    appendLine('INFO', 'WebSocket closed');
                };
            } else if (ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({cmd: 'SUBSCRIBE', channel: channel}));
            }
        }
    </script>
</body>
<footer style="position:fixed;left:0;right:0;bottom:0;z-index:1000;">
    <div style="text-align:center;color:#888;font-size:0.92rem;padding:0.4rem 0 0.2rem 0;user-select:none;">&copy; <?php echo date('Y'); ?> ninhtqse. All rights reserved.</div>
</footer>
</html>
