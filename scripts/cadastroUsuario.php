<?php
ob_start();
session_start();
// Include necessary files
require_once __DIR__ . '/../inc/navbar.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/usuarios_helpers.php';

// ⭐⭐ VERIFICAÇÃO DE ACESSO ADMIN ⭐⭐
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']->nivel_acesso !== 'admin') {
    header('Location: index.php?page=nao_autorizado');
    exit;
}
$db = new database();

function inserirUsuario($dbConn) {
    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
            if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
                $_SESSION['erro_cadastro'] = 'Token de seguranca invalido.';
                header('Location: index.php?page=administracao');
                exit;
            }

            $nome = trim($_POST['text_nome'] ?? '');
            $usuario = trim($_POST['text_usuario'] ?? '');
            $senha = $_POST['text_senha'] ?? '';
            $nivel_acesso = $_POST['text_nivel_acesso'] ?? 'user';

            if (empty($nome) || empty($usuario) || empty($senha)) {
                $_SESSION['erro_cadastro'] = 'Todos os campos são obrigatórios!';
                header('Location: index.php?page=administracao');
                exit;
            }
            if (usuarioExiste($dbConn, $usuario)) {
                $_SESSION['erro_cadastro'] = 'Este nome de usuário já está em uso.';
                header('Location: index.php?page=administracao');
                exit;
            }

            if (nomeExiste($dbConn, $nome)) {
                $_SESSION['erro_cadastro'] = 'Este nome de usuário já está em uso.';
                header('Location: index.php?page=administracao');
                exit;
            }

            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuarios (nome, usuario, senha, nivel_acesso, senha_temporaria) VALUES (:nome, :usuario, :senha, :nivel_acesso, 1)";
            $params = [
                ':nome' => $nome,
                ':usuario' => $usuario,
                ':senha' => $senha_hash,
                ':nivel_acesso' => $nivel_acesso
            ];
            
            $result = $dbConn->query($sql, $params);

            if ($result['status'] === 'success') {
                $_SESSION["status_cadastro"] = true;
                ob_end_clean();
                header('Location: index.php?page=administracao');
                exit;
            } else {
                $_SESSION['erro_cadastro'] = 'Erro ao cadastrar usuário!';
                header('Location: index.php?page=administracao');
                exit;
            }
        }
    } catch (Exception $ex) {
        $_SESSION['erro_cadastro'] = 'Erro ao cadastrar usuário!';
        header('Location: index.php?page=administracao');
        exit;
    }
}

inserirUsuario($db);
$listUsuarios = listarUsuariosComSenhaTemporaria($db);
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <h3>Lista de Usuários Cadastrados</h3>
            
            <?php if (isset($_SESSION['erro_cadastro'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['erro_cadastro'], ENT_QUOTES, 'UTF-8') ?>
                    <?php unset($_SESSION['erro_cadastro']); ?>
                </div>
            <?php endif; ?>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Usuário</th>
                        <th>Nível de Acesso</th>
                        <th>Data de Cadastro</th>
                        <th>Status Senha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($listUsuarios)): ?>
                        <?php foreach ($listUsuarios as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario->id ?? '') ?></td>
                                <td><?= htmlspecialchars($usuario->nome ?? '') ?></td>
                                <td><?= htmlspecialchars($usuario->usuario ?? '') ?></td>
                                <td>
                                    <span class="badge 
                                        <?php 
                                            // ⭐⭐ CORREÇÃO: Badges para os três níveis ⭐⭐
                                            switch($usuario->nivel_acesso) {
                                                case 'admin':
                                                    echo 'bg-danger';
                                                    break;
                                                case 'supervisor':
                                                    echo 'bg-info';
                                                    break;
                                                default:
                                                    echo 'bg-primary';
                                            }
                                        ?>">
                                        <?= htmlspecialchars($usuario->nivel_acesso ?? 'user') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($usuario->data ?? '') ?></td>
                                <td>
                                    <?php if ($usuario->senha_temporaria == 1): ?>
                                        <span class="badge bg-warning">Senha Temporária</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Senha Definida</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Nenhum usuário cadastrado</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <a href="?page=administracao" class="btn btn-secondary">Voltar para Administração</a>
        </div>
    </div>
</div>
