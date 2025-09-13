<?php
/**
 * Gerenciador de assets (CSS/JS)
 * Minifica, combina e otimiza arquivos para melhor performance
 */
class AssetManager {
    private static $instance = null;
    private $cache;
    private $assetsDir;
    private $publicDir;
    private $cacheDir;
    private $version;
    
    private function __construct() {
        // Verificar se a classe Cache existe antes de usar
        if (class_exists('Cache')) {
            $this->cache = Cache::getInstance();
        } else {
            $this->cache = null;
        }
        
        $this->assetsDir = BASE_PATH . '/assets';
        $this->publicDir = BASE_PATH . '/public';
        $this->cacheDir = BASE_PATH . '/storage/assets';
        $this->version = Environment::get('ASSETS_VERSION', time());
        
        $this->ensureDirectories();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Garante que os diretórios necessários existam
     */
    private function ensureDirectories() {
        $dirs = [$this->assetsDir, $this->cacheDir];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Combina e minifica arquivos CSS
     */
    public function combineCSS($files, $name = 'combined') {
        // Se não há cache disponível, usar fallback
        if (!$this->cache) {
            return $this->combineCSSFallback($files, $name);
        }
        
        $cacheKey = "css_{$name}_" . md5(serialize($files) . $this->version);
        
        return $this->cache->remember($cacheKey, function() use ($files, $name) {
            return $this->combineCSSFallback($files, $name);
        }, 3600); // Cache por 1 hora
    }
    
    /**
     * Fallback para combinação de CSS sem cache
     */
    private function combineCSSFallback($files, $name) {
        $combinedContent = '';
        $lastModified = 0;
        
        foreach ($files as $file) {
            $filePath = $this->resolveAssetPath($file);
            
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $content = $this->processCSS($content, dirname($filePath));
                $combinedContent .= "/* {$file} */\n{$content}\n";
                $lastModified = max($lastModified, filemtime($filePath));
            } else {
                error_log("Asset CSS não encontrado: {$file}");
            }
        }
        
        // Minificar CSS combinado
        $minifiedContent = $this->minifyCSS($combinedContent);
        
        // Salvar arquivo combinado
        $outputFile = $this->cacheDir . "/{$name}.min.css";
        file_put_contents($outputFile, $minifiedContent);
        
        // Retornar URL pública
        $publicUrl = "/assets/{$name}.min.css?v=" . $this->version;
        
        // Criar symlink se não existir
        $publicPath = $this->publicDir . "/assets/{$name}.min.css";
        $this->ensurePublicAsset($outputFile, $publicPath);
        
        return $publicUrl;
    }
    
    /**
     * Combina e minifica arquivos JavaScript
     */
    public function combineJS($files, $name = 'combined') {
        // Se não há cache disponível, usar fallback
        if (!$this->cache) {
            return $this->combineJSFallback($files, $name);
        }
        
        $cacheKey = "js_{$name}_" . md5(serialize($files) . $this->version);
        
        return $this->cache->remember($cacheKey, function() use ($files, $name) {
            return $this->combineJSFallback($files, $name);
        }, 3600); // Cache por 1 hora
    }
    
    /**
     * Fallback para combinação de JS sem cache
     */
    private function combineJSFallback($files, $name) {
        $combinedContent = '';
        $lastModified = 0;
        
        foreach ($files as $file) {
            $filePath = $this->resolveAssetPath($file);
            
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $content = $this->processJS($content);
                $combinedContent .= "/* {$file} */\n{$content};\n";
                $lastModified = max($lastModified, filemtime($filePath));
            } else {
                error_log("Asset JS não encontrado: {$file}");
            }
        }
        
        // Minificar JS combinado
        $minifiedContent = $this->minifyJS($combinedContent);
        
        // Salvar arquivo combinado
        $outputFile = $this->cacheDir . "/{$name}.min.js";
        file_put_contents($outputFile, $minifiedContent);
        
        // Retornar URL pública
        $publicUrl = "/assets/{$name}.min.js?v=" . $this->version;
        
        // Criar symlink se não existir
        $publicPath = $this->publicDir . "/assets/{$name}.min.js";
        $this->ensurePublicAsset($outputFile, $publicPath);
        
        return $publicUrl;
    }
    
