<?php
/**
 * Verifica se o usuário tem permissão para acessar a página
 * Delega para userHasAccess() em security.php para evitar duplicação
 * @param string $nivel_requerido Nível requerido para a página
 * @return bool True se tem acesso, false caso contrário
 */
function verificarAcesso(string $nivel_requerido = 'user'): bool
{
    return userHasAccess($nivel_requerido);
}

/**
 * Redireciona para página de não autorizado se não tiver acesso
 */
function requererAcesso(string $nivel_requerido = 'user'): void
{
    if (!verificarAcesso($nivel_requerido)) {
        if (!headers_sent()) {
            header('Location: index.php?page=nao_autorizado');
            exit;
        }

        echo 'Acesso nao autorizado. <a href="index.php?page=nao_autorizado">Continuar</a>.';
        exit;
    }
}
