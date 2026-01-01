<?php
// generate-article.php

require_once 'user_manager.php';

function generatePageTitle($isHomePage, $pageId, $siteIdentity) {
    if ($isHomePage) {
        $seed = crc32($_SERVER['HTTP_HOST'] . 'title');
        srand($seed);
        
        $emojis = ['🔍', '📊', '🗄️', '📋', '🔗', '📈', '⚡', '🌐', '📝', '🔧', '💾', '📡'];
        $patterns = [
            '%emoji% %name% - %descriptor%',
            '%name% %emoji% | %descriptor%', 
            '%emoji% %descriptor% - %name%',
            '%name% %emoji% %descriptor%'
        ];
        
        $descriptors = [
            'Central Data Hub', 'Archive Network', 'Information Portal', 
            'Resource Directory', 'Digital Repository', 'Data Collection'
        ];
        
        $emoji = $emojis[rand(0, count($emojis) - 1)];
        $pattern = $patterns[rand(0, count($patterns) - 1)];
        $descriptor = $descriptors[rand(0, count($descriptors) - 1)];
        
        return str_replace(
            ['%emoji%', '%name%', '%descriptor%'],
            [$emoji, $siteIdentity['name'], $descriptor],
            $pattern
        );
    } else {
        $seed = crc32($pageId);
        srand($seed);
        
        $emojis = ['📄', '📊', '🔍', '📋', '💾', '⚡', '🔧', '📝', '🗂️', '📈'];
        $prefixes = ['Archive', 'Data', 'System', 'Node', 'Entry', 'Record', 'File', 'Log'];
        $suffixes = ['Analysis', 'Report', 'Summary', 'Overview', 'Details', 'Metrics'];
        
        $emoji = $emojis[rand(0, count($emojis) - 1)];
        $prefix = $prefixes[rand(0, count($prefixes) - 1)];
        $suffix = $suffixes[rand(0, count($suffixes) - 1)];
        $hash = strtoupper(substr($pageId, 0, 6));
        
        return "$emoji $prefix $suffix #$hash";
    }
}

function generatePageDescription($isHomePage, $pageId, $siteIdentity) {
    if ($isHomePage) {
        $seed = crc32($_SERVER['HTTP_HOST'] . 'desc');
        srand($seed);
        
        $templates = [
            'Access comprehensive %type% with real-time updates. %features% for researchers and professionals.',
            'Central repository for %type%. Featuring %features% and advanced search capabilities.',
            'Professional %type% platform. %features% with detailed documentation and analysis.',
            'Explore our extensive %type% database. %features% updated continuously by experts.'
        ];
        
        $types = ['data archives', 'research materials', 'technical documentation', 'information resources'];
        $features = ['Expert-curated content', 'Advanced analytics', 'Collaborative editing', 'Peer-reviewed data'];
        
        $template = $templates[rand(0, count($templates) - 1)];
        $type = $types[rand(0, count($types) - 1)];
        $feature = $features[rand(0, count($features) - 1)];
        
        return str_replace(['%type%', '%features%'], [$type, $feature], $template);
    } else {
        $seed = crc32($pageId . 'desc');
        srand($seed);
        
        $templates = [
            'Detailed archive entry containing %content%. Last updated %timeframe% by verified contributors.',
            'Technical documentation for %content%. Includes %details% and expert analysis.',
            'Comprehensive data record covering %content%. Features %details% and related resources.',
            'Professional archive entry documenting %content%. Contains %details% with references.'
        ];
        
        $contents = ['system specifications', 'network configurations', 'data structures', 'process documentation'];
        $details = ['performance metrics', 'usage statistics', 'technical diagrams', 'implementation guides'];
        $timeframes = ['recently', 'this week', 'within days'];
        
        $template = $templates[rand(0, count($templates) - 1)];
        $content = $contents[rand(0, count($contents) - 1)];
        $detail = $details[rand(0, count($details) - 1)];
        $timeframe = $timeframes[rand(0, count($timeframes) - 1)];
        
        return str_replace(['%content%', '%details%', '%timeframe%'], [$content, $detail, $timeframe], $template);
    }
}

function getScriptRoot() {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $scriptDir = str_replace('\\', '/', $scriptDir);
    return rtrim($scriptDir, '/');
}

