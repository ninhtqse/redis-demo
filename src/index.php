<?php
/** @var RedisCluster $redis */
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
</body>
</html>
