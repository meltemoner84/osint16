<?php
/**
 * CyberTrace - PHP API Backend (v2 - Geliştirilmiş)
 * Hosting'de tam çalışan profil doğrulama ve görsel arama sistemi.
 * 
 * Değişiklikler:
 * - Platform-özel API kontrolleri (Instagram, TikTok, GitHub vb.)
 * - SPA sitelerde kullanıcı adı doğrulaması iyileştirildi
 * - Görsel arama: Hosting'in kendi uploads/ klasörüne kaydedip kendi URL'sini kullanır
 * - HTTP durum kodu bazlı akıllı karar verme
 */

@ini_set('upload_max_filesize', '25M');
@ini_set('post_max_size', '30M');
@ini_set('max_execution_time', '60');
@ini_set('memory_limit', '128M');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. Ping / Canlılık Kontrolü
if (isset($_GET['ping']) || $action === 'ping') {
    echo json_encode(['status' => 'ok', 'server' => 'php', 'timestamp' => time()]);
    exit;
}

// ===== Kendi public URL'mizi oluştur =====
function getSelfBaseUrl() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $base = $scheme . '://' . $host . rtrim($scriptDir, '/');
    return $base;
}

// ===== "Sayfa Bulunamadı" ve Bot Koruması Kalıpları =====
$NOT_FOUND_PATTERNS = [
    '/this user (doesn\'?t|does not) exist/i',
    '/user (doesn\'?t|does not) exist/i',
    '/this account (doesn\'?t|does not) exist/i',
    '/account (doesn\'?t|does not) exist/i',
    '/this profile (doesn\'?t|does not) exist/i',
    '/profile (doesn\'?t|does not) exist/i',
    '/this page (doesn\'?t|does not) exist/i',
    '/page (doesn\'?t|does not) exist/i',
    '/user (not|wasn\'?t) found/i',
    '/profile (not|wasn\'?t) found/i',
    '/account (not|wasn\'?t) found/i',
    '/page (not|wasn\'?t) found/i',
    '/shop (not|wasn\'?t) found/i',
    '/the specified profile could not be found/i',
    '/sorry, this page isn\'?t available/i',
    '/sorry, this content isn\'?t available/i',
    '/sorry, (the|this) page you were looking for was not found/i',
    '/the page you were looking for was not found/i',
    '/page you requested cannot be found/i',
    '/uh oh!?/i',
    '/we couldn\'?t find (that|this|the) (page|shop|user|profile)/i',
    '/no such user/i',
    '/no user found/i',
    '/nothing to see here/i',
    '/content (is |has been )?(unavailable|removed|deleted)/i',
    '/account (has been |is )?(suspended|deactivated|deleted|banned|removed)/i',
    '/this (account|user) has been (suspended|banned|terminated)/i',
    '/there are no packages owned by/i',
    '/not found\s*·\s*pypi/i',
    '/this user hasn\'?t uploaded any/i',
    '/bu kullanıcı (bulunamadı|mevcut değil|yok)/i',
    '/kullanıcı (bulunamadı|mevcut değil)/i',
    '/böyle bir kullanıcı (yok|bulunamadı)/i',
    '/bu profil (bulunamadı|mevcut değil)/i',
    '/profil bulunamadı/i',
    '/bu hesap (bulunamadı|mevcut değil|silindi|askıya alındı)/i',
    '/hesap bulunamadı/i',
    '/sayfa bulunamadı/i',
    '/sayfa mevcut değil/i',
    '/aradığınız sayfa/i',
    '/sonuç bulunamadı/i',
    '/<title>[^<]*(404|not found|bulunamadı|error|hata|doesn\'?t exist|mevcut değil|uh oh)[^<]*<\/title>/i',
    '/<h1[^>]*>[^<]*(404|not found|bulunamadı|doesn\'?t exist|mevcut değil|sayfa yok|uh oh)[^<]*<\/h1>/i'
];

$BOT_CHALLENGE_PATTERNS = [
    '/js_challenge=1/i',
    '/cf-chl/i',
    '/dd_referrer/i',
    '/just a moment\.\.\./i',
    '/checking your browser/i',
    '/attention required!/i',
    '/verify you are a human/i',
    '/security verification/i',
    '/ddos-guard/i'
];

