# 🧩 Integração com Subadquirentes - Laravel

## 📝 Resumo

Aplicação Laravel para integração com subadquirentes de pagamento, permitindo processamento assíncrono de PIX e saques. O sistema suporta múltiplas subadquirentes (SubadqA e SubadqB) e foi desenvolvido com arquitetura extensível para facilitar a integração de novas subadquirentes no futuro.

## 🚀 Requisitos

- Docker
- Docker Compose

## 📦 Como Rodar

1. Clone o repositório:
```bash
git clone git@github.com:Chris7T/integracao-subadq.git
cd integracao-subadq
```

2. Copie o arquivo `.env.example` para `.env`:
```bash
cp .env.example .env
```

3. Inicie os containers:
```bash
docker-compose up -d
```

4. Instale as dependências:
```bash
docker-compose exec app composer install
```

5. Corrija as permissões dos diretórios de storage:
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

6. Gere a chave da aplicação:
```bash
docker-compose exec app php artisan key:generate
```

7. Execute as migrações:
```bash
docker-compose exec app php artisan migrate
```

8. Execute os seeders:
```bash
docker-compose exec app php artisan db:seed
```

9. Gerar a documentação:
```bash
docker-compose exec app php artisan l5-swagger:generate
```

A aplicação estará disponível em `http://localhost:8080`

Acesse a documentação em `http://localhost:8080/api/documentation`

10. Execute os testes:
```bash
docker-compose exec app php artisan test
```

## 🏗️ Estratégias Adotadas

### Strategy Pattern

Foi utilizado o **Strategy Pattern** para criar um contrato entre as classes através da interface `SubacquirerInterface`, definindo os métodos comuns que todas as subadquirentes devem implementar.

### Factory Pattern

Foi usado a **Factory Pattern** (`SubacquirerFactory`) para definir qual instância da classe de subadquirente será usada naquele momento, baseado no ID da subadquirente.

### Service Layer

Foi criada uma camada de **Service** para isolar a lógica de negócio dos controllers.

### Processamento Assíncrono

Utilização de **Laravel Queues** com **Redis** para processar requisições de forma assíncrona, suportando múltiplas requisições por segundo.

## 📚 Tecnologias Utilizadas

- Laravel 12
- PHP 8.2
- MySQL 8.0
- Redis 7
- Docker & Docker Compose
- Nginx

## ⚠️ Observações

**Para mockar os subadquirentes**, basta configurar no `.env`:
- `MOCK_SUBADQUIRER=true` - Usa dados mockados (não faz requisições HTTP reais)
- `MOCK_SUBADQUIRER=false` - Faz requisições HTTP reais para as APIs