    /**
     * Resolve o caminho do asset
     */
    private function resolveAssetPath($file) {
        // Se é um caminho absoluto, usar como está
        if (strpos($file, '/') === 0) {
            return BASE_PATH . $file;
        }
        
        // Se é um arquivo no diretório public
        if (file_exists($this->publicDir . '/' . $file)) {
            return $this->publicDir . '/' . $file;
        }
        
        // Se é um arquivo no diretório assets
        if (file_exists($this->assetsDir . '/' . $file)) {
            return $this->assetsDir . '/' . $file;
        }
        
        // Tentar caminhos relativos comuns
        $commonPaths = [
            $this->publicDir . '/css/' . $file,
            $this->publicDir . '/js/' . $file,
            BASE_PATH . '/' . $file
        ];
        
        foreach ($commonPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return $file; // Retornar como está se não encontrar
    }
    
    /**
     * Processa arquivo CSS
     */
    private function processCSS($content, $basePath) {
        // Converter URLs relativos para absolutos
        $content = preg_replace_callback('/url\([\'"]?([^\'"\)]+)[\'"]?\)/', function($matches) use ($basePath) {
            $url = $matches[1];
            
            // Skip URLs absolutos, data URLs, etc.
            if (preg_match('/^(https?:\/\/|data:|\/)/i', $url)) {
                return $matches[0];
            }
            
            // Converter para URL absoluto
            $absolutePath = realpath($basePath . '/' . $url);
            if ($absolutePath && strpos($absolutePath, BASE_PATH) === 0) {
                $relativePath = substr($absolutePath, strlen(BASE_PATH));
                return "url('{$relativePath}')";
            }
            
            return $matches[0];
        }, $content);
        
        return $content;
    }
    
    /**
     * Processa arquivo JavaScript
     */
    private function processJS($content) {
        // Adicionar ponto e vírgula no final se não tiver
        $content = rtrim($content);
        if (!empty($content) && substr($content, -1) !== ';') {
            $content .= ';';
        }
        
        return $content;
    }
    
    /**
     * Minifica CSS - MÉTODO PÚBLICO para compatibilidade
     */
    public function minifyCSS($css) {
        // Remover comentários
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remover espaços desnecessários
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remover espaços ao redor de caracteres especiais
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        
        // Remover ponto e vírgula desnecessário antes de }
        $css = preg_replace('/;+}/', '}', $css);
        
        // Remover espaços no início e fim
        return trim($css);
    }
    
    /**
     * Minifica JavaScript - MÉTODO PÚBLICO para compatibilidade
     */
    public function minifyJS($js) {
        // Esta é uma minificação básica
        // Para produção, considere usar ferramentas mais robustas
        
        // Remover comentários de linha
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remover comentários de bloco
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        
        // Remover quebras de linha desnecessárias
        $js = preg_replace('/\n\s*\n/', "\n", $js);
        
        // Remover espaços em excesso
        $js = preg_replace('/[ \t]+/', ' ', $js);
        
        // Remover espaços ao redor de operadores
        $js = preg_replace('/\s*([=+\-*\/{}();,])\s*/', '$1', $js);
        
        return trim($js);
    }
    
    /**
     * Garante que o asset está disponível publicamente
     */
    private function ensurePublicAsset($sourcePath, $publicPath) {
        $publicDir = dirname($publicPath);
        
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        
        // Copiar arquivo se não existir ou for mais antigo
        if (!file_exists($publicPath) || filemtime($sourcePath) > filemtime($publicPath)) {
            copy($sourcePath, $publicPath);
        }
    }
    
    /**
     * Otimiza imagem
     */
    public function optimizeImage($imagePath, $quality = 85) {
        // Se não há cache disponível, usar fallback
        if (!$this->cache) {
            return $this->optimizeImageFallback($imagePath, $quality);
        }
        
        $cacheKey = "img_" . md5($imagePath . $quality . filemtime($imagePath));
        
        return $this->cache->remember($cacheKey, function() use ($imagePath, $quality) {
            return $this->optimizeImageFallback($imagePath, $quality);
        }, 86400); // Cache por 24 horas
    }
    
    /**
     * Fallback para otimização de imagem sem cache
     */
    private function optimizeImageFallback($imagePath, $quality) {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $optimizedPath = $this->cacheDir . '/' . basename($imagePath, ".{$extension}") . "_opt.{$extension}";
        
        try {
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($imagePath);
                    imagejpeg($image, $optimizedPath, $quality);
                    break;
                    
                case 'png':
                    $image = imagecreatefrompng($imagePath);
                    imagesavealpha($image, true);
                    imagepng($image, $optimizedPath, 9);
                    break;
                    
                case 'gif':
                    copy($imagePath, $optimizedPath); // GIF não comprime bem
                    break;
                    
                default:
                    return $imagePath; // Tipo não suportado
            }
            
            if (isset($image)) {
                imagedestroy($image);
            }
            
            // Verificar se a otimização resultou em arquivo menor
            if (file_exists($optimizedPath) && filesize($optimizedPath) < filesize($imagePath)) {
                return $optimizedPath;
            }
            
        } catch (Exception $e) {
            error_log("Erro ao otimizar imagem: " . $e->getMessage());
        }
        
        return $imagePath; // Retornar original se otimização falhou
    }
    
