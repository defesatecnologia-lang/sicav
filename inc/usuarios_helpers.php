<?php
/**
 * Helper functions for user management
 * Consolidates shared functionality used across multiple scripts
 * 
 * Usage:
 *   require_once __DIR__ . '/usuarios_helpers.php';
 *   $usuarios = listarUsuarios($db);
 *   $usuarios = listarUsuariosComSenhaTemporaria($db);
 */

/**
 * Listar todos os usuários com informações básicas
 * 
 * @param database $dbConn Database connection object
 * @return array Array of user objects or empty array on error
 */
function listarUsuarios(database $dbConn): array
{
    try {
        $sql = "SELECT id, nome, usuario, nivel_acesso, ativo, data FROM usuarios ORDER BY id DESC";
        $result = $dbConn->select($sql);
        return $result['status'] === 'success' ? $result['data'] : [];
    } catch (Exception $e) {
        error_log('Erro ao buscar usuarios: ' . $e->getMessage());
        return [];
    }
}

/**
 * Listar todos os usuários incluindo status de senha temporária
 * Usado para gerenciamento de senhas em administração
 * 
 * @param database $dbConn Database connection object
 * @return array Array of user objects with senha_temporaria field
 */
function listarUsuariosComSenhaTemporaria(database $dbConn): array
{
    try {
        $sql = "SELECT id, nome, usuario, nivel_acesso, data, senha_temporaria FROM usuarios ORDER BY id DESC";
        $result = $dbConn->select($sql);

        if ($result['status'] === 'success') {
            return $result['data'];
        }
        return [];
    } catch (Exception $e) {
        error_log('Erro ao buscar usuarios: ' . $e->getMessage());
        return [];
    }
}

/**
 * Verificar se um nome de usuário já existe
 * 
 * @param database $dbConn Database connection object
 * @param string $usuario Username to check
 * @return bool True if user exists, false otherwise
 */
function usuarioExiste(database $dbConn, string $usuario): bool
{
    try {
        $sql = "SELECT COUNT(*) AS total FROM usuarios WHERE usuario = :usuario";
        $params = [':usuario' => $usuario];
        $result = $dbConn->select($sql, $params);

        if ($result['status'] === 'success' && count($result['data']) > 0) {
            return $result['data'][0]->total >= 1;
        }
        return false;
    } catch (Exception $ex) {
        error_log('Erro ao verificar usuario: ' . $ex->getMessage());
        return false;
    }
}

/**
 * Verificar se um nome já existe
 * 
 * @param database $dbConn Database connection object
 * @param string $nome Full name to check
 * @return bool True if name exists, false otherwise
 */
function nomeExiste(database $dbConn, string $nome): bool
{
    try {
        $sql = "SELECT COUNT(*) AS total FROM usuarios WHERE nome = :nome";
        $params = [':nome' => $nome];
        $result = $dbConn->select($sql, $params);

        if ($result['status'] === 'success' && count($result['data']) > 0) {
            return $result['data'][0]->total >= 1;
        }
        return false;
    } catch (Exception $ex) {
        error_log('Erro ao verificar nome: ' . $ex->getMessage());
        return false;
    }
}
