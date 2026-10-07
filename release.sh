#!/bin/sh
# Publie une version d'Aurox dans le dépôt public osd84/aurox-dist.
#
# usage : ./release.sh 1.0.0
#
# Le dépôt privé (celui-ci) garde tout l'historique. Le public ne reçoit
# qu'un commit par version, avec le contenu exact du tag, sans .git.
# Packagist détecte le tag et publie la version.
#
# Pré-requis, une seule fois :
#   - créer osd84/aurox-dist sur GitHub (public, vide)
#   - l'enregistrer sur packagist.org et activer le hook GitHub
set -e

V="$1"
PUB_REPO="git@github.com:osd84/aurox-dist.git"
PUB_DIR="$(dirname "$0")/../aurox-dist"

[ -n "$V" ] || { echo "usage : ./release.sh 1.0.0"; exit 1; }
echo "$V" | grep -qE '^[0-9]+\.[0-9]+\.[0-9]+$' || { echo "version attendue : X.Y.Z"; exit 1; }

cd "$(dirname "$0")"
[ "$(git rev-parse --abbrev-ref HEAD)" = "main" ] || { echo "se placer sur main"; exit 1; }
git diff --quiet && git diff --cached --quiet || { echo "commit d'abord"; exit 1; }
git rev-parse "$V" >/dev/null 2>&1 && { echo "le tag $V existe déjà"; exit 1; }

# 1. export de HEAD dans un dossier temporaire (git archive = fichiers suivis par git uniquement, pas de .git)
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
git archive HEAD | tar -x -C "$TMP"

# 2. contrôle avant tout tag ou push : rien de sensible dans l'export
BAD="$(find "$TMP" -type f \( -name 'conf.php' -o -name '.env' -o -name '.env.*' \
    -o -name '*.pem' -o -name '*.key' -o -name '*.p12' -o -name '*.log' \
    -o -name 'blacklist__*' -o -name 'banlog__*' \) -print)"
if [ -z "$BAD" ]; then
    BAD="$(grep -rlIE '(AKIA[0-9A-Z]{16}|sk_live_[0-9A-Za-z]{8}|ghp_[0-9A-Za-z]{30}|xox[bp]-[0-9]|AIza[0-9A-Za-z_-]{30}|discord(app)?\.com/api/webhooks/[0-9]+/[A-Za-z0-9_-]{20}|BEGIN [A-Z ]*PRIVATE KEY)' "$TMP" || true)"
fi
if [ -n "$BAD" ]; then
    echo "STOP : contenu sensible dans l'export, rien n'a été publié :"
    echo "$BAD" | sed "s#^$TMP/##"
    exit 1
fi

# 3. tag côté privé
git tag "$V"
git push origin "$V"

# 4. clone du public si absent, puis remplacement complet de son contenu
[ -d "$PUB_DIR/.git" ] || git clone "$PUB_REPO" "$PUB_DIR"
find "$PUB_DIR" -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf {} +
cp -a "$TMP/." "$PUB_DIR/"

# 5. un commit par version côté public
cd "$PUB_DIR"
git add -A
git commit -m "Aurox $V"
git tag "$V"
git push origin HEAD:main --tags

echo
echo "Aurox $V publié sur $PUB_REPO"
echo "Packagist : https://packagist.org/packages/osd84/aurox"
