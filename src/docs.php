<?php
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redis CLI - Command Docs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="images/icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #181c20;
            color: #eee;
            font-family: 'Space Grotesk',ui-sans-serif,system-ui,sans-serif,apple color emoji,segoe ui emoji,segoe ui symbol,noto color emoji;
            font-size: 1.05rem;
        }
        .back-link { margin-bottom: 2rem; display: inline-block; }
        .group-title {
            position: sticky;
            top: 0;
            background: #181c20;
            z-index: 2;
            padding: 0.5rem 0 0.5rem 1rem;
            font-size: 1.2rem;
            color: #7fffd4;
            font-weight: 700;
            border-left: 4px solid #7fffd4;
            margin-bottom: 1rem;
        }
        .command-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.1rem;
            margin-bottom: 2.5rem;
        }
        .command-card {
            background: #23272e;
            border-radius: 8px;
            box-shadow: 0 2px 8px #0004;
            border: 1.5px solid #23272e;
            padding: 1rem 1rem 0.7rem 1rem;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        .command-name {
            color: #ffd700;
            font-weight: 700;
            font-size: 1.08rem;
            margin-bottom: 0.2em;
            letter-spacing: 0.5px;
        }
        .command-desc {
            color: #b0b0b0;
            font-size: 0.98em;
            margin-bottom: 0.3em;
        }
        .command-example {
            background: #181c20;
            color: #7fffd4;
            border-radius: 4px;
            padding: 0.2em 0.5em;
            font-size: 0.97em;
            margin-bottom: 0.1em;
            font-family: inherit;
            word-break: break-all;
        }
        .command-link {
            font-size: 0.95em;
            color: #7fffd4;
            text-decoration: underline;
        }
        @media (max-width: 600px) {
            .command-grid { grid-template-columns: 1fr; }
            .group-title { font-size: 1.08rem; }
        }
    </style>
</head>
<body class="container py-5">
    <h1 class="mb-4" style="font-weight:700;letter-spacing:1px;">redis-command-docs</h1>

    <a href="index.php" class="back-link" style="margin-bottom:2rem;display:inline-block; color:#7fffd4; text-decoration:underline; font-size:1.01rem;">&larr; Back to Redis CLI</a>

    <div class="group-title">Core Commands</div>
    <div class="command-grid">
        <div class="command-card"><div class="command-name">GET</div><div class="command-desc">Get the value of a key.</div><div class="command-example">GET key</div></div>
        <div class="command-card"><div class="command-name">SET</div><div class="command-desc">Set the value of a key.</div><div class="command-example">SET key value</div></div>
        <div class="command-card"><div class="command-name">DEL</div><div class="command-desc">Delete one or more keys.</div><div class="command-example">DEL key [key ...]</div></div>
        <div class="command-card"><div class="command-name">EXISTS</div><div class="command-desc">Check if a key exists.</div><div class="command-example">EXISTS key</div></div>
        <div class="command-card"><div class="command-name">KEYS</div><div class="command-desc">Find all keys matching a pattern.</div><div class="command-example">KEYS pattern</div></div>
        <div class="command-card"><div class="command-name">INCR / DECR</div><div class="command-desc">Increment or decrement the integer value of a key.</div><div class="command-example">INCR key<br>DECR key</div></div>
        <div class="command-card"><div class="command-name">EXPIRE</div><div class="command-desc">Set a timeout on a key.</div><div class="command-example">EXPIRE key seconds</div></div>
        <div class="command-card"><div class="command-name">TTL</div><div class="command-desc">Get the time to live for a key.</div><div class="command-example">TTL key</div></div>
        <div class="command-card"><div class="command-name">FLUSHDB / FLUSHALL</div><div class="command-desc">Delete all keys in the current database or all databases.</div><div class="command-example">FLUSHDB<br>FLUSHALL</div></div>
        <div class="command-card"><div class="command-name">HSET / HGET</div><div class="command-desc">Set or get fields in a hash.</div><div class="command-example">HSET key field value<br>HGET key field</div></div>
        <div class="command-card"><div class="command-name">LPUSH / RPUSH / LRANGE</div><div class="command-desc">Work with lists.</div><div class="command-example">LPUSH key value [value ...]<br>RPUSH key value [value ...]<br>LRANGE key start stop</div></div>
        <div class="command-card"><div class="command-name">SADD / SMEMBERS</div><div class="command-desc">Work with sets.</div><div class="command-example">SADD key member [member ...]<br>SMEMBERS key</div></div>
        <div class="command-card"><div class="command-name">ZADD / ZRANGE</div><div class="command-desc">Work with sorted sets.</div><div class="command-example">ZADD key score member [score member ...]<br>ZRANGE key start stop [WITHSCORES]</div></div>
    </div>

    <div class="group-title">RedisJSON</div>
    <div class="command-grid">
        <div class="command-card"><div class="command-name">JSON.SET</div><div class="command-desc">Set a JSON value at the specified path.</div><div class="command-example">JSON.SET user:1 . '{"name":"Alice","age":30}'</div></div>
        <div class="command-card"><div class="command-name">JSON.GET</div><div class="command-desc">Get a JSON value or sub-path.</div><div class="command-example">JSON.GET user:1<br>JSON.GET user:1 .name</div></div>
        <div class="command-card"><div class="command-name">JSON.DEL</div><div class="command-desc">Delete a value at path.</div><div class="command-example">JSON.DEL user:1 .name</div></div>
        <div class="command-card"><div class="command-name">JSON.ARRAPPEND</div><div class="command-desc">Append values to a JSON array.</div><div class="command-example">JSON.ARRAPPEND doc .foo 4 5</div></div>
        <div class="command-card"><div class="command-name">JSON.OBJKEYS</div><div class="command-desc">Get the keys in a JSON object.</div><div class="command-example">JSON.OBJKEYS user:1 .</div></div>
        <div class="command-card"><div class="command-name">JSON.NUMINCRBY</div><div class="command-desc">Increment a numeric value in a JSON document.</div><div class="command-example">JSON.NUMINCRBY user:1 .age 1</div></div>
    </div>

    <div class="group-title">RediSearch</div>
    <div class="command-grid">
        <div class="command-card"><div class="command-name">FT.CREATE</div><div class="command-desc">Create a full-text index.</div><div class="command-example">FT.CREATE myIdx ON HASH PREFIX 1 doc: SCHEMA title TEXT body TEXT</div></div>
        <div class="command-card"><div class="command-name">FT.SEARCH</div><div class="command-desc">Search an index.</div><div class="command-example">FT.SEARCH myIdx "hello world"</div></div>
        <div class="command-card"><div class="command-name">FT.AGGREGATE</div><div class="command-desc">Run aggregation queries.</div><div class="command-example">FT.AGGREGATE myIdx "hello" GROUPBY 1 @title REDUCE COUNT 0 AS count</div></div>
        <div class="command-card"><div class="command-name">FT.DROPINDEX</div><div class="command-desc">Drop an index.</div><div class="command-example">FT.DROPINDEX myIdx DD</div></div>
        <div class="command-card"><div class="command-name">FT.INFO</div><div class="command-desc">Get information about an index.</div><div class="command-example">FT.INFO myIdx</div></div>
    </div>
</body>
</html> 