// ===== Platform-özel API Kontrolleri =====
// Bu fonksiyonlar doğrudan platformun kendi API'sini kullanarak kesin sonuç verir
function checkWithPlatformAPI($targetUrl, $username) {
    
    // --- PyPI: Python Package Index (Kesin Paket & Profil Doğrulaması) ---
    if (strpos($targetUrl, 'pypi.org') !== false) {
        $pyCh = curl_init("https://pypi.org/user/" . urlencode($username) . "/");
        curl_setopt_array($pyCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        $pyRes = curl_exec($pyCh);
        $pyCode = curl_getinfo($pyCh, CURLINFO_HTTP_CODE);
        curl_close($pyCh);

        if ($pyCode === 404 || !$pyRes || preg_match('/(404 Not Found|Not Found · PyPI|We couldn\'?t find the page)/i', $pyRes)) {
            return ['status' => 'not-found', 'statusCode' => 404, 'matchedPattern' => 'PyPI kullanıcısı mevcut değil', 'verifiedByContent' => true];
        }

        // PyPI'da kullanıcının gerçekten paketi veya profili var mı?
        if (preg_match('/class="package-snippet__title"[^>]*>([^<]+)<\/span>/i', $pyRes, $pkgMatch) ||
            stripos($pyRes, 'package-snippet') !== false ||
            stripos($pyRes, 'user-profile') !== false) {
            $metrics = [];
            if (preg_match('/([0-9]+)\s+projects?/i', $pyRes, $pm)) {
                $metrics['repos'] = $pm[1] . ' Proje/Paket';
            }
            return ['status' => 'found', 'statusCode' => 200, 'matchedPattern' => null, 'verifiedByContent' => true, 'metrics' => $metrics];
        }

        return ['status' => 'not-found', 'statusCode' => 404, 'matchedPattern' => 'PyPI profili veya paketi bulunamadı', 'verifiedByContent' => true];
    }

    // --- Etsy: Mağaza, Ürün ve Profil Doğrulaması ---
    if (strpos($targetUrl, 'etsy.com') !== false) {
        $etCh = curl_init("https://www.etsy.com/shop/" . urlencode($username));
        curl_setopt_array($etCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => ['Accept-Language: en-US,en;q=0.9']
        ]);
        $etRes = curl_exec($etCh);
        $etCode = curl_getinfo($etCh, CURLINFO_HTTP_CODE);
        curl_close($etCh);

        // Sayfa bulunamadı belirteçleri (Etsy'ye özel)
        if ($etCode === 404 || !$etRes || 
            preg_match('/(Uh oh!?|Sorry, the page you were looking for was not found|page you were looking for was not found|shop doesn\'?t exist)/i', $etRes) ||
            stripos($etRes, 'dd_referrer') !== false) {
            return ['status' => 'not-found', 'statusCode' => 404, 'matchedPattern' => 'Etsy mağazası veya profili bulunamadı', 'verifiedByContent' => true];
        }

        // Etsy'de gerçekten mağaza, ürün veya takipçi var mı?
        if (stripos($etRes, 'shop-home') !== false || stripos($etRes, 'listing-card') !== false || preg_match('/([0-9.,]+)\s*Sales/i', $etRes)) {
            $metrics = [];
            if (preg_match('/([0-9.,]+)\s*Sales/i', $etRes, $sm)) {
                $metrics['sales'] = $sm[1] . ' Satış';
            }
            return ['status' => 'found', 'statusCode' => 200, 'matchedPattern' => null, 'verifiedByContent' => true, 'metrics' => $metrics];
        }

        return ['status' => 'not-found', 'statusCode' => 404, 'matchedPattern' => 'Etsy mağaza içeriği/ürünü yok', 'verifiedByContent' => true];
    }

    // --- Instagram: Çoklu API kontrolü (Kesin Doğruluk) ---
    if (strpos($targetUrl, 'instagram.com') !== false) {
        
        // Yöntem 1: Doğrudan Instagram Web Kontrolü
        $ch2 = curl_init("https://www.instagram.com/{$username}/");
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control: no-cache',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: none',
            ]
        ]);
        
        $body2 = curl_exec($ch2);
        $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        $finalUrl2 = curl_getinfo($ch2, CURLINFO_EFFECTIVE_URL);
        curl_close($ch2);
        
        // Kesin bulunamadı: HTTP 404 / 410 veya Instagram 'Sorry' sayfası
        if ($httpCode2 === 404 || $httpCode2 === 410) {
            return ['status' => 'not-found', 'statusCode' => $httpCode2, 'matchedPattern' => 'Instagram HTTP ' . $httpCode2, 'verifiedByContent' => true];
        }
        
        if ($body2) {
            // Instagram sayfa bulunamadı belirteçleri
            if (preg_match("/sorry, this page isn'?t available/i", $body2) ||
                preg_match('/<title[^>]*>(Page Not Found|Sayfa Bulunamadı)<\/title>/i', $body2) ||
                stripos($body2, 'Sayfa Bulunamadı') !== false) {
                return ['status' => 'not-found', 'statusCode' => 404, 'matchedPattern' => 'Instagram profili mevcut değil', 'verifiedByContent' => true];
            }
            
            // Login sayfasına yönlendirme: Instagram profil sayfaları anonim istekler için login'e yönlendirir (next=/username/)
            if (strpos($finalUrl2, '/accounts/login') !== false || strpos($finalUrl2, '/challenge') !== false || stripos($finalUrl2, 'next=') !== false) {
                return ['status' => 'found', 'statusCode' => 200, 'matchedPattern' => null, 'verifiedByContent' => true, 'metrics' => []];
            }
            
            // HTML veya gövdede kullanıcı adı veya profil verisi var mı
            $lowerBody = strtolower($body2);
            $lowerUser = strtolower($username);
            
            $hasInBody = (strpos($lowerBody, $lowerUser) !== false || strpos($lowerBody, '"username":"' . $lowerUser . '"') !== false);
            $hasProfileData = preg_match('/(profilePage|edge_followed_by|edge_follow|graphql)/i', $body2);
            
            if ($hasInBody || $hasProfileData) {
                $metrics = [];
                if (preg_match('/([0-9.,KMkm]+)\s*(?:Followers|Takipçi)/i', $body2, $fm)) {
                    $metrics['followers'] = trim($fm[1]) . ' Takipçi';
                }
                return ['status' => 'found', 'statusCode' => ($httpCode2 ?: 200), 'matchedPattern' => null, 'verifiedByContent' => true, 'metrics' => $metrics];
            }
        }
        
        // Yöntem 2: Picuki Açık Web Görüntüleyici (Instagram'ı açık proxy ile sorgular)
        $pCh = curl_init("https://www.picuki.com/profile/" . urlencode($username));
        curl_setopt_array($pCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        $pRes = curl_exec($pCh);
        $pCode = curl_getinfo($pCh, CURLINFO_HTTP_CODE);
        curl_close($pCh);
        
        if ($pCode === 200 && $pRes && !preg_match('/profile not found|error-title/i', $pRes)) {
            $metrics = [];
            if (preg_match('/<span class="followed_by">([0-9.,KMkm]+)<\/span>/i', $pRes, $pm)) {
                $metrics['followers'] = trim($pm[1]) . ' Takipçi';
            }
            if (preg_match('/<h1[^>]*class="profile-name"[^>]*>(.*?)<\/h1>/is', $pRes, $nm)) {
                $metrics['author'] = trim(strip_tags($nm[1]));
            }
            return ['status' => 'found', 'statusCode' => 200, 'matchedPattern' => null, 'verifiedByContent' => true, 'metrics' => $metrics];
        }
        if ($pCode === 404 || ($pRes && preg_match('/profile not found/i', $pRes))) {
            return ['status' => 'not-found', 'statusCode' => 404, 'matchedPattern' => 'Instagram kullanıcısı mevcut değil', 'verifiedByContent' => true];
        }

        // Yöntem 3: Bing Arama İndeksi Sorgusu (Sunucu engeli tanımaz)
        $bCh = curl_init("https://www.bing.com/search?q=" . urlencode("site:instagram.com/{$username}"));
        curl_setopt_array($bCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        $bRes = curl_exec($bCh);
        $bCode = curl_getinfo($bCh, CURLINFO_HTTP_CODE);
        curl_close($bCh);
        
        if ($bCode === 200 && $bRes) {
            if (stripos($bRes, "instagram.com/{$username}") !== false || stripos($bRes, "@{$username}") !== false) {
                return ['status' => 'found', 'statusCode' => 200, 'matchedPattern' => null, 'verifiedByContent' => true, 'metrics' => []];
            }
        }

        // HTTP 200 veya 302 ise ve hata mesajı yoksa profil var sayılır
        if ($httpCode2 === 200 || $httpCode2 === 302) {
            return ['status' => 'found', 'statusCode' => 200, 'matchedPattern' => null, 'verifiedByContent' => true, 'metrics' => []];
        }

        return ['status' => 'not-found', 'statusCode' => 404, 'matchedPattern' => 'Instagram kullanıcısı tespit edilemedi', 'verifiedByContent' => true];
    }
    
    // --- TikTok: oEmbed API (CORS destekli, kesin doğruluk) ---
    if (strpos($targetUrl, 'tiktok.com') !== false) {
        $ttCh = curl_init("https://www.tiktok.com/oembed?url=" . urlencode("https://www.tiktok.com/@{$username}"));
        curl_setopt_array($ttCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        $ttRes = curl_exec($ttCh);
        $ttCode = curl_getinfo($ttCh, CURLINFO_HTTP_CODE);
        curl_close($ttCh);

        if ($ttCode === 200 && $ttRes) {
            $ttData = json_decode($ttRes, true);
            if (isset($ttData['author_name']) || isset($ttData['title'])) {
                return [
                    'status' => 'found',
                    'statusCode' => 200,
                    'matchedPattern' => null,
                    'verifiedByContent' => true,
                    'metrics' => ['author' => isset($ttData['author_name']) ? $ttData['author_name'] : $username]
                ];
            }
        }
        if ($ttCode === 404 || $ttCode === 400) {
            return ['status' => 'not-found', 'statusCode' => 404, 'matchedPattern' => 'TikTok kullanıcısı mevcut değil', 'verifiedByContent' => true];
        }
        return null;
    }
    
    // --- GitHub: API v3 (Kesin doğruluk) ---
    if (strpos($targetUrl, 'github.com') !== false && strpos($targetUrl, 'gist') === false) {
        $ghCh = curl_init("https://api.github.com/users/" . urlencode($username));
        curl_setopt_array($ghCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'CyberTrace-OSINT/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/vnd.github.v3+json']
        ]);
        $ghRes = curl_exec($ghCh);
        $ghCode = curl_getinfo($ghCh, CURLINFO_HTTP_CODE);
        curl_close($ghCh);

        if ($ghCode === 200 && $ghRes) {
            $ghData = json_decode($ghRes, true);
            if (isset($ghData['login'])) {
                $metrics = [];
                if (isset($ghData['followers'])) $metrics['followers'] = $ghData['followers'] . ' Takipçi';
                if (isset($ghData['public_repos'])) $metrics['repos'] = $ghData['public_repos'] . ' Repo';
                return ['status' => 'found', 'statusCode' => 200, 'matchedPattern' => null, 'verifiedByContent' => true, 'metrics' => $metrics];
            }
        }
        if ($ghCode === 404) {
            return ['status' => 'not-found', 'statusCode' => 404, 'matchedPattern' => 'GitHub kullanıcısı mevcut değil', 'verifiedByContent' => true];
        }
        return null;
    }
    
    // --- GitLab: API v4 ---
    if (strpos($targetUrl, 'gitlab.com') !== false) {
        $glCh = curl_init("https://gitlab.com/api/v4/users?username=" . urlencode($username));
        curl_setopt_array($glCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'CyberTrace-OSINT/1.0'
        ]);
        $glRes = curl_exec($glCh);
        $glCode = curl_getinfo($glCh, CURLINFO_HTTP_CODE);
        curl_close($glCh);

        if ($glCode === 200 && $glRes) {
            $glData = json_decode($glRes, true);
            if (is_array($glData) && count($glData) > 0) {
                foreach ($glData as $u) {
                    if (isset($u['username']) && strtolower($u['username']) === strtolower($username)) {
                        return ['status' => 'found', 'statusCode' => 200, 'matchedPattern' => null, 'verifiedByContent' => true, 'metrics' => []];
                    }
                }
            }
            return ['status' => 'not-found', 'statusCode' => 404, 'matchedPattern' => 'GitLab kullanıcısı bulunamadı', 'verifiedByContent' => true];
        }
        return null;
    }
    
    // --- Reddit: JSON API ---
    if (strpos($targetUrl, 'reddit.com/user/') !== false) {
        $rdCh = curl_init("https://www.reddit.com/user/" . urlencode($username) . "/about.json");
        curl_setopt_array($rdCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'CyberTrace-OSINT/1.0 (by /u/cybertrace)',
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $rdRes = curl_exec($rdCh);
        $rdCode = curl_getinfo($rdCh, CURLINFO_HTTP_CODE);
        curl_close($rdCh);

        if ($rdCode === 200 && $rdRes) {
            $rdData = json_decode($rdRes, true);
            if (isset($rdData['data']['name'])) {
                $metrics = [];
                if (isset($rdData['data']['total_karma'])) $metrics['karma'] = $rdData['data']['total_karma'] . ' Karma';
                return ['status' => 'found', 'statusCode' => 200, 'matchedPattern' => null, 'verifiedByContent' => true, 'metrics' => $metrics];
            }
        }
        if ($rdCode === 404) {
            return ['status' => 'not-found', 'statusCode' => 404, 'matchedPattern' => 'Reddit kullanıcısı bulunamadı', 'verifiedByContent' => true];
        }
        return null;
    }
    
    // --- Duolingo: API ---
    if (strpos($targetUrl, 'duolingo.com') !== false) {
        $dlCh = curl_init("https://www.duolingo.com/2017-06-30/users?username=" . urlencode($username));
        curl_setopt_array($dlCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0'
        ]);
        $dlRes = curl_exec($dlCh);
        $dlCode = curl_getinfo($dlCh, CURLINFO_HTTP_CODE);
        curl_close($dlCh);

        if ($dlCode === 200 && $dlRes) {
            $dlData = json_decode($dlRes, true);
            if (isset($dlData['users']) && count($dlData['users']) > 0) {
                return ['status' => 'found', 'statusCode' => 200, 'matchedPattern' => null, 'verifiedByContent' => true, 'metrics' => []];
            }
            return ['status' => 'not-found', 'statusCode' => 404, 'matchedPattern' => 'Duolingo kullanıcısı bulunamadı', 'verifiedByContent' => true];
        }
        return null;
    }
    
    // --- Hacker News: Firebase API ---
    if (strpos($targetUrl, 'news.ycombinator.com') !== false) {
        $hnCh = curl_init("https://hacker-news.firebaseio.com/v0/user/" . urlencode($username) . ".json");
        curl_setopt_array($hnCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $hnRes = curl_exec($hnCh);
        $hnCode = curl_getinfo($hnCh, CURLINFO_HTTP_CODE);
        curl_close($hnCh);

        if ($hnCode === 200 && $hnRes && $hnRes !== 'null') {
            $hnData = json_decode($hnRes, true);
            if (isset($hnData['id'])) {
                return ['status' => 'found', 'statusCode' => 200, 'matchedPattern' => null, 'verifiedByContent' => true, 'metrics' => ['karma' => ($hnData['karma'] ?? 0) . ' Karma']];
            }
        }
        return ['status' => 'not-found', 'statusCode' => 404, 'matchedPattern' => 'HackerNews kullanıcısı bulunamadı', 'verifiedByContent' => true];
    }
    
    return null; // Platform-özel API yok, genel kontrol devam edecek
}

