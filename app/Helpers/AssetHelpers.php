<?php
/**
 * Helper functions para gerenciamento de assets
 */

// Carregar dependências necessárias
require_once __DIR__ . '/../Core/Environment.php';

/**
 * Inclui arquivos CSS combinados e minificados
 */
function css($files, $name = null) {
    if (!is_array($files)) {
        $files = [$files];
    }
    
    $name = $name ?: 'css_' . md5(serialize($files));
    
    if (Environment::isProduction()) {
        try {
            require_once __DIR__ . '/../Core/AssetManager.php';
            $assetManager = AssetManager::getInstance();
            $url = $assetManager->combineCSS($files, $name);
            return "<link rel=\"stylesheet\" href=\"{$url}\">";
        } catch (Exception $e) {
            error_log("Erro no AssetManager: " . $e->getMessage());
            // Fallback para modo desenvolvimento
        }
    }
    
    // Em desenvolvimento ou fallback, carregar arquivos individuais
    $output = '';
    foreach ($files as $file) {
        $url = asset_url($file);
        $output .= "<link rel=\"stylesheet\" href=\"{$url}\">\n";
    }
    return $output;
}

/**
 * Inclui arquivos JavaScript combinados e minificados
 */
function js($files, $name = null) {
    if (!is_array($files)) {
        $files = [$files];
    }
    
    $name = $name ?: 'js_' . md5(serialize($files));
    
    if (Environment::isProduction()) {
        try {
            require_once __DIR__ . '/../Core/AssetManager.php';
            $assetManager = AssetManager::getInstance();
            $url = $assetManager->combineJS($files, $name);
            return "<script src=\"{$url}\"></script>";
        } catch (Exception $e) {
            error_log("Erro no AssetManager: " . $e->getMessage());
            // Fallback para modo desenvolvimento
        }
    }
    
    // Em desenvolvimento ou fallback, carregar arquivos individuais
    $output = '';
    foreach ($files as $file) {
        $url = asset_url($file);
        $output .= "<script src=\"{$url}\"></script>\n";
    }
    return $output;
}

/**
 * Gera URL para asset com versionamento
 */
function asset_url($path, $version = null) {
    $version = $version ?: Environment::get('ASSETS_VERSION', time());
    
    // Se já tem protocolo, retornar como está
    if (preg_match('/^https?:\/\//', $path)) {
        return $path;
    }
    
    // Garantir que começa com /
    if (strpos($path, '/') !== 0) {
        $path = '/' . $path;
    }
    
    // Adicionar versão se não tiver
    if (strpos($path, '?') === false) {
        $path .= '?v=' . $version;
    }
    
    return $path;
}

/**
 * Inclui CSS inline minificado
 */
function inline_css($css) {
    if (Environment::isProduction()) {
        try {
            require_once __DIR__ . '/../Core/AssetManager.php';
            $assetManager = AssetManager::getInstance();
            $css = $assetManager->minifyCSS($css);
        } catch (Exception $e) {
            error_log("Erro no AssetManager para CSS inline: " . $e->getMessage());
            // Manter CSS original em caso de erro
        }
    }
    
    return "<style>{$css}</style>";
}

/**
 * Inclui JavaScript inline minificado
 */
function inline_js($js) {
    if (Environment::isProduction()) {
        try {
            require_once __DIR__ . '/../Core/AssetManager.php';
            $assetManager = AssetManager::getInstance();
            $js = $assetManager->minifyJS($js);
        } catch (Exception $e) {
            error_log("Erro no AssetManager para JS inline: " . $e->getMessage());
            // Manter JS original em caso de erro
        }
    }
    
    return "<script>{$js}</script>";
}

/**
 * Gera URL para imagem otimizada
 */
function optimized_image($path, $quality = 85) {
    if (!Environment::isProduction()) {
        return asset_url($path);
    }
    
    $fullPath = BASE_PATH . '/public' . $path;
    
    if (!file_exists($fullPath)) {
        return asset_url($path);
    }
    
    try {
        require_once __DIR__ . '/../Core/AssetManager.php';
        $assetManager = AssetManager::getInstance();
        $optimizedPath = $assetManager->optimizeImage($fullPath, $quality);
    } catch (Exception $e) {
        error_log("Erro no AssetManager para otimização de imagem: " . $e->getMessage());
        return asset_url($path);
    }
    
    if ($optimizedPath !== $fullPath) {
        // Retornar URL da imagem otimizada
        $relativePath = substr($optimizedPath, strlen(BASE_PATH . '/public'));
        return asset_url($relativePath);
    }
    
    return asset_url($path);
}

