### 2. Configuração do Banco de Dados

-- ==============================
-- CRIAÇÃO DO BANCO E USUÁRIO
-- ==============================

DROP DATABASE visitorpass;

CREATE DATABASE IF NOT EXISTS `visitorpass`
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

DROP USER IF EXISTS 'vpuser'@'localhost';

CREATE USER 'vpuser'@'localhost' IDENTIFIED BY '@gml_sstv2026!';

GRANT ALL PRIVILEGES ON visitorpass.* TO 'vpuser'@'localhost';

FLUSH PRIVILEGES;

SHOW GRANTS FOR 'vpuser'@'localhost';

USE `visitorpass`;

-- ==============================
-- TABELA PRINCIPAL: visitorinfo
-- ==============================

CREATE TABLE IF NOT EXISTS `visitorinfo` 
(
  `id` BIGINT(12) NOT NULL AUTO_INCREMENT,
  `nome_completo` VARCHAR(255) NOT NULL,
  `numero_cpf` CHAR(11) NOT NULL,
  `veiculo_placa` CHAR(7) NULL,
  `encontrar_com` VARCHAR(255) NOT NULL,
  `empresa` VARCHAR(255) DEFAULT NULL,
  `tipo_visita` VARCHAR(50) NOT NULL,
  `telefone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `foto_caminho` VARCHAR(500) DEFAULT NULL,
  `data_entrada` DATETIME NOT NULL,
  `data_saida` DATETIME DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `cadastro_original_id` BIGINT(12) NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_data_entrada` (`data_entrada`),
  KEY `idx_status` (`status`),
  KEY `idx_visitor_cpf` (`numero_cpf`),
  KEY `idx_visitor_placa` (`veiculo_placa`),
  KEY `idx_visitorinfo_cadastro_original` (`cadastro_original_id`),
  KEY `idx_visitorinfo_cpf_status` (`numero_cpf`, `status`),
  KEY `idx_status_data` (`status`, `data_entrada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ==============================
-- TABELA DE USUÁRIOS 
-- ==============================

CREATE TABLE IF NOT EXISTS usuarios (
id INT AUTO_INCREMENT PRIMARY KEY,
nome VARCHAR(100) NOT NULL,
usuario VARCHAR(50) UNIQUE NOT NULL,
senha VARCHAR(255) NOT NULL,
nivel_acesso ENUM('user', 'supervisor', 'admin') NOT NULL DEFAULT 'user',
ativo TINYINT(1) DEFAULT 1,
senha_temporaria TINYINT(1) DEFAULT 1,
data TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ==============================
-- TABELA DE CONTROLE DE LOGIN
-- ==============================

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_count INT NOT NULL DEFAULT 0,
    lock_until DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuario_ip (usuario, ip_address),
    INDEX idx_lock_until (lock_until),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    session_token_hash CHAR(64) NOT NULL,
    session_id VARCHAR(128) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    last_seen DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_sessions_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_user_sessions_active (active),
    INDEX idx_user_sessions_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE IF NOT EXISTS auditoria_visitantes (
    `id` BIGINT(12) AUTO_INCREMENT PRIMARY KEY,
    `tabela` VARCHAR(50) NOT NULL,
    `registro_id` BIGINT(12) NOT NULL,
    `acao` ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    `dados_antigos` JSON,
    `dados_novos` JSON,
    `usuario_id` INT,
    `data_acao` DATETIME NOT NULL,
    INDEX `idx_registro` (tabela, registro_id),
    INDEX `idx_usuario` (usuario_id),
    INDEX `idx_data` (data_acao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Trigger para INSERT

DROP TRIGGER IF EXISTS visitorinfo_after_insert;
DELIMITER $$
CREATE TRIGGER visitorinfo_after_insert
AFTER INSERT ON visitorinfo
FOR EACH ROW
BEGIN
    INSERT INTO auditoria_visitantes (
        tabela, 
        registro_id, 
        acao, 
        dados_novos, 
        usuario_id, 
        data_acao
    ) VALUES (
        'visitorinfo',
        NEW.id,
        'INSERT',
        JSON_OBJECT(
            'nome_completo', NEW.nome_completo,
            'numero_cpf', NEW.numero_cpf,
            'status', NEW.status
        ),
        @usuario_id,
        NOW()
    );
END$$
DELIMITER ;

-- Trigger para UPDATE

DROP TRIGGER IF EXISTS visitorinfo_after_update;
DELIMITER $$
CREATE TRIGGER visitorinfo_after_update
AFTER UPDATE ON visitorinfo
FOR EACH ROW
BEGIN
    INSERT INTO auditoria_visitantes (
        tabela, 
        registro_id, 
        acao, 
        dados_antigos, 
        dados_novos, 
        usuario_id, 
        data_acao
    ) VALUES (
        'visitorinfo',
        NEW.id,
        'UPDATE',
        JSON_OBJECT(
            'status', OLD.status,
            'data_saida', OLD.data_saida
        ),
        JSON_OBJECT(
            'status', NEW.status,
            'data_saida', NEW.data_saida
        ),
        @usuario_id,
        NOW()
    );
END$$
DELIMITER ;

-- Trigger para DELETE (opcional)

DROP TRIGGER IF EXISTS visitorinfo_after_delete;
DELIMITER $$
CREATE TRIGGER visitorinfo_after_delete
AFTER DELETE ON visitorinfo
FOR EACH ROW
BEGIN
    INSERT INTO auditoria_visitantes (
        tabela, 
        registro_id, 
        acao, 
        dados_antigos, 
        usuario_id, 
        data_acao
    ) VALUES (
        'visitorinfo',
        OLD.id,
        'DELETE',
        JSON_OBJECT(
            'nome_completo', OLD.nome_completo,
            'numero_cpf', OLD.numero_cpf,
            'status', OLD.status
        ),
        @usuario_id,
        NOW()
    );
END$$
DELIMITER ;
-- ==============================
-- TRIGGERS DE VALIDAÇÃO
-- ==============================
DELIMITER $$

CREATE TRIGGER `before_insert_visitorinfo`
BEFORE INSERT ON `visitorinfo`
FOR EACH ROW
BEGIN
-- Remove caracteres indesejados e padroniza
SET NEW.numero_cpf = REPLACE(REPLACE(NEW.numero_cpf, '.', ''), '-', '');

-- Apenas formata placa se não for NULL ou vazia
IF NEW.veiculo_placa IS NOT NULL AND NEW.veiculo_placa != '' THEN
SET NEW.veiculo_placa = UPPER(REPLACE(NEW.veiculo_placa, '-', ''));
END IF;

-- Validação CPF: 11 dígitos
IF NOT (NEW.numero_cpf REGEXP '^[0-9]{11}$') THEN
SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'CPF inválido: deve conter 11 dígitos numéricos.';
END IF;

-- ✅ VALIDAÇÃO PLACA APENAS SE PREENCHIDA
IF NEW.veiculo_placa IS NOT NULL AND NEW.veiculo_placa != '' THEN
IF NOT (
NEW.veiculo_placa REGEXP '^[A-Z]{3}[0-9]{4}$' OR
      NEW.veiculo_placa REGEXP '^[A-Z]{3}[0-9][A-Z][0-9]{2}$'
) THEN
SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Placa inválida: formato incorreto. Use AAA9999 ou AAA9A99.';
END IF;
END IF;
END$$

CREATE TRIGGER `before_update_visitorinfo`
BEFORE UPDATE ON `visitorinfo`
FOR EACH ROW
BEGIN
-- Remove caracteres indesejados e padroniza
SET NEW.numero_cpf = REPLACE(REPLACE(NEW.numero_cpf, '.', ''), '-', '');

IF NEW.veiculo_placa IS NOT NULL AND NEW.veiculo_placa != '' THEN
SET NEW.veiculo_placa = UPPER(REPLACE(NEW.veiculo_placa, '-', ''));
END IF;

-- Validação CPF
IF NOT (NEW.numero_cpf REGEXP '^[0-9]{11}$') THEN
SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'CPF inválido: deve conter 11 dígitos numéricos.';
END IF;

-- Validação placa
IF NEW.veiculo_placa IS NOT NULL AND NEW.veiculo_placa != '' THEN
IF NOT (
NEW.veiculo_placa REGEXP '^[A-Z]{3}[0-9]{4}$' OR
      NEW.veiculo_placa REGEXP '^[A-Z]{3}[0-9][A-Z][0-9]{2}$'
) THEN
SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Placa inválida: formato incorreto. Use AAA9999 ou AAA9A99.';
END IF;
END IF;
END$$

DELIMITER ;
