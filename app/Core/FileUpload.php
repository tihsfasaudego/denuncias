<?php
/**
 * Classe para upload seguro de arquivos
 * Implementa validações robustas contra ataques de upload
 */
class FileUpload {
    private $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf'
    ];
    
    private $maxFileSize;
    private $uploadDir;
    private $allowWebp;
    
    public function __construct($uploadDir = null, $maxFileSize = null, $allowWebp = false) {
        $this->uploadDir = $uploadDir ?: UPLOAD_DIR;
        $this->maxFileSize = $maxFileSize ?: MAX_UPLOAD_SIZE;
        $this->allowWebp = $allowWebp;
        
        if ($allowWebp) {
            $this->allowedMimeTypes['image/webp'] = 'webp';
        }
        
        $this->ensureUploadDirectory();
    }
    
    /**
     * Faz upload de um arquivo com validações de segurança
     * 
     * @param array $file Array $_FILES do arquivo
     * @param string $prefix Prefixo para o nome do arquivo
     * @return array Resultado do upload
     */
    public function upload($file, $prefix = '') {
        try {
            // Validações básicas
            $this->validateUploadErrors($file);
            $this->validateFileSize($file['size']);
            
            // Validação de tipo MIME real
            $mimeType = $this->validateMimeType($file['tmp_name']);
            
            // Validação de extensão
            $extension = $this->getExtensionFromMime($mimeType);
            
            // Validação adicional para imagens
            if (strpos($mimeType, 'image/') === 0) {
                $this->validateImage($file['tmp_name']);
            }
            
            // Validação adicional para PDFs
            if ($mimeType === 'application/pdf') {
                $this->validatePdf($file['tmp_name']);
            }
            
            // Gerar nome seguro
            $fileName = $this->generateSecureFileName($prefix, $extension);
            $filePath = $this->uploadDir . '/' . $fileName;
            
            // Mover arquivo
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception("Erro ao salvar o arquivo no servidor");
            }
            
            // Aplicar permissões seguras
            chmod($filePath, 0644);
            
            // Log do upload
            error_log("Upload realizado: {$fileName}, MIME: {$mimeType}, Tamanho: {$file['size']} bytes");
            
            return [
                'success' => true,
                'filename' => $fileName,
                'original_name' => $file['name'],
                'mime_type' => $mimeType,
                'size' => $file['size'],
                'path' => $filePath
            ];
            
        } catch (Exception $e) {
            error_log("Erro no upload: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Valida erros de upload do PHP
     */
    private function validateUploadErrors($file) {
        if (!isset($file['error'])) {
            throw new Exception("Nenhum arquivo foi enviado");
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception("O arquivo é muito grande");
            case UPLOAD_ERR_PARTIAL:
                throw new Exception("O arquivo foi enviado apenas parcialmente");
            case UPLOAD_ERR_NO_FILE:
                throw new Exception("Nenhum arquivo foi enviado");
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new Exception("Diretório temporário não encontrado");
            case UPLOAD_ERR_CANT_WRITE:
                throw new Exception("Falha ao escrever o arquivo no disco");
            case UPLOAD_ERR_EXTENSION:
                throw new Exception("Upload bloqueado por extensão");
            default:
                throw new Exception("Erro desconhecido no upload");
        }
    }
    
    /**
     * Valida o tamanho do arquivo
     */
    private function validateFileSize($size) {
        if ($size <= 0) {
            throw new Exception("Arquivo vazio");
        }
        
        if ($size > $this->maxFileSize) {
            $maxMB = round($this->maxFileSize / (1024 * 1024), 1);
            throw new Exception("Arquivo muito grande. Máximo permitido: {$maxMB}MB");
        }
    }
    
    /**
     * Valida o tipo MIME real do arquivo
     */
    private function validateMimeType($tmpName) {
        // Usar fileinfo para verificar o tipo MIME real
        if (!function_exists('finfo_open')) {
            throw new Exception("Extensão fileinfo não está disponível");
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpName);
        finfo_close($finfo);
        
        if (!$mimeType) {
            throw new Exception("Não foi possível determinar o tipo do arquivo");
        }
        
        if (!array_key_exists($mimeType, $this->allowedMimeTypes)) {
            $allowed = implode(', ', array_unique(array_values($this->allowedMimeTypes)));
            throw new Exception("Tipo de arquivo não permitido. Tipos aceitos: {$allowed}");
        }
        
        return $mimeType;
    }
    
    /**
     * Obtém a extensão baseada no tipo MIME
     */
    private function getExtensionFromMime($mimeType) {
        return $this->allowedMimeTypes[$mimeType];
    }
    
    /**
     * Validação específica para imagens
     */
    private function validateImage($tmpName) {
        $imageInfo = @getimagesize($tmpName);
        
        if ($imageInfo === false) {
            throw new Exception("Arquivo não é uma imagem válida");
        }
        
        // Verificar dimensões máximas
        $maxWidth = 4000;
        $maxHeight = 4000;
        
        if ($imageInfo[0] > $maxWidth || $imageInfo[1] > $maxHeight) {
            throw new Exception("Imagem muito grande. Máximo: {$maxWidth}x{$maxHeight} pixels");
        }
        
        // Verificar tipos de imagem suportados pelo GD
        $supportedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
        if ($this->allowWebp && defined('IMAGETYPE_WEBP')) {
            $supportedTypes[] = IMAGETYPE_WEBP;
        }
        
        if (!in_array($imageInfo[2], $supportedTypes)) {
            throw new Exception("Tipo de imagem não suportado");
        }
    }
    
    /**
     * Validação específica para PDFs
     */
    private function validatePdf($tmpName) {
        // Verificar se começa com header PDF
        $handle = fopen($tmpName, 'rb');
        $header = fread($handle, 4);
        fclose($handle);
        
        if ($header !== '%PDF') {
            throw new Exception("Arquivo PDF inválido");
        }
        
        // Verificar se não é muito grande para um PDF
        $fileSize = filesize($tmpName);
        $maxPdfSize = 50 * 1024 * 1024; // 50MB máximo para PDF
        
        if ($fileSize > $maxPdfSize) {
            throw new Exception("PDF muito grande. Máximo: 50MB");
        }
    }
    
    /**
     * Gera um nome de arquivo seguro
     */
    private function generateSecureFileName($prefix, $extension) {
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        $prefix = $prefix ? preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) . '_' : '';
        
        return $prefix . $timestamp . '_' . $random . '.' . $extension;
    }
    
    /**
     * Garante que o diretório de upload existe e é seguro
     */
    private function ensureUploadDirectory() {
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                throw new Exception("Não foi possível criar o diretório de uploads");
            }
        }
        
        // Criar arquivo .htaccess para segurança
        $htaccessPath = $this->uploadDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "# Bloquear execução de scripts\n";
            $htaccessContent .= "Options -ExecCGI\n";
            $htaccessContent .= "AddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\n";
            $htaccessContent .= "RemoveHandler .php .phtml .php3 .php4 .php5 .php6\n";
            $htaccessContent .= "\n# Bloquear acesso direto a arquivos perigosos\n";
            $htaccessContent .= "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php6|pl|py|jsp|asp|sh|cgi)$\">\n";
            $htaccessContent .= "    Require all denied\n";
            $htaccessContent .= "</FilesMatch>\n";
            
            file_put_contents($htaccessPath, $htaccessContent);
        }
        
        // Criar index.php vazio para evitar listagem
        $indexPath = $this->uploadDir . '/index.php';
        if (!file_exists($indexPath)) {
            file_put_contents($indexPath, '<?php // Acesso negado');
        }
    }
    
    /**
     * Remove um arquivo enviado
     */
    public function delete($filename) {
        $filePath = $this->uploadDir . '/' . basename($filename);
        
        if (file_exists($filePath) && is_file($filePath)) {
            if (unlink($filePath)) {
                error_log("Arquivo removido: {$filename}");
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Obtém informações sobre um arquivo
     */
    public function getFileInfo($filename) {
        $filePath = $this->uploadDir . '/' . basename($filename);
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        return [
            'filename' => $filename,
            'size' => filesize($filePath),
            'mime_type' => mime_content_type($filePath),
            'modified' => filemtime($filePath)
        ];
    }
    
    /**
     * Lista arquivos no diretório de upload
     */
    public function listFiles($pattern = '*') {
        $files = glob($this->uploadDir . '/' . $pattern);
        return array_map('basename', $files);
    }
}
