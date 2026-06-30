<?php
require_once __DIR__ . '/../config/database.php';

$db = new database();
$mensagem_erro = '';
$mensagem_sucesso = '';
$senhaTemporariaModal = null;

function gerarSenhaTemporariaSegura(int $bytes = 9): string
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

function registrarAuditoriaResetSenha(database $dbConn, int $adminId, object $usuarioAlvo): void
{
    $dadosNovos = json_encode([
        'evento' => 'RESET_SENHA_TEMPORARIA',
        'usuario_alvo_id' => (int)($usuarioAlvo->id ?? 0),
        'usuario_alvo_login' => (string)($usuarioAlvo->usuario ?? ''),
        'executado_por_usuario_id' => $adminId,
    ], JSON_UNESCAPED_UNICODE);

    $dbConn->insert(
        "INSERT INTO auditoria_visitantes (tabela, registro_id, acao, dados_novos, usuario_id, data_acao)
        VALUES ('usuarios', :registro_id, 'UPDATE', :dados_novos, :usuario_id, NOW())",
        [
            ':registro_id' => (int)($usuarioAlvo->id ?? 0),
            ':dados_novos' => $dadosNovos,
            ':usuario_id' => $adminId,
        ]
    );
}

$id_usuario = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id_usuario || $id_usuario <= 0) {
    header('Location: index.php?page=listarUsuario');
    exit;
}

$sql = "SELECT id, usuario, nome, nivel_acesso, ativo FROM usuarios WHERE id = :id";
$params = [':id' => $id_usuario];
$result = $db->select($sql, $params);

if ($result['status'] !== 'success' || count($result['data']) === 0) {
    $_SESSION['erro'] = 'Usuário não encontrado!';
    header('Location: index.php?page=listarUsuario');
    exit;
}

$usuario = $result['data'][0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $mensagem_erro = 'Token de seguranca invalido.';
    } else {
    $nome = trim($_POST['nome'] ?? '');
    $usuario_novo = trim($_POST['usuario'] ?? '');
    $nivel_acesso = $_POST['nivel_acesso'] ?? 'user';
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $redefinir_senha = isset($_POST['redefinir_senha']);
    
    // Validações
    if (empty($nome) || empty($usuario_novo)) {
        $mensagem_erro = 'Nome e usuário são obrigatórios!';
    } else {
        $sql_verifica = "SELECT id FROM usuarios WHERE usuario = :usuario AND id != :id";
        $params_verifica = [
            ':usuario' => $usuario_novo,
            ':id' => $id_usuario
        ];
        $result_verifica = $db->select($sql_verifica, $params_verifica);
        
        if (count($result_verifica['data']) > 0) {
            $mensagem_erro = 'Este nome de usuário já está em uso!';
        } else {
            if ($redefinir_senha) {
                $novaSenhaTemporaria = gerarSenhaTemporariaSegura();
                $senha_temporaria = password_hash($novaSenhaTemporaria, PASSWORD_DEFAULT);
                $sql_update = "UPDATE usuarios SET nome = :nome, usuario = :usuario, nivel_acesso = :nivel_acesso, ativo = :ativo, senha = :senha, senha_temporaria = 1 WHERE id = :id";
                $params_update = [
                    ':nome' => $nome,
                    ':usuario' => $usuario_novo,
                    ':nivel_acesso' => $nivel_acesso,
                    ':ativo' => $ativo,
                    ':senha' => $senha_temporaria,
                    ':id' => $id_usuario
                ];
            } else {
                $sql_update = "UPDATE usuarios SET nome = :nome, usuario = :usuario, nivel_acesso = :nivel_acesso, ativo = :ativo WHERE id = :id";
                $params_update = [
                    ':nome' => $nome,
                    ':usuario' => $usuario_novo,
                    ':nivel_acesso' => $nivel_acesso,
                    ':ativo' => $ativo,
                    ':id' => $id_usuario
                ];
            }
            
            $result_update = $db->update($sql_update, $params_update);
            
            if ($result_update['status'] === 'success') {
                $mensagem_sucesso = 'Usuário atualizado com sucesso!';
                $usuario->nome = $nome;
                $usuario->usuario = $usuario_novo;
                $usuario->nivel_acesso = $nivel_acesso;
                $usuario->ativo = $ativo;
                
                if ($redefinir_senha) {
                    $senhaTemporariaModal = $novaSenhaTemporaria;
                    $adminId = (int)($_SESSION['usuario']->id ?? 0);
                    if ($adminId > 0) {
                        registrarAuditoriaResetSenha($db, $adminId, $usuario);
                    }
                    $mensagem_sucesso .= ' Senha temporaria gerada com sucesso. Repassar ao usuario por canal seguro.';
                }
            } else {
                $mensagem_erro = 'Erro ao atualizar usuário!';
            }
        }
    }
    }
}