function generateLinkListHtml($links, $trackerPrefix) {
    if (empty($links)) return '<p>No data available.</p>';

    $listStyles = ['table', 'list', 'grid'];
    $style = $listStyles[crc32($_SERVER['HTTP_HOST']) % count($listStyles)];
    
    $userManager = new UserManager();
    $root = getScriptRoot(); 
    
    $html = '<div class="data-container style-'.$style.'">';
    $html .= '<h3>Resource Index</h3>';
    
    if ($style == 'table') $html .= '<table class="data-table"><thead><tr><th>Resource</th><th>Date</th><th>Editor</th></tr></thead><tbody>';
    elseif ($style == 'list') $html .= '<ul class="resource-list">';
    else $html .= '<div class="grid-layout">';
    
    foreach ($links as $linkData) {
        $hasRecentEdit = (rand(1, 4) == 1);
        $editInfo = '';
        $editorCell = '';
        
        if ($hasRecentEdit) {
            $editor = $userManager->getRandomUser();
            $editDays = rand(1, 7);
            $editDate = date('Y-m-d', strtotime("-$editDays days"));
            $profileLink = $root . '/profile/' . $editor['id'];
            $editInfo = " <small class='edit-info'>edited $editDate by <a href='$profileLink'>{$editor['fullName']}</a></small>";
            $editorCell = "<a href='$profileLink'>{$editor['fullName']}</a>";
        }
        
        if ($linkData['is_trust']) {
            $url = $linkData['url'];
            $anchor = $linkData['anchor'];
            $icon = '📚'; 
            $rel = 'target="_blank" rel="nofollow noopener"';
            $class = 'trust-link';
        } else {
            $url = "$root/$trackerPrefix/" . base64_encode($linkData['url']);
            $anchor = "Node: " . parse_url($linkData['url'], PHP_URL_HOST) . " / " . substr(md5($linkData['url']), 0, 4);
            $icon = '📄';
            $rel = 'target="_blank" rel="nofollow"';
            $class = 'client-link';
        }
        
        $date = date('Y-m-d');
        
        if ($style == 'table') {
            $html .= "<tr class='$class'><td><span class='icon'>$icon</span> <a href='$url' $rel>$anchor</a>$editInfo</td><td>$date</td><td>$editorCell</td></tr>";
        } elseif ($style == 'list') {
            $html .= "<li class='$class'><span class='icon'>$icon</span> <a href='$url' $rel>$anchor</a> <small>($date)</small>$editInfo</li>";
        } else {
            $html .= "<div class='grid-item $class'><span class='icon'>$icon</span> <a href='$url' $rel>$anchor</a>$editInfo</div>";
        }
    }
    
    if ($style == 'table') $html .= '</tbody></table>';
    elseif ($style == 'list') $html .= '</ul>';
    else $html .= '</div>';
    
    $html .= '</div>';
    return $html;
}

function generatePaginationHtml($paginationData) {
    if (empty($paginationData)) return '';
    
    $html = '<nav class="pagination-nav" aria-label="Page navigation">';
    $html .= '<ul class="pagination">';
    
    if (isset($paginationData['prev'])) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $paginationData['prev']['url'] . '">' . $paginationData['prev']['text'] . '</a></li>';
    }
    
    if (isset($paginationData['pages'])) {
        foreach ($paginationData['pages'] as $page) {
            $activeClass = $page['current'] ? ' active' : '';
            $html .= '<li class="page-item' . $activeClass . '"><a class="page-link" href="' . $page['url'] . '">' . $page['text'] . '</a></li>';
        }
    }
    
    if (isset($paginationData['next'])) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $paginationData['next']['url'] . '">' . $paginationData['next']['text'] . '</a></li>';
    }
    
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}

function generateUpdatesWidget($updates, $siteName) {
    $seed = crc32($_SERVER['HTTP_HOST'] . 'updates');
    srand($seed);
    
    $blockTitles = [
        'Recent Updates', 'Latest Changes', 'System Updates', 'Recent Modifications', 
        'Latest Revisions', 'Current Updates', 'New Entries', 'Fresh Content'
    ];
    
    $title = $blockTitles[rand(0, count($blockTitles) - 1)];
    
    $hue = rand(0, 360);
    $bgColor = "hsl($hue, 15%, 97%)";
    $borderColor = "hsl($hue, 30%, 85%)";
    
    $html = '<div class="widget updates-widget" style="background: ' . $bgColor . '; border-left: 4px solid ' . $borderColor . ';">';
    $html .= '<h3>' . $title . '</h3>';
    $html .= '<ul class="updates-list">';
    
    foreach ($updates as $update) {
        $html .= '<li>';
        $html .= '<a href="' . $update['url'] . '">' . $update['title'] . '</a>';
        $html .= '<small>' . $update['date'] . '</small>';
        $html .= '</li>';
    }
    
    $html .= '</ul>';
    $html .= '</div>';
    
    return $html;
}

