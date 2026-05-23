# 🐳 Guia do Backend

🥑 [Documentação do Banco de Dados🔗](../docs/db/README.md)

🥑 [Documentação da API REST🔗](../docs/api/README.md)

## 💾 Setup Local Híbrido

> Parte do setup com containers (banco MySQL e PhpMyAdmin) e API REST rodando localmente 

Observei que um setup híbrido docker e código local é mais performático no Windows 11/10, por conta da emulação do WSL utilizada pelo Docker Desktop. 

A lógica é que quanto menos coisas pra ele emular, mais rápido o retorno das requisições.

O foco do tutorial é para Windows 10/11.

### Requisitos

- 🐋 Docker Desktop
- 🐘 PHP

### Passo 1 -  Configurando Docker

Atualmente utilizamos apenas dois containers: do MySQL e do PhpMyAdmin. 

Porém, seguiremos o processo descrito no `README.md` geral do projeto que vai criar todas as imagens na sua máquina pois atualmente não criamos um dockerfile só com as duas relevantes. Lá, temos:

```bash
# Se já rodou no README.md geral, ignore! Se não, rode e espere finalizar:
./setup.sh
```
Não esqueça de deixar o Docker Desktop aberto rodando, ele será necessário para executar os containers.

1. Remover containers com configuração base

```bash
docker rm -f hortas_mysql hortas_phpmyadmin
```

2. Criar uma network para os dois containers conversarem

```bash
docker network create hortas_network
```
3. Subir os containers relevantes com as configurações de variáveis relevantes:

```bash
docker run -d --name hortas_mysql --network hortas_network -e MYSQL_ROOT_PASSWORD=root_password -e MYSQL_DATABASE=railway -p 3306:3306 mysql:8.0
docker run -d --name hortas_phpmyadmin --network hortas_network -e PMA_HOST=hortas_mysql -e PMA_PORT=3306 -e PMA_USER=root -e PMA_PASSWORD=root_password -p 8080:80 phpmyadmin/phpmyadmin
```

4. Atualizar o `.env` para este conteúdo:

```bash
# Environment
APP_ENV=development
APP_DEBUG=true

# Database
DB_HOST=127.0.0.1
DB_NAME=railway
DB_USER=root
DB_PASS=root_password
DB_CHARSET=utf8mb4

# JWT
JWT_SECRET=hortas_dev

# API
API_VERSION=v1

```
4. Acessar as URLs dos Serviços:

- **Backend API**: http://localhost:8181
- **phpMyAdmin**: http://localhost:8080
- **URL base da API REST:** http://localhost:8000/api/v1/

É possível que você encontre erros ao tentar mandar requisições nessa etapa. Isso acontece porque o banco de dados não está criado e não há tabelas dentro dele.

### Passo 2 - Criando o banco de dados MySQL + uso da API

1. Rodar os scripts `.sql` na pasta `backend/src/Utils/SQL` na ordem enumerada, um por vez, na aba "SQL" do PhpMyAdmin. Isto criará o banco e seus dados seed. 
    **Dica:** Se ao executar o script 0 não aparecer nada, atualize a coluna lateral de bancos de dados no botão de setinha circular

2. Entrar na pasta backend e rodar o comando `php -S localhost:8000 -t public public/index.php `. Isto inicia a API REST do projeto

3. Baixar e exportar os templates da API REST para o Postman disponíveis [aqui 🔗](../postman)

4. Utilizar a API REST conforme documentado na [documentação da API🔗](../docs/api/README.md)

---

## 💾 Setup Local

\#TODO - Não utilizamos todos os containers ao mesmo tempo para agilizar e melhorar performance.

---

## 💾 Setup sem Docker

Para rodar localmente sem Docker, é preciso estar instalado:
- PHP
- PHP Composer
- MySQL
- NodeJS e NPM
- HeidiSQL (opcional)

### Rodando o backend

1. Execute os seguintes comandos no diretório raiz para instalar as dependências:

```bash
cd backend
compose install
```

2. Duplique a .env.example, renomeie para .env e cole os conteúdos do outro setup acima.  

3. Crie o banco pelo Heidi ou pela linha de comando.  

4. Rode a criação das tabelas e dos dados iniciais

```bash
php run-migrations.php
php run-seeds.php
```

5. Inicie o backend.

```bash
php -S localhost:8181 -t public public/index.php
```

---

## 🐋 Comandos úteis do Docker 

Limpar containers e imagens, no terminal do Windows (Powershell).

```bash
# Parar todos os containers
docker ps -aq | ForEach-Object { docker stop $_ }

# Remover todos os containers
docker ps -aq | ForEach-Object { docker rm $_ }

# Remover todas as imagens
docker images -q | ForEach-Object { docker rmi -f $_ }
```