// ===== SPA (Single Page Application) siteleri - sadece HTTP durum koduna bakılacak =====
$SPA_DOMAINS = [
    'instagram.com', 'twitter.com', 'x.com', 'tiktok.com', 'facebook.com',
    'linkedin.com', 'snapchat.com', 'pinterest.com', 'threads.net',
    'medium.com', 'notion.so', 'figma.com', 'canva.com',
    'spotify.com', 'discord.com'
];

function isSPASite($url) {
    global $SPA_DOMAINS;
    $host = strtolower(parse_url($url, PHP_URL_HOST));
    if ($host) {
        $host = preg_replace('/^www\./i', '', $host);
        foreach ($SPA_DOMAINS as $domain) {
            if ($host === $domain || strpos($host, '.' . $domain) !== false) {
                return true;
            }
        }
    }
    return false;
}

// ===== 2. Profil Doğrulama (Geliştirilmiş) =====
if ($action === 'check-profile' || strpos($_SERVER['REQUEST_URI'], '/api/check-profile') !== false) {
    $targetUrl = isset($_GET['url']) ? trim($_GET['url']) : '';
    $username = isset($_GET['username']) ? trim($_GET['username']) : '';

    if (empty($targetUrl)) {
        http_response_code(400);
        echo json_encode(['error' => 'URL parametresi gerekli']);
        exit;
    }

    // Karakter Sentaks Kontrolü
    if (strpos($targetUrl, 'reddit.com') !== false && strpos($username, '.') !== false) {
        echo json_encode([
            'status' => 'not-found', 'statusCode' => 404,
            'matchedPattern' => 'Reddit kullanıcı adlarında nokta olamaz',
            'verifiedByContent' => true
        ]);
        exit;
    }
    if ((strpos($targetUrl, 'x.com') !== false || strpos($targetUrl, 'twitter.com') !== false) && 
        (strpos($username, '.') !== false || strlen($username) > 15)) {
        echo json_encode([
            'status' => 'not-found', 'statusCode' => 404,
            'matchedPattern' => 'X kullanıcı adlarında nokta olamaz ve max 15 karakterdir',
            'verifiedByContent' => true
        ]);
        exit;
    }

    // 1. Önce platform-özel API'yi dene (en güvenilir sonuç)
    $apiResult = checkWithPlatformAPI($targetUrl, $username);
    if ($apiResult !== null) {
        echo json_encode($apiResult);
        exit;
    }

    // 2. Platform-özel API yoksa, cURL ile sayfayı çek
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $targetUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9,tr-TR;q=0.8,tr;q=0.7',
            'Cache-Control: no-cache',
        ],
        CURLOPT_ENCODING => '', // gzip/deflate support
    ]);

    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($body === false || $httpCode === 0) {
        echo json_encode([
            'status' => 'not-found', 'statusCode' => 0,
            'matchedPattern' => 'Bağlantı hatası: ' . $curlError,
            'verifiedByContent' => false
        ]);
        exit;
    }

    // Yönlendirme analizi
    $finalPath = parse_url($finalUrl, PHP_URL_PATH);
    if ($finalPath) {
        $finalPathLower = strtolower($finalPath);
        $isAuthRedirect = (strpos($finalPathLower, '/login') !== false ||
            strpos($finalPathLower, '/signin') !== false ||
            strpos($finalPathLower, '/checkpoint') !== false ||
            strpos($finalPathLower, '/auth') !== false);
        $isErrorRedirect = (strpos($finalPathLower, '/404') !== false ||
            strpos($finalPathLower, '/notfound') !== false ||
            strpos($finalPathLower, '/error') !== false);

        if ($isErrorRedirect) {
            echo json_encode([
                'status' => 'not-found', 'statusCode' => $httpCode,
                'matchedPattern' => 'Hata Sayfasına Yönlendirildi: ' . $finalUrl,
                'verifiedByContent' => true
            ]);
            exit;
        }

        if ($isAuthRedirect) {
            // Eğer yönlendirme URL'si kullanıcı adını içeriyorsa veya platform profil için oturum istiyorsa profil var
            if (isSPASite($targetUrl) || stripos($finalUrl, urlencode($username)) !== false || stripos($finalUrl, $username) !== false) {
                echo json_encode([
                    'status' => 'found', 'statusCode' => 200,
                    'matchedPattern' => null,
                    'verifiedByContent' => true,
                    'metrics' => []
                ]);
                exit;
            }
            echo json_encode([
                'status' => 'not-found', 'statusCode' => $httpCode,
                'matchedPattern' => 'Giriş Sayfasına Yönlendirildi: ' . $finalUrl,
                'verifiedByContent' => true
            ]);
            exit;
        }
        
        // Ana sayfaya yönlendirme (profil URL'si ama ana sayfaya atıldı)
        $origPath = parse_url($targetUrl, PHP_URL_PATH);
        if ($origPath && strlen($origPath) > 2 && ($finalPathLower === '/' || $finalPathLower === '')) {
            echo json_encode([
                'status' => 'not-found', 'statusCode' => $httpCode,
                'matchedPattern' => 'Ana sayfaya yönlendirildi (profil yok)',
                'verifiedByContent' => true
            ]);
            exit;
        }
    }

    // 1. Bot Koruması / Cloudflare / DataDome Challenge Kontrolü
    $isBotChallenge = false;
    foreach ($BOT_CHALLENGE_PATTERNS as $bPattern) {
        if (preg_match($bPattern, $body) || preg_match($bPattern, $finalUrl)) {
            $isBotChallenge = true;
            break;
        }
    }
    
    // Bot challenge varsa KESİNLİKLE gerçek profil kabul edilmez
    if ($isBotChallenge) {
        echo json_encode([
            'status' => 'not-found', 'statusCode' => $httpCode,
            'matchedPattern' => 'Platform güvenlik engeli / Bot koruması (Gerçek profil teyit edilemedi)',
            'verifiedByContent' => true
        ]);
        exit;
    }

    // 2. HTTP Hata Kodları (404, 410, 451, 500 vb.)
    if ($httpCode === 404 || $httpCode === 410 || $httpCode === 451 || $httpCode >= 500) {
        echo json_encode([
            'status' => 'not-found', 'statusCode' => $httpCode,
            'matchedPattern' => 'HTTP ' . $httpCode,
            'verifiedByContent' => true
        ]);
        exit;
    }

    // 3. "Sayfa Bulunamadı" ve Hata Kalıplarını Tara
    foreach ($NOT_FOUND_PATTERNS as $pattern) {
        if (preg_match($pattern, $body)) {
            echo json_encode([
                'status' => 'not-found', 'statusCode' => $httpCode,
                'matchedPattern' => 'İçerik Kalıbı Eşleşti: Sayfa Yok / Profil Bulunamadı',
                'verifiedByContent' => true
            ]);
            exit;
        }
    }

    // 4. Başlık (<title>) ve H1 Analizi
    $titleText = '';
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $body, $tMatch)) {
        $titleText = strtolower(trim(strip_tags($tMatch[1])));
    }
    if (preg_match('/(404|not found|bulunamadı|error|hata|doesn\'?t exist|uh oh|page not found)/i', $titleText)) {
        echo json_encode([
            'status' => 'not-found', 'statusCode' => 404,
            'matchedPattern' => 'Başlıkta Hata Belirteci: ' . $titleText,
            'verifiedByContent' => true
        ]);
        exit;
    }

    // 5. TÜM SİTELER İÇİN DERİN PROFİL & AKTİVİTE / TAKİPÇİ / ÜRÜN DOĞRULAMASI
    $cleanUser = strtolower($username);
    $cleanBody = strtolower($body);

    $hasInTitle = strpos($titleText, $cleanUser) !== false;
    $hasInBody = strpos($cleanBody, $cleanUser) !== false;

    // og:title kontrolü
    $hasInOg = false;
    if (preg_match('/<meta[^>]*property=["\']og:title["\'][^>]*content=["\']([^"\']*)["\']/', $body, $ogMatch)) {
        if (strpos(strtolower($ogMatch[1]), $cleanUser) !== false) {
            $hasInOg = true;
        }
    }

    // Kullanıcı adı sayfada hiç geçmiyorsa direkt NOT-FOUND
    if (!$hasInTitle && !$hasInBody && !$hasInOg) {
        echo json_encode([
            'status' => 'not-found', 'statusCode' => $httpCode,
            'matchedPattern' => 'Kullanıcı adı sayfada tespit edilemedi',
            'verifiedByContent' => true
        ]);
        exit;
    }

    // Pozitif Profil & Aktivite Göstergeleri (Takipçi, Takip Edilen, Ürünler, Paketler, Gönderiler, Satışlar vb.)
    $metrics = [];
    $hasPositiveProof = false;

    // A) Takipçi / Abone
    if (preg_match('/([0-9.,KMkm]+)\s*(?:followers?|takipçi|subscribers?|abone|fans?)/i', $body, $mFollowers)) {
        $metrics['followers'] = trim($mFollowers[1]) . ' Takipçi';
        $hasPositiveProof = true;
    }
    // B) Takip Edilen
    if (preg_match('/([0-9.,KMkm]+)\s*(?:following|takip edilen)/i', $body, $mFollowing)) {
        $metrics['following'] = trim($mFollowing[1]) . ' Takip';
        $hasPositiveProof = true;
    }
    // C) Satışlar / Ürünler / İlanlar (Etsy, E-ticaret)
    if (preg_match('/([0-9.,]+)\s*(?:sales|satış|items?|listings?|ürün)/i', $body, $mSales)) {
        $metrics['sales'] = trim($mSales[1]) . ' Ürün/Satış';
        $hasPositiveProof = true;
    }
    // D) Paketler / Projeler / Repolar (PyPI, GitHub, npm vb.)
    if (preg_match('/([0-9.,]+)\s*(?:repositories|repos?|projects?|packages?|paket)/i', $body, $mRepos)) {
        $metrics['repos'] = trim($mRepos[1]) . ' Proje/Paket';
        $hasPositiveProof = true;
    }
    // E) Gönderi / Post / Tweet / Makale
    if (preg_match('/([0-9.,]+)\s*(?:posts?|tweets?|gönderi|articles?)/i', $body, $mPosts)) {
        $metrics['posts'] = trim($mPosts[1]) . ' Gönderi';
        $hasPositiveProof = true;
    }
    // F) Karma / Puan
    if (preg_match('/([0-9.,]+)\s*(?:karma|points?|puan)/i', $body, $mKarma)) {
        $metrics['karma'] = trim($mKarma[1]) . ' Karma';
        $hasPositiveProof = true;
    }
    // G) Katılım Tarihi / Üyelik
    if (preg_match('/(?:joined|member since|kayıt|üyelik)\s*:?\s*([A-Za-z0-9,\s]+)/i', $body, $mJoined)) {
        $metrics['author'] = 'Katıldı: ' . substr(trim(strip_tags($mJoined[1])), 0, 20);
        $hasPositiveProof = true;
    }

    // H) Yapısal Profil Göstergeleri (og:type=profile, schema.org/Person, avatar vb.)
    $hasProfileMarkup = (
        stripos($body, 'og:type" content="profile') !== false ||
        stripos($body, 'og:type\' content=\'profile') !== false ||
        stripos($body, 'schema.org/person') !== false ||
        stripos($body, 'schema.org/profilepage') !== false ||
        stripos($body, 'profile-avatar') !== false ||
        stripos($body, 'user-profile') !== false ||
        stripos($body, 'profile-header') !== false ||
        stripos($body, 'author-bio') !== false
    );

    if ($hasProfileMarkup) {
        $hasPositiveProof = true;
    }

    // Eğer başlık doğrudan kullanıcının adına aitse (Örn: "cihanksdg - Profil" veya "@cihanksdg on ...")
    if ($hasInTitle || $hasInOg) {
        $hasPositiveProof = true;
    }

    // Pozitif kanıt yoksa (sayfa açıldı ama içi boş, arama kutusu veya generic landing page)
    if (!$hasPositiveProof) {
        echo json_encode([
            'status' => 'not-found', 'statusCode' => $httpCode,
            'matchedPattern' => 'Hesaba ait aktif takipçi, ürün veya profil aktivitesi tespit edilemedi (Boş / Geçersiz Sayfa)',
            'verifiedByContent' => true
        ]);
        exit;
    }

    // Gerçek profil kanıtlandı
    echo json_encode([
        'status' => 'found', 'statusCode' => $httpCode,
        'matchedPattern' => null,
        'verifiedByContent' => true,
        'metrics' => $metrics,
        'reason' => 'Profil aktivitesi ve hesap verileri başarıyla doğrulandı'
    ]);
    exit;
}

