<?php
/**
 * Biblioteca JWT simples para autenticação de API
 * Implementação básica do JSON Web Token
 */
class JWT {
    private static $key;
    private static $algorithm = 'HS256';
    
    public static function init() {
        self::$key = Environment::get('JWT_SECRET', 'hsfa_denuncias_secret_key_change_in_production');
    }
    
    /**
     * Codifica um payload em JWT
     */
    public static function encode($payload, $exp = null) {
        if (!self::$key) {
            self::init();
        }
        
        $header = [
            'typ' => 'JWT',
            'alg' => self::$algorithm
        ];
        
        // Adicionar claims padrão
        $now = time();
        $payload['iat'] = $now; // Issued at
        $payload['exp'] = $exp ?: ($now + 3600); // Expire em 1 hora por padrão
        $payload['iss'] = Environment::get('APP_URL', 'localhost'); // Issuer
        
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, self::$key, true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    /**
     * Decodifica e valida um JWT
     */
    public static function decode($jwt) {
        if (!self::$key) {
            self::init();
        }
        
        $parts = explode('.', $jwt);
        
        if (count($parts) !== 3) {
            throw new Exception('Token JWT inválido');
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Verificar assinatura
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, self::$key, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            throw new Exception('Assinatura JWT inválida');
        }
        
        // Decodificar payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        
        if (!$payload) {
            throw new Exception('Payload JWT inválido');
        }
        
        // Verificar expiração
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token JWT expirado');
        }
        
        return $payload;
    }
    
    /**
     * Verifica se um token é válido
     */
    public static function verify($jwt) {
        try {
            self::decode($jwt);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtém payload sem verificar assinatura (para debug)
     */
    public static function getPayload($jwt) {
        $parts = explode('.', $jwt);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        return json_decode(self::base64UrlDecode($parts[1]), true);
    }
    
    /**
     * Codificação Base64 URL-safe
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Decodificação Base64 URL-safe
     */
    private static function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    /**
     * Gera token de acesso
     */
    public static function generateAccessToken($user, $permissions = []) {
        $payload = [
            'sub' => $user['id'], // Subject (user ID)
            'email' => $user['email'] ?? $user['usuario'],
            'name' => $user['nome'] ?? $user['usuario'],
            'type' => 'access',
            'permissions' => $permissions,
            'exp' => time() + 3600 // 1 hora
        ];
        
        return self::encode($payload);
    }
    
    /**
     * Gera token de refresh
     */
    public static function generateRefreshToken($user) {
        $payload = [
            'sub' => $user['id'],
            'type' => 'refresh',
            'exp' => time() + (7 * 24 * 3600) // 7 dias
        ];
        
        return self::encode($payload);
    }
    
    /**
     * Valida token de API
     */
    public static function validateApiToken($token) {
        try {
            $payload = self::decode($token);
            
            if ($payload['type'] !== 'access') {
                throw new Exception('Tipo de token inválido');
            }
            
            return $payload;
            
        } catch (Exception $e) {
            throw new Exception('Token inválido: ' . $e->getMessage());
        }
    }
}
