# INSTRUÇÕES PARA ATUALIZAR O BANCO DE DADOS

## Passo 1: Execute o SQL para adicionar a coluna change_for

Execute este comando no seu MySQL:

```sql
ALTER TABLE orders ADD COLUMN change_for DECIMAL(10,2) DEFAULT NULL;
```

OU importe o arquivo:
- `add_change_for_column.sql`

## Passo 2: (OPCIONAL) Atualizar estrutura de endereços

Se você quiser usar endereços com campos separados (street, number, etc), execute:
- `update_addresses_structure.sql`

Isso vai converter a tabela `addresses` para usar campos separados ao invés de um campo de texto único.

## Notas:
- O arquivo `database.sql` já está atualizado com todas as colunas necessárias para novas instalações
- Estes arquivos SQL são apenas para BANCOS EXISTENTES que precisam ser atualizados
