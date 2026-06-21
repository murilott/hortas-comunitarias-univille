# Endpoints de Canteiristas

## Rotas disponíveis

```text
GET    /Canteiristas
GET    /Canteiristas/filtro
GET    /Canteiristas/estatisticas
GET    /Canteiristas/{uuid}
POST   /Canteiristas
PUT    /Canteiristas/{uuid}
PATCH  /Canteiristas/{uuid}/ativar
PATCH  /Canteiristas/{uuid}/desativar
DELETE /Canteiristas/{uuid}
```

## Filtros suportados em `GET /Canteiristas/filtro`

- `nome_completo`
- `cpf`
- `email`
- `telefone`
- `horta_uuid`
- `com_canteiros`
- `data_inicio`
- `data_fim`
- `ordenar_por`
- `ordem`
