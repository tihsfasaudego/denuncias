<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Canal de Denúncias'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS básico inline -->
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container-fluid { max-width: 1200px; margin: 0 auto; }
        .card { 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            margin-bottom: 20px;
        }
        .text-primary { color: #007cba !important; }
        .btn-primary { 
            background-color: #007cba; 
            border-color: #007cba; 
        }
        .breadcrumb-item a { color: #007cba; }
        .badge { font-size: 0.875em; }
        .hsfa-card { 
            border: none; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.08); 
            border-radius: 10px; 
        }
        .hsfa-title { 
            color: #003a4d !important; 
            font-weight: 600; 
        }
    </style>
</head>
<body>
    <?php if (isset($isAdminPage) && $isAdminPage): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin/dashboard">
                <i class="fas fa-shield-alt me-2"></i>
                Admin - Canal de Denúncias
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/admin/logout">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Sair
                </a>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <main>
        <?php if (isset($content)) echo $content; ?>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JS básico -->
    <script>
        // Toast simples
        function showToast(message, type = 'info') {
            alert(message);
        }
        
        // Placeholder para outras funções
        window.HSFA = {
            toast: {
                success: function(msg) { console.log('Success:', msg); },
                error: function(msg) { console.log('Error:', msg); },
                info: function(msg) { console.log('Info:', msg); }
            }
        };
    </script>
</body>
</html>
