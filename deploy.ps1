param(
    [string] $Core = '',
    [switch] $FilesOnly,
    [switch] $Tests
)

$ErrorActionPreference = 'Stop'

# Déploiement rapide du module dans un Coeur de l'application, en développement.
#
#   .\deploy.ps1                 copie le module dans ..\xnova-2026.2, applique ses
#                                migrations et sème le registre des modules
#   .\deploy.ps1 -Core D:\jeu    autre dossier de Coeur
#   .\deploy.ps1 -FilesOnly      copie seulement, sans toucher à la base
#   .\deploy.ps1 -Tests          lance en plus les tests du module dans le conteneur
#
# Pourquoi un script plutôt que la page d'administration des modules : le Coeur monte ses
# sources en volume (`./:/var/www/html` dans son `docker-compose.yml`), donc **copier les
# fichiers suffit** — ils sont déjà dans le conteneur, sans reconstruction d'image. Il ne
# reste que les deux gestes que la page faisait à la main : appliquer les migrations du
# module (le migrateur lit `modules/<nom>/db/migrations/`) et semer le registre des modules,
# que le jeu interroge à chaque point de surcharge. Le module naît **allumé** : rien à cocher
# dans l'interface.

# Le Coeur voisin par défaut : `..\..\xnova-2026.2`, à côté du dossier des modules.
if ($Core -eq '') {
    $Core = Join-Path (Split-Path (Split-Path $PSScriptRoot -Parent) -Parent) 'xnova-2026.2'
}

$name = Split-Path $PSScriptRoot -Leaf

# Le manifeste dit le nom du module, et le jeu exige qu'il soit celui du dossier : c'est par
# lui que le registre, le routeur et la surcharge de classes le retrouvent.
$manifestPath = Join-Path $PSScriptRoot 'package.json'
if (-not (Test-Path $manifestPath)) {
    throw "Manifeste introuvable : $manifestPath"
}

$manifest = Get-Content $manifestPath -Raw | ConvertFrom-Json
if ($manifest.name -ne $name) {
    throw "Le manifeste declare '$($manifest.name)' et le dossier s'appelle '$name' : le jeu veut les deux identiques."
}

if (-not (Test-Path (Join-Path $Core 'app/bootstrap.php'))) {
    throw "Ce n'est pas un Coeur de l'application : $Core (app/bootstrap.php introuvable). Utiliser -Core."
}

# 1. Les fichiers. Le dossier cible est efface d'abord : un fichier retire du module ne doit
#    pas survivre dans le jeu, sinon on eprouve un melange des deux versions.
$cible = Join-Path $Core "modules/$name"
$exclus = @('.git', '.github', '.gitignore', 'README.md', 'deploy.ps1')

Remove-Item $cible -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force -Path $cible | Out-Null

$entrees = 0
Get-ChildItem -Path $PSScriptRoot -Force |
    Where-Object { $exclus -notcontains $_.Name } |
    ForEach-Object {
        Copy-Item -Path $_.FullName -Destination $cible -Recurse -Force
        $entrees++
    }

Write-Host "Fichiers : $entrees entrees copiees dans $cible"

if ($FilesOnly) {
    Write-Host 'Base intouchee (-FilesOnly) : le registre des modules et les migrations attendent.'
    return
}

# 2. La base. Tout se fait depuis le dossier du Coeur : c'est la que Docker Compose trouve
#    son fichier et son projet.
Push-Location $Core
try {
    $services = ((& docker compose ps --status running --format '{{.Service}}') 2>$null) -join ','
    if ($services -notmatch 'app') {
        Write-Host "La pile ne tourne pas dans $Core : lance son .\deploy.ps1 (ou docker compose up -d), puis relance ce script pour la base."
        return
    }

    $configPath = Join-Path $Core 'configs/config.php'
    if (-not (Test-Path $configPath) -or (Get-Item $configPath).Length -eq 0) {
        Write-Host "Le Coeur n'est pas installe : ouvre http://localhost:8080/ et passe l'installateur, puis relance ce script."
        return
    }

    # Les migrations du module : le migrateur lit `modules/<nom>/db/migrations/`, et il
    # tolere le rejeu (une migration deja appliquee n'est pas rejouee, une colonne deja
    # presente est ignoree).
    & docker compose exec -T app php db/migrate.php migrate
    if ($LASTEXITCODE -ne 0) {
        throw 'Les migrations ont echoue.'
    }

    # Le registre : c'est lui que le jeu lit pour savoir quel module repond a un point de
    # surcharge. Sans ce geste, le module est sur le disque mais absent du jeu.
    & docker compose exec -T app php db/modules.php
    if ($LASTEXITCODE -ne 0) {
        throw 'Le semis du registre des modules a echoue.'
    }

    if ($Tests) {
        & docker compose exec -T app php ./vendor/bin/phpunit --no-coverage "modules/$name"
        if ($LASTEXITCODE -ne 0) {
            throw 'Les tests du module ont echoue.'
        }
    }
} finally {
    Pop-Location
}

Write-Host "En jeu : http://localhost:8080/game/alliance"