function generateRecentEditsWidget($edits) {
    $seed = crc32($_SERVER['HTTP_HOST'] . 'edits');
    srand($seed);
    $root = getScriptRoot();
    
    $blockTitles = [
        'Recent Changes', 'Latest Edits', 'User Contributions', 'Recent Revisions', 
        'Editorial Activity', 'Content Updates', 'Contributor Activity', 'Recent Modifications'
    ];
    
    $title = $blockTitles[rand(0, count($blockTitles) - 1)];
    
    $html = '<div class="widget recent-edits-widget">';
    $html .= '<h3>' . $title . '</h3>';
    $html .= '<div class="edits-list">';
    
    foreach ($edits as $edit) {
        $profileLink = $root . '/profile/' . $edit['user']['id'];
        
        $html .= '<div class="edit-item">';
        $html .= '<div class="edit-user">';
        $html .= '<div class="user-info">';
        $html .= '<a href="' . $profileLink . '" class="user-name">' . $edit['user']['fullName'] . '</a>';
        $html .= '<div class="edit-action">' . $edit['action'] . '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="edit-date">' . $edit['date'] . '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

function generatePopularPagesWidget($popularPages) {
    $seed = crc32($_SERVER['HTTP_HOST'] . 'popular');
    srand($seed);
    
    $blockTitles = [
        'Most Viewed', 'Popular Content', 'Trending Pages', 'Top Resources', 
        'Frequently Accessed', 'Popular Entries', 'Most Popular', 'Top Accessed'
    ];
    
    $title = $blockTitles[rand(0, count($blockTitles) - 1)];
    
    $hue1 = rand(0, 360);
    $hue2 = ($hue1 + rand(30, 60)) % 360;
    $gradientBg = "linear-gradient(135deg, hsl($hue1, 20%, 98%) 0%, hsl($hue2, 25%, 96%) 100%)";
    
    $html = '<div class="widget popular-widget" style="background: ' . $gradientBg . ';">';
    $html .= '<h3>' . $title . '</h3>';
    $html .= '<ol class="popular-list">';
    
    foreach ($popularPages as $i => $page) {
        $rank = $i + 1;
        $html .= '<li class="popular-item">';
        $html .= '<span class="rank">#' . $rank . '</span>';
        $html .= '<div class="page-info">';
        $html .= '<a href="' . $page['url'] . '" class="page-title">' . $page['title'] . '</a>';
        $html .= '<span class="view-count">👁️ ' . $page['views'] . ' views</span>';
        $html .= '</div>';
        $html .= '</li>';
    }
    
    $html .= '</ol>';
    $html .= '</div>';
    
    return $html;
}

function generateUserProfileSchema($user) {
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "ProfilePage",
        "mainEntity" => [
            "@type" => "Person",
            "name" => $user['fullName'],
            "givenName" => $user['firstName'],
            "familyName" => $user['lastName'],
            "email" => $user['email'],
            "jobTitle" => $user['jobTitle'],
            "worksFor" => [
                "@type" => "Organization",
                "name" => $user['organization']
            ],
            "address" => [
                "@type" => "PostalAddress",
                "addressCountry" => $user['country']
            ],
            "sameAs" => [
                $user['linkedIn'],
                $user['twitter']
            ],
            "knowsAbout" => $user['expertise']
        ]
    ];
    
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
}

// ИЗМЕНЕНИЕ: Используем CollectionPage вместо Article
function generateCollectionSchema($title, $description, $siteName, $userManager) {
    $editors = [];
    $editorCount = rand(1, 2);
    for ($i = 0; $i < $editorCount; $i++) {
        $user = $userManager->getRandomUser();
        $editors[] = [
            "@type" => "Person",
            "name" => $user['fullName'],
            "url" => "https://" . $_SERVER['HTTP_HOST'] . getScriptRoot() . "/profile/" . $user['id']
        ];
    }

    $schema = [
        "@context" => "https://schema.org",
        "@type" => "CollectionPage",
        "headline" => $title,
        "description" => $description,
        "url" => "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        "mainEntity" => [
            "@type" => "ItemList",
            "itemListElement" => [
                // Мы не перечисляем тут все 50 ссылок, чтобы не раздувать HTML,
                // но указываем, что это список элементов.
                // В идеале, сюда можно добавить топ-5 элементов.
            ]
        ],
        "author" => $editors[0],
        "publisher" => [
            "@type" => "Organization",
            "name" => $siteName,
            "logo" => [
                "@type" => "ImageObject",
                "url" => "https://" . $_SERVER['HTTP_HOST'] . "/logo.png"
            ]
        ]
    ];
    
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
}

