

# Usuários para o sistema de visitantes

# Criação de usuários para o sistema de visitantes na implantação inicial do banco de dados. Estes usuários são criados com senhas criptografadas e níveis de acesso específicos.



INSERT INTO usuarios (nome, usuario, senha, nivel_acesso)
VALUES ('Administrador Sistema', 'admin', '$2y$10$aodHCC/gG3.TbOs07aFyROWJXw1M650nkG9se.lLEs1EVftTiQip6', 'admin');

INSERT INTO usuarios (nome, usuario, senha, nivel_acesso)
VALUES ('Supervisor Sistema', 'supervisor', '$2y$10$Ku0U6/n8KOsG/PUBoDJHyOVPEt8dLWgfzVWzC8cHqIz/zFavgEjzK', 'supervisor');