require_once __DIR__ . '/../inc/navbar.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>Editar Usuário
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($mensagem_erro): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($mensagem_erro, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <?php if ($mensagem_sucesso): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($mensagem_sucesso, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome Completo *</label>
                                    <input type="text" class="form-control" id="nome" name="nome"
                                        value="<?= htmlspecialchars($usuario->nome) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="usuario" class="form-label">Nome de Usuário *</label>
                                    <input type="text" class="form-control" id="usuario" name="usuario"
                                        value="<?= htmlspecialchars($usuario->usuario) ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nivel_acesso" class="form-label">Nível de Acesso</label>
                                    <select class="form-select" id="nivel_acesso" name="nivel_acesso">
                                        <option value="user" <?= $usuario->nivel_acesso === 'user' ? 'selected' : '' ?>>
                                            Usuário</option>
                                        <option value="supervisor"
                                            <?= $usuario->nivel_acesso === 'supervisor' ? 'selected' : '' ?>>Supervisor
                                        </option>
                                        <option value="admin"
                                            <?= $usuario->nivel_acesso === 'admin' ? 'selected' : '' ?>>Administrador
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="ativo">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="ativo" name="ativo"
                                            value="1" <?= $usuario->ativo ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="ativo">
                                            Usuário ativo
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check p-3 rounded border bg-light">
                                <input class="form-check-input" type="checkbox" id="redefinir_senha"
                                    name="redefinir_senha">
                                <label class="form-check-label" for="redefinir_senha">
                                    <strong>Redefinir senha para temporária</strong>
                                </label>
                                <div class="form-text">
                                    Marque esta opção para gerar uma senha temporária aleatória e segura.
                                    O usuário será obrigado a trocar a senha no próximo login.
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="?page=listarUsuario" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Voltar
                            </a>
                            <button type="submit" name="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Card de informações importantes -->
            <div class="card mt-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informações Importantes
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Campos marcados com * são obrigatórios</li>
                        <li>Ao redefinir a senha, o usuário será obrigado a trocá-la no próximo login</li>
                        <li>Usuários inativos não poderão fazer login no sistema</li>
                        <li><strong>Supervisor:</strong> Acesso a relatórios, sem administração de usuários</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($senhaTemporariaModal !== null): ?>
<div class="modal fade" id="tempPasswordModal" tabindex="-1" aria-labelledby="tempPasswordModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="tempPasswordModalLabel">Senha temporaria gerada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2"><strong>Exibicao unica:</strong> copie e repasse agora ao usuario.</p>
                <input type="text" class="form-control" id="tempPasswordValue"
                    value="<?= htmlspecialchars($senhaTemporariaModal, ENT_QUOTES, 'UTF-8') ?>" readonly>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
<script nonce="<?= htmlspecialchars($GLOBALS['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('tempPasswordModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;

    const modal = new bootstrap.Modal(modalEl, {
        backdrop: 'static',
        keyboard: false
    });
    modal.show();

    modalEl.addEventListener('hidden.bs.modal', function() {
        const input = document.getElementById('tempPasswordValue');
        if (input) input.value = '';
        modalEl.remove();
    }, {
        once: true
    });
});
</script>
<?php endif; ?>

<style nonce="<?= htmlspecialchars($GLOBALS['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
#redefinir_senha.form-check-input {
    width: 1.35rem;
    height: 1.35rem;
    margin-top: 0.15rem;
    border: 2px solid #0d6efd;
    background-color: #fff;
    cursor: pointer;
}

#redefinir_senha.form-check-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

#redefinir_senha.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>