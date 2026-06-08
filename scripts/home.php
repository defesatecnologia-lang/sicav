<?php
require_once __DIR__ . '/../inc/navbar.php';
?>

<style nonce="<?= htmlspecialchars($GLOBALS['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
body {
    margin: 0;
    padding: 0;

    background:
        linear-gradient(rgba(255, 255, 255, 0.35), rgba(255, 255, 255, 0.35)),
        url('<?= htmlspecialchars($GLOBALS['APP_BASE_PATH'] ?? '', ENT_QUOTES, 'UTF-8') ?>/imagens/tela_fundo.png');

    background-size: 38% auto;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    height: 100vh;
}

@keyframes zoom {
    from {
        background-size: 30%;
    }

    to {
        background-size: 50%;
    }
}

.logo-fixa {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-60%);
    width: 260px;
    z-index: 9999;
    opacity: 0.95;
}
</style>

<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <div class="jumbotron bg-light p-5 rounded">
                <h1 class="display-4">Sistema de Controle de Visitantes</h1>
                <p class="lead">Bem-vindo, <?= htmlspecialchars($_SESSION['usuario']->nome ?? '', ENT_QUOTES, 'UTF-8') ?>!</p>
                <hr class="my-4">
                <div class="row mt-5">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Cadastrar Visitante</h5>
                                <p class="card-text">Registrar Visitante</p>
                                <a href="?page=cadastro_visitantes" class="btn btn-primary">Acessar</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Visitantes Ativos</h5>
                                <p class="card-text">Ver visitas em andamento</p>
                                <a href="?page=listar_visitantes" class="btn btn-info">Visualizar</a>
                            </div>
                        </div>
                    </div>
                    <?php if ($_SESSION['usuario']->nivel_acesso === 'admin' || $_SESSION['usuario']->nivel_acesso === 'supervisor'): ?>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Relatórios</h5>
                                <p class="card-text">Relatórios e estatísticas</p>
                                <a href="?page=relatorios_visitantes" class="btn btn-success">Gerar</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($_SESSION['usuario']->nivel_acesso === 'admin'): ?>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title">Administração</h5>
                                <p class="card-text">Gerenciar usuários</p>
                                <a href="?page=administracao" class="btn btn-warning">Administrar</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


