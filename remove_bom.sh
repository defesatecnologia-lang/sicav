cd ~/sistema-visitantes-v1.0
for f in inc/footer.php api/session_check.php public/index.php scripts/login.php scripts/editarUsuario.php scripts/logout.php; do
    sed -i '1s/^\xEF\xBB\xBF//' "$f" && echo "Fixed: $f"
done