function generateFullPageHtml($mainArticle, $sidebarLinks, $paginationData = null, $updates = [], $popularPages = [], $recentEdits = []) {
    // ВСТАВЬТЕ GA ID
    $ga_id = 'G-XXXXXXXXXX'; 

    $seed = crc32($_SERVER['HTTP_HOST']);
    srand($seed);
    
    $fonts = [['Roboto', 'sans-serif'], ['Open Sans', 'sans-serif'], ['Lato', 'sans-serif'], ['Merriweather', 'serif'], ['Arial', 'sans-serif'], ['Georgia', 'serif']];
    $font = $fonts[rand(0, count($fonts)-1)];
    
    $hue = rand(0, 360);
    $sat = rand(5, 20); 
    $light = rand(92, 98); 
    $bgColor = "hsl($hue, $sat%, $light%)";
    $textColor = "#333";
    $accentColor = "hsl($hue, 70%, 45%)";
    $linkColor = "hsl($hue, 80%, 35%)";
    
    $layouts = ['left-sidebar', 'right-sidebar', 'no-sidebar-bottom'];
    $layout = $layouts[rand(0, count($layouts)-1)];
    
    $containerWidth = rand(900, 1400) . 'px';
    $borderRadius = rand(0, 12) . 'px';
    
    $siteName = $mainArticle['site_name'] ?? 'Data Archive';
    $siteEmail = $mainArticle['email'] ?? 'info@' . $_SERVER['HTTP_HOST'];
    $root = getScriptRoot();
    
    $userManager = new UserManager();
    
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <title><?= htmlspecialchars($mainArticle['title']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($mainArticle['description']) ?>">
    
    <?php if(!in_array($font[0], ['Arial', 'Georgia'])): ?>
    <link href="https://fonts.googleapis.com/css2?family=<?= str_replace(' ', '+', $font[0]) ?>:wght@400;700&display=swap" rel="stylesheet">
    <?php endif; ?>

    <!-- ИЗМЕНЕНИЕ: Используем новую схему CollectionPage -->
    <?= generateCollectionSchema($mainArticle['title'], $mainArticle['description'], $siteName, $userManager) ?>

    <style>
        :root {
            --bg: <?= $bgColor ?>;
            --text: <?= $textColor ?>;
            --accent: <?= $accentColor ?>;
            --link: <?= $linkColor ?>;
            --radius: <?= $borderRadius ?>;
            --width: <?= $containerWidth ?>;
            --font: '<?= $font[0] ?>', <?= $font[1] ?>;
        }
        
        body { font-family: var(--font); background: var(--bg); color: var(--text); margin: 0; line-height: 1.6; }
        a { color: var(--link); text-decoration: none; }
        a:hover { text-decoration: underline; }
        
        .page-wrap { max-width: var(--width); margin: 0 auto; padding: 20px; display: grid; gap: 30px; }
        
        .site-header { grid-column: 1 / -1; background: #fff; padding: 20px; border-radius: var(--radius); border-bottom: 3px solid var(--accent); display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 22px; font-weight: bold; color: var(--accent); }
        .logo a { color: var(--accent); text-decoration: none; }
        
        <?php if($layout == 'left-sidebar'): ?>
            .page-wrap { grid-template-columns: 300px 1fr; }
            .sidebar { grid-column: 1; }
            .content { grid-column: 2; }
        <?php elseif($layout == 'right-sidebar'): ?>
            .page-wrap { grid-template-columns: 1fr 300px; }
            .sidebar { grid-column: 2; }
            .content { grid-column: 1; }
        <?php else: ?>
            .page-wrap { grid-template-columns: 1fr; }
            .sidebar { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: 20px; }
            .sidebar .widget { flex: 1; min-width: 250px; }
        <?php endif; ?>
        
        .content { background: #fff; padding: 40px; border-radius: var(--radius); box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .widget { background: #fff; padding: 20px; border-radius: var(--radius); margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.03); }
        .widget ul { padding-left: 20px; }
        
        h1 { margin-top: 0; color: var(--accent); }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th, .data-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .data-table th { background: #f8f9fa; font-weight: 600; }
        
        .resource-list { list-style: none; padding: 0; }
        .resource-list li { padding: 10px 0; border-bottom: 1px dashed #eee; }
        
        .grid-layout { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-top: 20px; }
        .grid-item { border: 1px solid #eee; padding: 15px; border-radius: 4px; background: #fafafa; }
        
        /* Pagination Styles */
        .pagination-nav { margin: 30px 0; text-align: center; }
        .pagination { list-style: none; display: inline-flex; padding: 0; margin: 0; }
        .page-item { margin: 0 5px; }
        .page-link { display: block; padding: 10px 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; color: var(--link); }
        .page-item.active .page-link { background: var(--accent); color: white; border-color: var(--accent); }
        .page-link:hover { background: #e9ecef; text-decoration: none; }
        
        /* Updates Widget */
        .updates-list { list-style: none; padding: 0; }
        .updates-list li { padding: 8px 0; border-bottom: 1px dashed #eee; display: flex; justify-content: space-between; align-items: center; }
        .updates-list small { color: #666; font-size: 0.85em; }
        
        /* Recent Edits Widget */
        .edits-list { }
        .edit-item { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .edit-user { display: flex; align-items: center; flex: 1; }
        .user-info { }
        .user-name { font-weight: 600; display: block; }
        .edit-action { font-size: 0.9em; color: #666; }
        .edit-date { font-size: 0.85em; color: #888; }
        
        /* Popular Pages Widget */
        .popular-list { padding-left: 0; }
        .popular-item { display: flex; align-items: center; padding: 8px 0; border-bottom: 1px dashed #eee; }
        .rank { background: var(--accent); color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8em; font-weight: bold; margin-right: 10px; flex-shrink: 0; }
        .page-info { flex: 1; }
        .page-title { display: block; font-weight: 600; }
        .view-count { font-size: 0.85em; color: #666; }
        
        /* Edit info in links */
        .edit-info { color: #666; font-style: italic; }
        .edit-info a { color: var(--accent); }
        
        footer { grid-column: 1 / -1; text-align: center; padding: 40px; font-size: 0.9em; color: #666; border-top: 1px solid rgba(0,0,0,0.05); margin-top: 20px; }
        .contact-info { margin-top: 10px; font-weight: bold; color: var(--accent); }
        
        @media(max-width: 800px) {
            .page-wrap { grid-template-columns: 1fr !important; }
            .edit-item { flex-direction: column; align-items: flex-start; }
            .popular-item { flex-direction: column; align-items: flex-start; }
            .rank { margin-bottom: 5px; }
        }
    </style>
</head>
<body>

    <div class="page-wrap">
        <header class="site-header">
            <div class="logo"><a href="<?= $root ?>/"><?= $siteName ?></a></div>
            <nav><a href="<?= $root ?>/">Home</a> &bull; <a href="<?= $root ?>/sitemap">Sitemap</a></nav>
        </header>

        <main class="content">
            <h1><?= htmlspecialchars($mainArticle['title']) ?></h1>
            <p style="font-size: 1.1em; color: #555;"><?= htmlspecialchars($mainArticle['description']) ?></p>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
            
            <?= $mainArticle['body'] ?>
            
            <?php if ($paginationData): ?>
                <?= generatePaginationHtml($paginationData) ?>
            <?php endif; ?>
        </main>

        <aside class="sidebar">
            <div class="widget">
                <h3>Latest Logs</h3>
                <ul>
                    <?php foreach($sidebarLinks as $page): ?>
                        <li><a href="<?= htmlspecialchars($page['url']) ?>"><?= htmlspecialchars($page['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <?php if (!empty($updates)): ?>
                <?= generateUpdatesWidget($updates, $siteName) ?>
            <?php endif; ?>
            
            <?php if (!empty($recentEdits)): ?>
                <?= generateRecentEditsWidget($recentEdits) ?>
            <?php endif; ?>
            
            <?php if (!empty($popularPages)): ?>
                <?= generatePopularPagesWidget($popularPages) ?>
            <?php endif; ?>
            
            <div class="widget">
                <h3>System Status</h3>
                <p><strong>Online</strong>. Nodes: <?= rand(5000, 50000) ?></p>
            </div>
        </aside>

        <footer>
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. All rights reserved.</p>
            <p class="contact-info">Contact: <a href="mailto:<?= htmlspecialchars($siteEmail) ?>"><?= htmlspecialchars($siteEmail) ?></a></p>
            <p style="font-size: 0.8em; margin-top: 10px;">System ID: <?= substr(md5($_SERVER['HTTP_HOST']), 0, 6) ?></p>
        </footer>
    </div>

</body>
</html>
    <?php
    return ob_get_clean();
}
?>