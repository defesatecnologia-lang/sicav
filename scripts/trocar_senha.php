<?php
ob_start();

require_once __DIR__ . '/../config/database.php';

if ($_SESSION['usuario']->senha_temporaria === 0) {
    header('Location: index.php?page=home');
    exit;
}

$db = new database();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $_SESSION['erro_troca_senha'] = 'Token de seguranca invalido.';
    } else {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';
    
    // Validações
    if (empty($senha_atual) || empty($nova_senha) || empty($confirma_senha)) {
        $_SESSION['erro_troca_senha'] = 'Todos os campos são obrigatórios!';
    } elseif ($nova_senha !== $confirma_senha) {
        $_SESSION['erro_troca_senha'] = 'As novas senhas não coincidem!';
    } elseif (strlen($nova_senha) < 6) {
        $_SESSION['erro_troca_senha'] = 'A nova senha deve ter pelo menos 6 caracteres!';
    } else {
        $sqlHash = "SELECT senha FROM usuarios WHERE id = :id LIMIT 1";
        $resultHash = $db->select($sqlHash, [':id' => $_SESSION['usuario']->id]);
        $hashAtual = ($resultHash['status'] === 'success' && !empty($resultHash['data'])) ? $resultHash['data'][0]->senha : null;
        if (!$hashAtual || !password_verify($senha_atual, $hashAtual)) {
            $_SESSION['erro_troca_senha'] = 'Senha atual incorreta!';
        } else {
            $senha_hash = password_hash($nova_senha, PASSWORD_ARGON2ID);
            $sql = "UPDATE usuarios SET senha = :senha, senha_temporaria = 0 WHERE id = :id";
            $params = [
                ':senha' => $senha_hash,
                ':id' => $_SESSION['usuario']->id
            ];
            $result = $db->update($sql, $params);            
            if ($result['status'] === 'success') {
                $_SESSION['sucesso_troca_senha'] = 'Senha alterada com sucesso!';
                $_SESSION['usuario']->senha_temporaria = 0;
                unset($_SESSION['usuario']->senha);
                ob_end_clean();
                header('Location: index.php?page=home');
                exit;
            } else {
                $_SESSION['erro_troca_senha'] = 'Erro ao alterar a senha!';
            }
        }
    }
    }
}
require_once __DIR__ . '/../inc/navbar.php';
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Troca de Senha Obrigatória</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Por segurança, você deve trocar sua senha temporária por uma senha permanente de sua escolha.
                    </div>
                    
                    <?php if (isset($_SESSION['erro_troca_senha'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_SESSION['erro_troca_senha'], ENT_QUOTES, 'UTF-8') ?>
                            <?php unset($_SESSION['erro_troca_senha']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-3">
                            <label for="senha_atual" class="form-label">Senha Atual (Temporária)</label>
                            <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                        </div>
                        <div class="mb-3">
                            <label for="nova_senha" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="nova_senha" name="nova_senha" 
                                minlength="6" required>
                            <div class="form-text">Mínimo de 6 caracteres</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirma_senha" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="confirma_senha" name="confirma_senha" 
                                minlength="6" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="submit" class="btn btn-primary">
                                <i class="fas fa-key me-1"></i>Trocar Senha
                            </button>
                            <a href="?page=home" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
