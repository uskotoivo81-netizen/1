<?php
// template_network_monitor.php
// Шаблон имитирует панель мониторинга сетевого трафика

function generateFullPageHtml($content, $recentPages, $pagination, $updates, $popular, $edits) {
    global $rootUrl;

    // --- 1. ГЕНЕРАЦИЯ ФЕЙКОВЫХ ЛОГОВ ---
    $logRows = [];
    
    // Парсим реальные ссылки из контента (предполагаем, что они уже в HTML, но нам нужен массив)
    // В текущей архитектуре $content['body'] это уже HTML список. 
    // Для этого шаблона нам лучше бы иметь "сырые" ссылки, но мы извлечем их.
    
    preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>/', $content['body'], $matches, PREG_SET_ORDER);
    $realLinks = $matches; // Массив массивов [full_tag, url, text]

    // Генерируем "шум" (фейковые строки логов)
    $statuses = ['200 OK', '301 Moved', '500 Error', '403 Forbidden', 'TIMEOUT'];
    $methods = ['GET', 'POST', 'CONNECT', 'HEAD'];
    $agents = ['Mozilla/5.0', 'Chrome/120.0', 'Safari/537.36', 'Go-http-client', 'Python/3.9'];
    
    $rows = [];
    $totalBytes = 0;

    // Перемешиваем реальные ссылки с фейковыми данными
    foreach ($realLinks as $linkData) {
        $bytesSent = rand(100, 5000);
        $bytesRecv = rand(500, 150000);
        $totalBytes += $bytesSent + $bytesRecv;
        
        $rows[] = [
            'time' => date('H:i:s', time() - rand(0, 3600)),
            'source' => '10.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254),
            'method' => $methods[array_rand($methods)],
            'target_html' => "<a href=\"{$linkData[1]}\" class=\"monitor-link\">" . (strlen($linkData[2]) > 0 ? $linkData[2] : 'node-'.substr(md5($linkData[1]), 0, 8)) . "</a>",
            'status' => '<span class="status-ok">ACTIVE</span>',
            'size' => number_format($bytesRecv) . ' B',
            'latency' => rand(10, 300) . 'ms'
        ];
    }

    // Добавляем немного "шума" (Apache style)
    for ($i = 0; $i < rand(5, 10); $i++) {
        $rows[] = [
            'time' => date('H:i:s', time() - rand(0, 86400)),
            'source' => '192.168.' . rand(0, 10) . '.' . rand(1, 254),
            'method' => 'SYSTEM',
            'target_html' => 'internal-check-worker-' . rand(1000, 9999),
            'status' => '<span class="status-system">DAEMON</span>',
            'size' => '0 B',
            'latency' => '0ms'
        ];
    }

    // Сортируем по времени (обратно)
    usort($rows, function($a, $b) { return strcmp($b['time'], $a['time']); });

    ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetMon: <?= htmlspecialchars($content['title']) ?></title>
    <meta name="description" content="Network traffic analysis for <?= htmlspecialchars($content['title']) ?>. Real-time proxy logs and throughput statistics.">
    <style>
        :root { --bg: #1e1e1e; --panel: #252526; --text: #d4d4d4; --accent: #007acc; --border: #333; --green: #4ec9b0; }
        body { background: var(--bg); color: var(--text); font-family: 'Consolas', 'Monaco', 'Courier New', monospace; margin: 0; padding: 0; font-size: 13px; }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        
        .layout { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        
        /* SIDEBAR */
        .sidebar { background: var(--panel); border-right: 1px solid var(--border); padding: 20px; }
        .brand { font-size: 18px; font-weight: bold; color: #fff; margin-bottom: 20px; display: block; border-bottom: 1px solid var(--border); padding-bottom: 10px; }
        .menu-item { padding: 8px 0; color: #aaa; cursor: pointer; display: block;}
        .menu-item:hover, .menu-item.active { color: #fff; }
        .stat-box { margin-top: 30px; background: #333; padding: 10px; border-radius: 4px; }
        .stat-val { font-size: 20px; color: var(--green); }
        
        /* MAIN */
        .main { padding: 20px; }
        .header { display: flex; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 20px; }
        .h-title { font-size: 24px; color: #fff; margin: 0; }
        
        /* DATA TABLE */
        .log-table { width: 100%; border-collapse: collapse; background: var(--panel); }
        .log-table th { text-align: left; padding: 10px; border-bottom: 1px solid var(--border); color: #888; font-weight: normal; }
        .log-table td { padding: 8px 10px; border-bottom: 1px solid #333; }
        .log-table tr:hover { background: #2a2d2e; }
        
        .status-ok { color: #4ec9b0; background: rgba(78, 201, 176, 0.1); padding: 2px 6px; border-radius: 3px; font-size: 11px; }
        .status-system { color: #ce9178; font-size: 11px; }
        .monitor-link { font-weight: bold; }
        
        /* PAGINATION */
        .pagination { margin-top: 20px; display: flex; gap: 5px; }
        .page-link { padding: 5px 10px; background: var(--panel); border: 1px solid var(--border); }
        .page-link.active { background: var(--accent); color: white; border-color: var(--accent); }
        
        /* FOOTER */
        .footer-info { margin-top: 40px; color: #666; font-size: 11px; border-top: 1px solid var(--border); padding-top: 10px; }
    </style>
</head>
<body>

<div class="layout">
    <div class="sidebar">
        <span class="brand">⚡ <?= htmlspecialchars($content['site_name']) ?></span>
        
        <div style="margin-bottom: 20px; font-size:11px; color:#666;">
            System: LINUX-HADOOP-NODE-01<br>
            Uptime: <?= rand(10, 400) ?> days
        </div>

        <strong>Dashboards</strong>
        <a href="<?= $rootUrl ?>/" class="menu-item active">Traffic Monitor</a>
        <a href="<?= $rootUrl ?>/sitemap" class="menu-item">Node Map</a>
        <div class="menu-item">Security Logs</div>
        <div class="menu-item">Config (workers2.properties)</div>

        <div class="stat-box">
            <div style="font-size:11px; color:#aaa;">Total Throughput</div>
            <div class="stat-val"><?= number_format($totalBytes / 1024 / 1024, 2) ?> MB</div>
        </div>
        
        <div class="stat-box">
            <div style="font-size:11px; color:#aaa;">Active Connections</div>
            <div class="stat-val"><?= count($rows) ?></div>
        </div>
        
        <div style="margin-top:30px;">
            <strong>Recent Batches</strong>
            <?php foreach(array_slice($recentPages, 0, 5) as $p): ?>
                <a href="<?= $rootUrl ?><?= $p['url'] ?>" class="menu-item" style="font-size:11px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    > <?= htmlspecialchars($p['title']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="main">
        <div class="header">
            <div>
                <h1 class="h-title">Network Log Analysis: <?= htmlspecialchars($content['title']) ?></h1>
                <div style="color:#888; margin-top:5px;"><?= htmlspecialchars($content['description']) ?></div>
            </div>
            <div style="text-align:right; font-size:11px; color:#888;">
                Log file: proxylogs.txt<br>
                Date: <?= date('Y-m-d') ?>
            </div>
        </div>

        <!-- Terminal Output Decoy -->
        <div style="background:black; padding:10px; margin-bottom:20px; font-family:monospace; color:#ccc; font-size:11px; border:1px solid #444;">
            <span style="color:#4ec9b0">[INFO]</span> org.apache.hadoop.mapred.YarnChild: Executing with tokens<br>
            <span style="color:#4ec9b0">[INFO]</span> org.apache.hadoop.mapred.ReduceTask: Using ShuffleConsumerPlugin<br>
            <span style="color:#ce9178">[WARN]</span> Connection lifetime &lt;1 sec detected on port 5070<br>
            <span style="color:#4ec9b0">[INFO]</span> Loaded <?= count($rows) ?> active nodes from cluster config.
        </div>

        <table class="log-table">
            <thead>
                <tr>
                    <th width="80">Time</th>
                    <th width="120">Source IP</th>
                    <th width="80">Method</th>
                    <th>Destination / Resource</th>
                    <th width="80">Size</th>
                    <th width="80">Latency</th>
                    <th width="100">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td style="color:#569cd6"><?= $row['time'] ?></td>
                    <td><?= $row['source'] ?></td>
                    <td style="color:#c586c0"><?= $row['method'] ?></td>
                    <td><?= $row['target_html'] ?></td>
                    <td style="color:#b5cea8"><?= $row['size'] ?></td>
                    <td><?= $row['latency'] ?></td>
                    <td><?= $row['status'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($pagination): ?>
        <div class="pagination">
            <?php if ($pagination['prev']): ?>
                <a href="<?= $pagination['prev']['url'] ?>" class="page-link">&laquo; Prev</a>
            <?php endif; ?>
            
            <?php foreach ($pagination['pages'] as $p): ?>
                <a href="<?= $p['url'] ?>" class="page-link <?= $p['current'] ? 'active' : '' ?>">
                    <?= $p['num'] ?>
                </a>
            <?php endforeach; ?>
            
            <?php if ($pagination['next']): ?>
                <a href="<?= $pagination['next']['url'] ?>" class="page-link">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="footer-info">
            Generated by Apache Hadoop YarnChild | Log Analysis Engine v2.4 | Contact: <?= $content['email'] ?>
        </div>
    </div>
</div>

</body>
</html>
<?php
    return ob_get_clean();
}
?>