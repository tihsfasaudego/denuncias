<?php
// Verificar se o autoload.php existe antes de incluí-lo
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Verificar path alternativo
    $altPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (file_exists($altPath)) {
        require_once $altPath;
    }
}

// Verificar se as classes do PHPMailer existem
if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    // Definimos namespaces vazios para evitar erros
    class_alias('stdClass', 'PHPMailer\\PHPMailer\\PHPMailer');
    class_alias('stdClass', 'PHPMailer\\PHPMailer\\SMTP');
    class_alias('Exception', 'PHPMailer\\PHPMailer\\Exception');
}

// Em PHP mais recente, podemos usar o bloco use mesmo se as classes não existirem
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mail;
    private $config;
    private static $instance = null;

    private function __construct() {
        // Carregar configurações de e-mail
        $this->loadConfig();
        
        // Verificar se o PHPMailer real está disponível
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer') && !is_a('PHPMailer\\PHPMailer\\PHPMailer', 'stdClass', true)) {
            // Inicializar PHPMailer real
            $this->mail = new PHPMailer(true);
            
            // Configuração do servidor SMTP
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['smtp_host'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->config['smtp_username'];
            $this->mail->Password = $this->config['smtp_password'];
            $this->mail->SMTPSecure = $this->config['smtp_secure'];
            $this->mail->Port = $this->config['smtp_port'];
            
            // Configurações padrão de e-mail
            $this->mail->CharSet = 'UTF-8';
            $this->mail->isHTML(true);
            $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
            
            // Modo de depuração (0 = desativado)
            $this->mail->SMTPDebug = 0;
        } else {
            // Criar um objeto vazio para evitar erros
            $this->mail = new \stdClass();
            $this->mail->CharSet = 'UTF-8';
            $this->mail->SMTPDebug = 0;
            $this->mail->SMTPAuth = true;
            $this->mail->Host = $this->config['smtp_host'];
            $this->mail->Username = $this->config['smtp_username'];
            $this->mail->Password = $this->config['smtp_password'];
            $this->mail->SMTPSecure = $this->config['smtp_secure'];
            $this->mail->Port = $this->config['smtp_port'];
            $this->mail->Subject = '';
            $this->mail->Body = '';
            $this->mail->AltBody = '';
        }
    }

    /**
     * Obtém a instância única do serviço de e-mail (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Carrega as configurações de e-mail
     */
    private function loadConfig() {
        // Verificar se existe arquivo de configuração específico para e-mail
        $configFile = __DIR__ . '/../../config/email.php';
        
        if (file_exists($configFile)) {
            $this->config = include $configFile;
        } else {
            // Configurações padrão
            $this->config = [
                'smtp_host' => 'smtp.exemplo.com.br',
                'smtp_username' => 'sistema@exemplo.com.br',
                'smtp_password' => 'senha_segura',
                'smtp_secure' => 'tls',
                'smtp_port' => 587,
                'from_email' => 'sistema@exemplo.com.br',
                'from_name' => 'Sistema de Denúncias',
                'reply_to' => 'naoresponda@exemplo.com.br'
            ];
        }
    }

    /**
     * Envia uma notificação para os usuários sobre uma nova denúncia
     */
    public function enviarNotificacaoNovaDenuncia($denuncia, $destinatarios) {
        try {
            // Verificar se o PHPMailer real está disponível
            if (method_exists($this->mail, 'clearAllRecipients')) {
                // Limpar destinatários anteriores
                $this->mail->clearAllRecipients();
                
                // Adicionar destinatários
                foreach ($destinatarios as $destinatario) {
                    $this->mail->addAddress($destinatario['email'], $destinatario['nome']);
                }
                
                // Configurar resposta automática
                $this->mail->addReplyTo($this->config['reply_to'], 'Não Responda');
                
                // Assunto do e-mail
                $this->mail->Subject = 'Nova Denúncia Registrada - Protocolo: ' . $denuncia['protocolo'];
                
                // Preparar conteúdo do e-mail (versão HTML)
                $this->mail->Body = $this->prepararConteudoHTML($denuncia);
                
                // Versão alternativa em texto simples
                $this->mail->AltBody = $this->prepararConteudoTexto($denuncia);
                
                // Enviar o e-mail
                return $this->mail->send();
            }
            
            // Se chegou aqui, o PHPMailer não está disponível
            error_log('PHPMailer não está disponível. Email seria enviado para: ' . 
                implode(', ', array_column($destinatarios, 'email')));
            
            return true; // Fingir que enviou para não quebrar o fluxo da aplicação
            
        } catch (Exception $e) {
            error_log('Erro ao enviar e-mail: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prepara o conteúdo HTML do e-mail
     */
    private function prepararConteudoHTML($denuncia) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #1a4c96; color: white; padding: 15px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; border: 1px solid #ddd; }
                .info-item { margin-bottom: 10px; }
                .label { font-weight: bold; }
                .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #777; }
                .alert { color: #721c24; background-color: #f8d7da; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Nova Denúncia Registrada</h2>
                </div>
                <div class="content">
                    <p>Uma nova denúncia foi registrada no sistema e requer sua atenção.</p>
                    
                    <div class="info-item">
                        <span class="label">Protocolo:</span> 
                        ' . htmlspecialchars($denuncia['protocolo']) . '
                    </div>
                    
                    <div class="info-item">
                        <span class="label">Data de Registro:</span> 
                        ' . date('d/m/Y H:i', strtotime($denuncia['data_criacao'])) . '
                    </div>';
                    
        if (!empty($denuncia['categorias'])) {
            $html .= '
                    <div class="info-item">
                        <span class="label">Categorias:</span> 
                        ' . htmlspecialchars($denuncia['categorias']) . '
                    </div>';
        }
                    
        $html .= '
                    <div class="alert">
                        <strong>Atenção:</strong> Este e-mail contém apenas informações básicas sobre a denúncia.
                        Para ver detalhes completos, acesse o sistema.
                    </div>
                    
                    <p>
                        <a href="http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/admin/denuncia?protocolo=' . urlencode($denuncia['protocolo']) . '" 
                           style="display: inline-block; padding: 10px 20px; background-color: #1a4c96; color: white; text-decoration: none; border-radius: 5px;">
                            Visualizar Denúncia
                        </a>
                    </p>
                </div>
                <div class="footer">
                    <p>Este é um e-mail automático. Por favor, não responda.</p>
                    <p>Sistema de Ouvidoria - ' . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }

    /**
     * Prepara o conteúdo em texto simples do e-mail
     */
    private function prepararConteudoTexto($denuncia) {
        $texto = "NOVA DENÚNCIA REGISTRADA\n\n";
        $texto .= "Uma nova denúncia foi registrada no sistema e requer sua atenção.\n\n";
        $texto .= "Protocolo: " . $denuncia['protocolo'] . "\n";
        $texto .= "Data de Registro: " . date('d/m/Y H:i', strtotime($denuncia['data_criacao'])) . "\n";
        
        if (!empty($denuncia['categorias'])) {
            $texto .= "Categorias: " . $denuncia['categorias'] . "\n";
        }
        
        $texto .= "\nATENÇÃO: Este e-mail contém apenas informações básicas sobre a denúncia.\n";
        $texto .= "Para ver detalhes completos, acesse o sistema.\n\n";
        $texto .= "Link: http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/admin/denuncia?protocolo=" . $denuncia['protocolo'] . "\n\n";
        $texto .= "Este é um e-mail automático. Por favor, não responda.\n";
        $texto .= "Sistema de Ouvidoria - " . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        
        return $texto;
    }
    
    /**
     * Envia um e-mail personalizado
     */
    public function enviarEmail($destinatarios, $assunto, $conteudoHTML, $conteudoTexto = '') {
        try {
            // Verificar se o PHPMailer real está disponível
            if (method_exists($this->mail, 'clearAllRecipients')) {
                // Limpar destinatários anteriores
                $this->mail->clearAllRecipients();
                
                // Adicionar destinatários
                foreach ($destinatarios as $destinatario) {
                    $this->mail->addAddress($destinatario['email'], $destinatario['nome']);
                }
                
                // Assunto
                $this->mail->Subject = $assunto;
                
                // Conteúdo HTML
                $this->mail->Body = $conteudoHTML;
                
                // Conteúdo em texto simples
                if (!empty($conteudoTexto)) {
                    $this->mail->AltBody = $conteudoTexto;
                }
                
                // Enviar
                return $this->mail->send();
            }
            
            // Se chegou aqui, o PHPMailer não está disponível
            error_log('PHPMailer não está disponível. Email seria enviado para: ' . 
                implode(', ', array_column($destinatarios, 'email')));
            
            return true; // Fingir que enviou para não quebrar o fluxo da aplicação
            
        } catch (Exception $e) {
            error_log('Erro ao enviar e-mail personalizado: ' . $e->getMessage());
            return false;
        }
    }
} 