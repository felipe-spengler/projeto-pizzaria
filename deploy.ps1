# Script de deploy otimizado para pizzaria (PowerShell)
# Usa cache do Docker para acelerar builds

param(
    [switch]$Quick,
    [switch]$Changed,
    [switch]$NoCache
)

$ErrorActionPreference = "Stop"

Write-Host "🚀 Iniciando deploy otimizado..." -ForegroundColor Cyan

# Verifica se está no diretório correto
if (-not (Test-Path "docker-compose.yml")) {
    Write-Host "❌ Erro: docker-compose.yml não encontrado. Execute este script na raiz do projeto." -ForegroundColor Red
    exit 1
}

# Opção 1: Deploy rápido (só reconstrói se necessário)
if ($Quick) {
    Write-Host "⚡ Modo rápido: usando cache do Docker" -ForegroundColor Yellow
    docker compose build --parallel
    docker compose up -d
    Write-Host "✅ Deploy rápido concluído!" -ForegroundColor Green
    exit 0
}

# Opção 2: Deploy apenas dos serviços que mudaram
if ($Changed) {
    Write-Host "🔄 Deploy incremental: apenas serviços alterados" -ForegroundColor Yellow
    
    # Detecta arquivos alterados (requer Git)
    try {
        $changedFiles = git diff --name-only HEAD~1 HEAD 2>$null
        if ($changedFiles) {
            Write-Host "📝 Arquivos alterados detectados:" -ForegroundColor Cyan
            $changedFiles | Select-Object -First 10 | ForEach-Object { Write-Host "   $_" }
            
            # Se mudou Dockerfile ou docker-compose.yml, reconstrói tudo
            $needsRebuild = $changedFiles | Where-Object { $_ -match "(Dockerfile|docker-compose\.yml|composer\.json|composer\.lock)" }
            
            if ($needsRebuild) {
                Write-Host "🔨 Mudanças em arquivos de build detectadas. Reconstruindo..." -ForegroundColor Yellow
                docker compose build --parallel
            } else {
                Write-Host "⚡ Apenas código PHP alterado. Deploy rápido..." -ForegroundColor Green
                docker compose up -d --no-build
            }
        } else {
            Write-Host "⚠️  Nenhuma mudança detectada. Fazendo deploy completo..." -ForegroundColor Yellow
            docker compose build --parallel
        }
    } catch {
        Write-Host "⚠️  Git não disponível ou erro ao detectar mudanças. Fazendo deploy completo..." -ForegroundColor Yellow
        docker compose build --parallel
    }
    
    docker compose up -d
    Write-Host "✅ Deploy incremental concluído!" -ForegroundColor Green
    exit 0
}

# Opção 3: Deploy completo (padrão)
Write-Host "🏗️  Deploy completo com cache otimizado" -ForegroundColor Yellow

if ($NoCache) {
    Write-Host "⚠️  Construindo sem cache (mais lento, mas garante limpeza total)" -ForegroundColor Yellow
    docker compose build --no-cache --parallel
} else {
    Write-Host "⚡ Construindo com cache (muito mais rápido)" -ForegroundColor Green
    docker compose build --parallel
}

# Reinicia os containers
docker compose up -d

Write-Host "✅ Deploy concluído com sucesso!" -ForegroundColor Green
Write-Host ""
Write-Host "📊 Status dos containers:" -ForegroundColor Cyan
docker compose ps

Write-Host ""
Write-Host "💡 Dicas para próximos deploys:" -ForegroundColor Yellow
Write-Host "   - Use './deploy.ps1 -Quick' para deploy rápido"
Write-Host "   - Use './deploy.ps1 -Changed' para deploy apenas do que mudou"
Write-Host "   - Use './deploy.ps1 -NoCache' para rebuild completo sem cache"

