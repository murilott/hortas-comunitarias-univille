SET @adminCargoUUID = (SELECT uuid FROM cargos WHERE slug = 'admin_plataforma');

INSERT INTO usuarios (
    uuid,
    nome_completo,
    cpf,
    email,
    senha,
    data_de_nascimento,
    cargo_uuid,
    taxa_associado_em_centavos,
    excluido,
    data_de_criacao
)
VALUES (
    UUID(),
    'Administração para Teste',
    '457.456.789-09',
    'admin@email.com',
    '$2y$12$2/iy1B0EiCXq1f1VtIs84eiVIF.vaqzYkh.wRDtg14/TGz46a8gjC', -- SENHA: admin
    '1980-01-01',
    @adminCargoUUID,
    0,
    0,
    NOW()
);