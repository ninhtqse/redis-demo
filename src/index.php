<?php
// Connect to Redis Cluster
$seeds = [
    'redis-node1:7000',
    'redis-node2:7001',
    'redis-node3:7002',
    'redis-node4:7003',
    'redis-node5:7004',
    'redis-node6:7005'
];
try {
    $redis = new RedisCluster(null, $seeds, 1.5, 1.5, true);
    $status = 'Redis Cluster connection successful!';
} catch (Exception $e) {
    $status = 'Redis Cluster connection failed: ' . $e->getMessage();
}

$cliResult = '';
$getSetResult = '';
$key = '';
$value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'getset') {
        $key = $_POST['key'] ?? '';
        $value = $_POST['value'] ?? '';
        $mode = $_POST['mode'] ?? 'get';
        try {
            $redis = new RedisCluster(null, $seeds, 1.5, 1.5, true);
            if ($mode === 'get') {
                $val = $redis->get($key);
                if ($val !== false) {
                    $getSetResult = "Giá trị của key '{$key}': {$val}";
                } else {
                    $getSetResult = "Không tìm thấy key '{$key}' trong Redis Cluster";
                }
            } elseif ($mode === 'set') {
                $redis->set($key, $value);
                $getSetResult = "Đã set key '{$key}' với giá trị '{$value}' thành công.";
            }
        } catch (Exception $e) {
            $getSetResult = 'Lỗi: ' . $e->getMessage();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'cli') {
        $cliCmd = $_POST['cli_cmd'] ?? '';
        try {
            $redis = new RedisCluster(null, $seeds, 1.5, 1.5, true);
            if ($cliCmd) {
                $parts = preg_split('/\s+/', trim($cliCmd));
                $cmd = array_shift($parts);
                $result = $redis->rawCommand($cmd, ...$parts);
                if (is_array($result)) {
                    $cliResult = print_r($result, true);
                } elseif (is_bool($result)) {
                    $cliResult = $result ? 'OK' : 'FALSE';
                } elseif ($result === null) {
                    $cliResult = 'NULL';
                } else {
                    $cliResult = (string)$result;
                }
            }
        } catch (Exception $e) {
            $cliResult = 'Lỗi: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Redis Cluster Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
    <h1 class="mb-4">Redis Cluster Demo</h1>
    <div class="alert alert-info">
        <?= htmlspecialchars($status) ?>
    </div>
    <?php if (!empty($getSetResult)): ?>
        <div class="alert alert-success"> <?= htmlspecialchars($getSetResult) ?> </div>
    <?php endif; ?>
    <form method="post" class="mb-4">
        <input type="hidden" name="action" value="getset">
        <div class="row mb-2">
            <div class="col-md-3">
                <input type="text" name="key" class="form-control" placeholder="Key" value="<?= htmlspecialchars($key) ?>" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="value" class="form-control" placeholder="Value (chỉ dùng khi SET)">
            </div>
            <div class="col-md-2">
                <select name="mode" class="form-select">
                    <option value="get">GET</option>
                    <option value="set">SET</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Thực hiện</button>
            </div>
        </div>
    </form>
    <hr>
    <h3>Chạy lệnh redis-cli</h3>
    <form method="post" class="mb-3">
        <input type="hidden" name="action" value="cli">
        <div class="row mb-2">
            <div class="col-md-8">
                <input type="text" name="cli_cmd" class="form-control" placeholder="Nhập lệnh redis-cli, ví dụ: KEYS *" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary">Chạy lệnh</button>
            </div>
        </div>
    </form>
    <?php if (!empty($cliResult)): ?>
        <pre class="bg-light p-3 border"> <?= htmlspecialchars($cliResult) ?> </pre>
    <?php endif; ?>
</body>
</html>