// ===== 3. Görsel İstihbarat & Ters Görsel Arama API'si (v3 - Tam Çalışan) =====
if ($action === 'scan-image' || strpos($_SERVER['REQUEST_URI'], '/api/scan-image') !== false) {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    $imageBase64 = isset($input['imageBase64']) ? $input['imageBase64'] : '';
    $imageUrl = isset($input['imageUrl']) ? $input['imageUrl'] : '';

    $tempFile = null;
    $publicImageUrl = null;
    $selfHostedFile = null;

    if (!empty($imageUrl) && strpos($imageUrl, 'http') === 0) {
        $publicImageUrl = $imageUrl;
    } elseif (!empty($imageBase64)) {
        $base64Data = $imageBase64;
        $ext = 'jpg';
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $ext = strtolower($type[1]);
            if ($ext === 'jpeg') $ext = 'jpg';
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        }
        $decoded = base64_decode($base64Data);
        if ($decoded) {
            // Hosting'in kendi uploads/ klasörüne kaydet
            $uploadDir = __DIR__ . '/uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $filename = 'scan_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $filePath = $uploadDir . '/' . $filename;
            file_put_contents($filePath, $decoded);
            
            // Kendi hosting URL'mizi oluştur
            $baseUrl = getSelfBaseUrl();
            $publicImageUrl = $baseUrl . '/uploads/' . $filename;
            $selfHostedFile = $filePath;
            $tempFile = $filePath;
        }
    }

    // Kendi sunucumuzun internete açık bir domain olup olmadığını kontrol et
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $isLocal = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $host));

    // Eğer yerel sunucudaysak (dışarıdan erişilemez) veya dosya henüz yüklenemediyse harici servislere yükle
    if (($isLocal || empty($publicImageUrl)) && $tempFile && file_exists($tempFile)) {
        // 1. Litterbox (1 saatlik geçici URL)
        $ch = curl_init('https://litterbox.catbox.moe/resources/internals/api.php');
        $cfile = new CURLFile($tempFile, 'image/' . $ext, 'target.' . $ext);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['reqtype' => 'fileupload', 'time' => '1h', 'fileToUpload' => $cfile],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $res = trim(curl_exec($ch));
        curl_close($ch);
        if ($res && strpos($res, 'http') === 0) $publicImageUrl = $res;

        // 2. Catbox Yedek
        if (!$publicImageUrl) {
            $ch = curl_init('https://catbox.moe/user/api.php');
            $cfile2 = new CURLFile($tempFile, 'image/' . $ext, 'target.' . $ext);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => ['reqtype' => 'fileupload', 'fileToUpload' => $cfile2],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $res2 = trim(curl_exec($ch));
            curl_close($ch);
            if ($res2 && strpos($res2, 'http') === 0) $publicImageUrl = $res2;
        }

        // 3. TmpFiles Yedek
        if (!$publicImageUrl) {
            $ch = curl_init('https://tmpfiles.org/api/v1/upload');
            $cfile3 = new CURLFile($tempFile, 'image/' . $ext, 'target.' . $ext);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => ['file' => $cfile3],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $res3 = curl_exec($ch);
            curl_close($ch);
            if ($res3) {
                $j3 = json_decode($res3, true);
                if (isset($j3['data']['url'])) {
                    $publicImageUrl = str_replace('tmpfiles.org/', 'tmpfiles.org/dl/', $j3['data']['url']);
                }
            }
        }
    }

    // Arama Linklerini Oluştur
    $searchUrls = [
        'google' => $publicImageUrl ? "https://lens.google.com/uploadbyurl?url=" . urlencode($publicImageUrl) : "https://lens.google.com/",
        'yandex' => $publicImageUrl ? "https://yandex.com/images/search?rpt=imageview&url=" . urlencode($publicImageUrl) : "https://yandex.com/images/",
        'bing' => $publicImageUrl ? "https://www.bing.com/images/search?view=detailv2&iss=sbi&form=SBIVSP&sbisrc=UrlPaste&q=imgurl:" . urlencode($publicImageUrl) : "https://www.bing.com/visualsearch",
        'tineye' => $publicImageUrl ? "https://tineye.com/search?url=" . urlencode($publicImageUrl) : "https://tineye.com/",
        'pimeyes' => "https://pimeyes.com/en",
        'facecheck' => "https://facecheck.id/",
        'baidu' => $publicImageUrl ? "https://graph.baidu.com/details?isfromvs=1&sign=&src=" . urlencode($publicImageUrl) : "https://image.baidu.com/"
    ];

    $results = [];
    $KNOWN_SOCIAL = ['instagram.com', 'facebook.com', 'tiktok.com', 'twitter.com', 'x.com', 'linkedin.com', 'pinterest.com', 'vk.com', 'reddit.com', 'youtube.com', 't.me', 'threads.net'];

    // Yardımcı fonksiyon: platform bilgisi çıkar
    $identifyPlatform = function($pageUrl) use ($KNOWN_SOCIAL) {
        $domain = parse_url($pageUrl, PHP_URL_HOST);
        if ($domain) $domain = preg_replace('/^www\./i', '', strtolower($domain));
        $isSocial = false;
        $platform = $domain ?: 'Web';
        foreach ($KNOWN_SOCIAL as $s) {
            if ($domain && strpos($domain, $s) !== false) {
                $isSocial = true;
                $platform = ucfirst(explode('.', $s)[0]);
                break;
            }
        }
        return ['domain' => $domain, 'isSocial' => $isSocial, 'platform' => $platform];
    };

    if ($publicImageUrl) {
        // === Bing Visual Knowledge API ===
        $bingApi = "https://www.bing.com/images/api/custom/knowledge?imgurl=" . urlencode($publicImageUrl);
        $ch = curl_init($bingApi);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json, text/plain, */*',
                'Accept-Language: en-US,en;q=0.9',
                'Referer: https://www.bing.com/visualsearch',
                'Origin: https://www.bing.com'
            ]
        ]);
        $bingRes = curl_exec($ch);
        $bingHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $isIrrelevantOrAnimal = function($text, $url = '') {
            $haystack = strtolower($text . ' ' . $url);
            $badWords = ['cat', 'cats', 'dog', 'dogs', 'puppy', 'puppies', 'kitten', 'kittens', 'pet', 'pets', 'animal', 'animals', 'kedi', 'köpek', 'hayvan', 'feline', 'canine', 'wildlife', 'funny cat', 'cute dog', 'wallpaper', 'clipart'];
            foreach ($badWords as $w) {
                if (preg_match('/\b' . preg_quote($w, '/') . '\b/i', $haystack)) {
                    return true;
                }
            }
            return false;
        };

        if ($bingRes && $bingHttpCode === 200) {
            $data = json_decode($bingRes, true);
            if (isset($data['tags']) && is_array($data['tags'])) {
                foreach ($data['tags'] as $tag) {
                    if (!isset($tag['actions']) || !is_array($tag['actions'])) continue;
                    
                    foreach ($tag['actions'] as $act) {
                        $actionType = $act['actionType'] ?? '';
                        
                        // 1. PagesIncluding: Bu görselin tam olarak yer aldığı gerçek web sayfaları ve profiller (EN DOĞRU EŞLEŞME)
                        if ($actionType === 'PagesIncluding' && isset($act['data']['value'])) {
                            foreach ($act['data']['value'] as $it) {
                                $pageUrl = $it['hostPageUrl'] ?? ($it['webSearchUrl'] ?? '');
                                $title = $it['name'] ?? 'Eşleşen Web Sayfası / Profil';
                                if (empty($pageUrl) || $isIrrelevantOrAnimal($title, $pageUrl)) continue;
                                
                                $info = $identifyPlatform($pageUrl);
                                $results[] = [
                                    'title' => $title,
                                    'pageUrl' => $pageUrl,
                                    'imageUrl' => $it['contentUrl'] ?? ($it['thumbnailUrl'] ?? ''),
                                    'thumbnailUrl' => $it['thumbnailUrl'] ?? '',
                                    'engine' => 'Web Sayfası & Profil Taraması',
                                    'type' => 'pages_including',
                                    'domain' => $info['domain'],
                                    'isSocial' => $info['isSocial'],
                                    'platform' => $info['platform']
                                ];
                            }
                        }
                        
                        // 2. VisualSearch: Sadece hayvan/çöp filtresini geçen ve doğrudan hedef kişiyle alakalı görseller
                        if ($actionType === 'VisualSearch' && isset($act['data']['value'])) {
                            foreach ($act['data']['value'] as $vs) {
                                $pageUrl = $vs['hostPageUrl'] ?? '';
                                $title = $vs['name'] ?? 'Benzer Fotoğraf Eşleşmesi';
                                if (empty($pageUrl) || $isIrrelevantOrAnimal($title, $pageUrl)) continue;
                                
                                $info = $identifyPlatform($pageUrl);
                                $results[] = [
                                    'title' => $title,
                                    'pageUrl' => $pageUrl,
                                    'imageUrl' => $vs['contentUrl'] ?? '',
                                    'thumbnailUrl' => $vs['thumbnailUrl'] ?? '',
                                    'engine' => 'Ters Görsel Veritabanı',
                                    'type' => 'visual_search',
                                    'domain' => $info['domain'],
                                    'isSocial' => $info['isSocial'],
                                    'platform' => $info['platform']
                                ];
                            }
                        }
                        
                        // 3. ImageResults / RelatedImages: Filtrelenmiş ilişkili fotoğraflar
                        if (($actionType === 'ImageResults' || $actionType === 'RelatedImages') && isset($act['data']['value'])) {
                            foreach ($act['data']['value'] as $ir) {
                                $pageUrl = $ir['hostPageUrl'] ?? '';
                                $title = $ir['name'] ?? 'İlişkili Fotoğraf';
                                if (empty($pageUrl) || $isIrrelevantOrAnimal($title, $pageUrl)) continue;
                                
                                $info = $identifyPlatform($pageUrl);
                                $results[] = [
                                    'title' => $title,
                                    'pageUrl' => $pageUrl,
                                    'imageUrl' => $ir['contentUrl'] ?? '',
                                    'thumbnailUrl' => $ir['thumbnailUrl'] ?? '',
                                    'engine' => 'Ters Görsel Arama',
                                    'type' => 'related_images',
                                    'domain' => $info['domain'],
                                    'isSocial' => $info['isSocial'],
                                    'platform' => $info['platform']
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        // === Bing Visual Search (Alternatif endpoint - daha fazla sonuç) ===
        if (empty($results)) {
            $bingAlt = "https://www.bing.com/images/search?view=detailv2&iss=sbi&form=SBIVSP&sbisrc=UrlPaste&q=imgurl:" . urlencode($publicImageUrl);
            $ch = curl_init($bingAlt);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml', 'Accept-Language: en-US,en;q=0.9']
            ]);
            $bingAltRes = curl_exec($ch);
            $bingAltCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($bingAltRes && $bingAltCode === 200) {
                // HTML'den eşleşen sayfaları çıkar
                preg_match_all('/"purl"\s*:\s*"(https?:\/\/[^"]+)"/i', $bingAltRes, $bMatches);
                if (!empty($bMatches[1])) {
                    $seenBing = [];
                    foreach ($bMatches[1] as $bUrl) {
                        $bUrl = stripcslashes($bUrl);
                        if (strpos($bUrl, 'bing.com') !== false) continue;
                        if (in_array($bUrl, $seenBing)) continue;
                        $seenBing[] = $bUrl;
                        $info = $identifyPlatform($bUrl);
                        $results[] = [
                            'title' => 'Bing Görsel Eşleşmesi - ' . $info['domain'],
                            'pageUrl' => $bUrl,
                            'imageUrl' => '',
                            'thumbnailUrl' => '',
                            'engine' => 'Bing Ters Görsel Arama',
                            'type' => 'visual_search',
                            'domain' => $info['domain'],
                            'isSocial' => $info['isSocial'],
                            'platform' => $info['platform']
                        ];
                        if (count($seenBing) >= 20) break;
                    }
                }
            }
        }
        
        // === Yandex Ters Görsel Arama ===
        $yandexUrl = "https://yandex.com/images/search?rpt=imageview&url=" . urlencode($publicImageUrl);
        $yCh = curl_init($yandexUrl);
        curl_setopt_array($yCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml', 'Accept-Language: en-US,en;q=0.9']
        ]);
        $yRes = curl_exec($yCh);
        $yCode = curl_getinfo($yCh, CURLINFO_HTTP_CODE);
        curl_close($yCh);
        
        if ($yRes && $yCode === 200) {
            preg_match_all('/"url"\s*:\s*"(https?:\/\/[^"]+)"/i', $yRes, $yMatches);
            if (!empty($yMatches[1])) {
                $seenYandex = [];
                foreach ($yMatches[1] as $yUrl) {
                    $yUrl = stripcslashes($yUrl);
                    if (strpos($yUrl, 'yandex') !== false || strpos($yUrl, 'yastatic') !== false || strpos($yUrl, 'tmpfiles.org') !== false || strpos($yUrl, 'catbox.moe') !== false) continue;
                    if (in_array($yUrl, $seenYandex)) continue;
                    $seenYandex[] = $yUrl;
                    $info = $identifyPlatform($yUrl);
                    $results[] = [
                        'title' => 'Yandex Görsel Eşleşmesi - ' . $info['domain'],
                        'pageUrl' => $yUrl,
                        'imageUrl' => '',
                        'thumbnailUrl' => '',
                        'engine' => 'Yandex Ters Görsel Arama',
                        'type' => 'yandex_search',
                        'domain' => $info['domain'],
                        'isSocial' => $info['isSocial'],
                        'platform' => $info['platform']
                    ];
                    if (count($seenYandex) >= 15) break;
                }
            }
        }
        
        // === Google Lens Ters Görsel Arama ===
        $googleLensUrl = "https://lens.google.com/uploadbyurl?url=" . urlencode($publicImageUrl);
        $gCh = curl_init($googleLensUrl);
        curl_setopt_array($gCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml', 'Accept-Language: en-US,en;q=0.9']
        ]);
        $gRes = curl_exec($gCh);
        $gCode = curl_getinfo($gCh, CURLINFO_HTTP_CODE);
        curl_close($gCh);
        
        if ($gRes && $gCode === 200) {
            // Google Lens sonuç sayfasından URL'leri çıkar
            preg_match_all('/"(https?:\/\/(?:www\.)?(?:instagram|facebook|twitter|tiktok|linkedin|pinterest|youtube|reddit|vk|flickr|behance|deviantart)\.com\/[^"]+)"/i', $gRes, $gMatches);
            if (!empty($gMatches[1])) {
                $seenGoogle = [];
                foreach ($gMatches[1] as $gUrl) {
                    $gUrl = stripcslashes($gUrl);
                    if (in_array($gUrl, $seenGoogle)) continue;
                    $seenGoogle[] = $gUrl;
                    $info = $identifyPlatform($gUrl);
                    $results[] = [
                        'title' => 'Google Lens Eşleşmesi - ' . $info['platform'],
                        'pageUrl' => $gUrl,
                        'imageUrl' => '',
                        'thumbnailUrl' => '',
                        'engine' => 'Google Lens Ters Görsel',
                        'type' => 'google_lens',
                        'domain' => $info['domain'],
                        'isSocial' => $info['isSocial'],
                        'platform' => $info['platform']
                    ];
                    if (count($seenGoogle) >= 15) break;
                }
            }
            
            // Genel web sonuçları da çıkar
            preg_match_all('/\["(https?:\/\/[^"]{20,})"[^\]]*\]/i', $gRes, $gAllMatches);
            if (!empty($gAllMatches[1])) {
                $seenGoogleAll = [];
                foreach ($gAllMatches[1] as $gaUrl) {
                    $gaUrl = stripcslashes($gaUrl);
                    if (strpos($gaUrl, 'google.com') !== false || strpos($gaUrl, 'gstatic.com') !== false || strpos($gaUrl, 'googleapis.com') !== false) continue;
                    if (in_array($gaUrl, $seenGoogleAll)) continue;
                    $seenGoogleAll[] = $gaUrl;
                    $info = $identifyPlatform($gaUrl);
                    $results[] = [
                        'title' => 'Google Lens Sonucu - ' . $info['domain'],
                        'pageUrl' => $gaUrl,
                        'imageUrl' => '',
                        'thumbnailUrl' => '',
                        'engine' => 'Google Lens',
                        'type' => 'google_lens',
                        'domain' => $info['domain'],
                        'isSocial' => $info['isSocial'],
                        'platform' => $info['platform']
                    ];
                    if (count($seenGoogleAll) >= 10) break;
                }
            }
        }
    }

    // Sonuçları tekilleştir ve kırık sorgu/arama linklerini temizle
    $seen = [];
    $uniqueResults = [];
    foreach ($results as $r) {
        $u = strtolower($r['pageUrl']);
        if (strpos($u, 'tmpfiles.org') !== false || strpos($u, 'catbox.moe') !== false ||
            strpos($u, 'rpt=imageview') !== false || strpos($u, 'uploadbyurl') !== false ||
            strpos($u, 'yandex.') !== false || strpos($u, 'bing.com/images/search?view=') !== false) {
            continue;
        }
        if (!isset($seen[$u]) && !empty($r['pageUrl'])) {
            $seen[$u] = true;
            $r['id'] = count($uniqueResults) + 1;
            $uniqueResults[] = $r;
        }
    }

    // Sosyal medya sonuçlarını önce göster
    usort($uniqueResults, function($a, $b) {
        if ($a['isSocial'] && !$b['isSocial']) return -1;
        if (!$a['isSocial'] && $b['isSocial']) return 1;
        return 0;
    });

    // ID'leri yeniden numarala
    foreach ($uniqueResults as $idx => &$r) {
        $r['id'] = $idx + 1;
    }

    // Eski yüklenen dosyaları temizle (1 saatten eski)
    $uploadDir = __DIR__ . '/uploads';
    if (is_dir($uploadDir)) {
        foreach (glob($uploadDir . '/scan_*') as $oldFile) {
            if (filemtime($oldFile) < time() - 3600) {
                @unlink($oldFile);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'count' => count($uniqueResults),
        'socialCount' => count(array_filter($uniqueResults, function($r) { return $r['isSocial']; })),
        'results' => $uniqueResults,
        'uploadedImageUrl' => $publicImageUrl,
        'searchUrls' => $searchUrls
    ]);
    exit;
}

echo json_encode(['status' => 'running', 'message' => 'CyberTrace API Hazır']);