    /**
     * Gera sprite de ícones CSS
     */
    public function generateIconSprite($iconDir, $spriteName = 'icons') {
        // Se não há cache disponível, usar fallback
        if (!$this->cache) {
            return $this->generateIconSpriteFallback($iconDir, $spriteName);
        }
        
        $cacheKey = "sprite_{$spriteName}_" . md5($iconDir . $this->version);
        
        return $this->cache->remember($cacheKey, function() use ($iconDir, $spriteName) {
            return $this->generateIconSpriteFallback($iconDir, $spriteName);
        }, 86400); // Cache por 24 horas
    }
    
    /**
     * Fallback para geração de sprite sem cache
     */
    private function generateIconSpriteFallback($iconDir, $spriteName) {
        $icons = glob($iconDir . '/*.{png,jpg,gif}', GLOB_BRACE);
        
        if (empty($icons)) {
            return null;
        }
        
        $spriteWidth = 0;
        $spriteHeight = 0;
        $iconData = [];
        
        // Calcular dimensões do sprite
        foreach ($icons as $icon) {
            $info = getimagesize($icon);
            $iconData[] = [
                'path' => $icon,
                'name' => pathinfo($icon, PATHINFO_FILENAME),
                'width' => $info[0],
                'height' => $info[1],
                'x' => $spriteWidth,
                'y' => 0
            ];
            
            $spriteWidth += $info[0];
            $spriteHeight = max($spriteHeight, $info[1]);
        }
        
        // Criar sprite
        $sprite = imagecreatetruecolor($spriteWidth, $spriteHeight);
        imagealphablending($sprite, false);
        imagesavealpha($sprite, true);
        
        $currentX = 0;
        $cssRules = [];
        
        foreach ($iconData as $icon) {
            $iconImage = imagecreatefrompng($icon['path']);
            imagecopy($sprite, $iconImage, $currentX, 0, 0, 0, $icon['width'], $icon['height']);
            
            // Gerar regra CSS
            $cssRules[] = ".icon-{$icon['name']} { background-position: -{$currentX}px 0; width: {$icon['width']}px; height: {$icon['height']}px; }";
            
            $currentX += $icon['width'];
            imagedestroy($iconImage);
        }
        
        // Salvar sprite
        $spritePath = $this->cacheDir . "/{$spriteName}.png";
        imagepng($sprite, $spritePath);
        imagedestroy($sprite);
        
        // Gerar CSS
        $css = ".icon { display: inline-block; background-image: url('/assets/{$spriteName}.png'); background-repeat: no-repeat; }\n";
        $css .= implode("\n", $cssRules);
        
        $cssPath = $this->cacheDir . "/{$spriteName}.css";
        file_put_contents($cssPath, $css);
        
        // Disponibilizar publicamente
        $this->ensurePublicAsset($spritePath, $this->publicDir . "/assets/{$spriteName}.png");
        $this->ensurePublicAsset($cssPath, $this->publicDir . "/assets/{$spriteName}.css");
        
        return "/assets/{$spriteName}.css?v=" . $this->version;
    }
    
    /**
     * Limpa cache de assets
     */
    public function clearCache() {
        // Limpar cache interno se disponível
        if ($this->cache) {
            $this->cache->delete('css_*');
            $this->cache->delete('js_*');
            $this->cache->delete('img_*');
            $this->cache->delete('sprite_*');
        }
        
        // Limpar arquivos de cache
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Gera manifest de assets para versionamento
     */
    public function generateManifest() {
        $manifest = [];
        
        // Escanear diretórios de assets
        $dirs = [
            $this->publicDir . '/css',
            $this->publicDir . '/js',
            $this->publicDir . '/images'
        ];
        
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir)
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $relativePath = substr($file->getPathname(), strlen($this->publicDir));
                        $hash = substr(md5_file($file->getPathname()), 0, 8);
                        $manifest[$relativePath] = $relativePath . '?v=' . $hash;
                    }
                }
            }
        }
        
        $manifestPath = $this->publicDir . '/assets-manifest.json';
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
        
        return $manifest;
    }
}