/**
 * Pré-carrega recursos críticos
 */
function preload_asset($url, $type = 'script') {
    $url = asset_url($url);
    
    switch ($type) {
        case 'style':
        case 'css':
            return "<link rel=\"preload\" href=\"{$url}\" as=\"style\" onload=\"this.onload=null;this.rel='stylesheet'\">";
            
        case 'script':
        case 'js':
            return "<link rel=\"preload\" href=\"{$url}\" as=\"script\">";
            
        case 'font':
            return "<link rel=\"preload\" href=\"{$url}\" as=\"font\" type=\"font/woff2\" crossorigin>";
            
        case 'image':
            return "<link rel=\"preload\" href=\"{$url}\" as=\"image\">";
            
        default:
            return "<link rel=\"preload\" href=\"{$url}\">";
    }
}

/**
 * Gera critical CSS inline
 */
function critical_css($files = []) {
    // Retornar CSS básico inline para evitar erros
    return '<style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .container-fluid { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 10px 0; }
        .text-primary { color: #007cba !important; }
        .btn-primary { background-color: #007cba; border-color: #007cba; }
    </style>';
}

/**
 * Extrai CSS crítico (simplificado)
 */
function extract_critical_css($css) {
    // Esta é uma implementação básica
    // Para produção, considere usar ferramentas como Critical ou PurgeCSS
    
    $critical = '';
    $lines = explode("\n", $css);
    $inCriticalBlock = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Marcar blocos críticos
        if (strpos($line, '/* critical:start */') !== false) {
            $inCriticalBlock = true;
            continue;
        }
        
        if (strpos($line, '/* critical:end */') !== false) {
            $inCriticalBlock = false;
            continue;
        }
        
        // Incluir CSS de elementos visíveis primeiro
        if ($inCriticalBlock || 
            preg_match('/^(body|html|\.header|\.navbar|\.hero|\.main|h[1-6]|\.btn)/', $line)) {
            $critical .= $line . "\n";
        }
    }
    
    return $critical;
}

/**
 * Defer do carregamento de CSS não crítico
 */
function defer_css($files) {
    if (!is_array($files)) {
        $files = [$files];
    }
    
    $output = '';
    foreach ($files as $file) {
        $url = asset_url($file);
        $output .= "<link rel=\"preload\" href=\"{$url}\" as=\"style\" onload=\"this.onload=null;this.rel='stylesheet'\">\n";
        $output .= "<noscript><link rel=\"stylesheet\" href=\"{$url}\"></noscript>\n";
    }
    
    return $output;
}

/**
 * Carrega JavaScript de forma assíncrona
 */
function async_js($files) {
    if (!is_array($files)) {
        $files = [$files];
    }
    
    $output = '';
    foreach ($files as $file) {
        $url = asset_url($file);
        $output .= "<script async src=\"{$url}\"></script>\n";
    }
    
    return $output;
}

/**
 * Gera Service Worker para cache de assets
 */
function generate_service_worker() {
    $swContent = "
const CACHE_NAME = 'hsfa-denuncias-v" . Environment::get('ASSETS_VERSION', '1') . "';
const urlsToCache = [
    '/',
    '/css/hsfa-theme.css',
    '/css/styles.css',
    '/js/scripts.js',
    '/css/images/logo1.png'
];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                if (response) {
                    return response;
                }
                return fetch(event.request);
            })
    );
});
";
    
    $swPath = BASE_PATH . '/public/sw.js';
    file_put_contents($swPath, $swContent);
    
    return '/sw.js';
}

/**
 * Registra Service Worker
 */
function register_service_worker() {
    $swUrl = generate_service_worker();
    
    return "
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('{$swUrl}')
                .then(function(registration) {
                    console.log('SW registered: ', registration);
                })
                .catch(function(registrationError) {
                    console.log('SW registration failed: ', registrationError);
                });
        });
    }
    </script>";
}
