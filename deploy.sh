#!/bin/bash

# Script de deploy otimizado para pizzaria
# Usa cache do Docker para acelerar builds

set -e

echo "🚀 Iniciando deploy otimizado..."

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verifica se está no diretório correto
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ Erro: docker-compose.yml não encontrado. Execute este script na raiz do projeto."
    exit 1
fi

# Opção 1: Deploy rápido (só reconstrói se necessário)
if [ "$1" == "--quick" ] || [ "$1" == "-q" ]; then
    echo -e "${YELLOW}⚡ Modo rápido: usando cache do Docker${NC}"
    docker compose build --parallel
    docker compose up -d
    echo -e "${GREEN}✅ Deploy rápido concluído!${NC}"
    exit 0
fi

# Opção 2: Deploy apenas dos serviços que mudaram
if [ "$1" == "--changed" ] || [ "$1" == "-c" ]; then
    echo -e "${YELLOW}🔄 Deploy incremental: apenas serviços alterados${NC}"
    
    # Detecta arquivos alterados
    CHANGED_FILES=$(git diff --name-only HEAD~1 HEAD 2>/dev/null || echo "")
    
    if [ -z "$CHANGED_FILES" ]; then
        echo "⚠️  Nenhuma mudança detectada. Fazendo deploy completo..."
        docker compose build --parallel
    else
        echo "📝 Arquivos alterados detectados:"
        echo "$CHANGED_FILES" | head -10
        
        # Se mudou Dockerfile ou docker-compose.yml, reconstrói tudo
        if echo "$CHANGED_FILES" | grep -qE "(Dockerfile|docker-compose.yml|composer.json)"; then
            echo "🔨 Mudanças em arquivos de build detectadas. Reconstruindo..."
            docker compose build --parallel
        else
            echo "⚡ Apenas código PHP alterado. Deploy rápido..."
            # Para código PHP, se usar volume mount, não precisa rebuild
            docker compose up -d --no-build
        fi
    fi
    
    docker compose up -d
    echo -e "${GREEN}✅ Deploy incremental concluído!${NC}"
    exit 0
fi

# Opção 3: Deploy completo (padrão)
echo -e "${YELLOW}🏗️  Deploy completo com cache otimizado${NC}"

# Para produção, use --no-cache apenas se necessário
if [ "$1" == "--no-cache" ]; then
    echo "⚠️  Construindo sem cache (mais lento, mas garante limpeza total)"
    docker compose build --no-cache --parallel
else
    echo "⚡ Construindo com cache (muito mais rápido)"
    docker compose build --parallel
fi

# Reinicia os containers
docker compose up -d

echo -e "${GREEN}✅ Deploy concluído com sucesso!${NC}"
echo ""
echo "📊 Status dos containers:"
docker compose ps

echo ""
echo "💡 Dicas para próximos deploys:"
echo "   - Use './deploy.sh --quick' para deploy rápido"
echo "   - Use './deploy.sh --changed' para deploy apenas do que mudou"
echo "   - Use './deploy.sh --no-cache' para rebuild completo sem cache"

