# Reverse proxy baseado na imagem oficial do nginx, fixada por digest.
#
# O "apk upgrade" aplica as correcoes de seguranca publicadas no Alpine depois
# do build oficial da imagem (ex.: libxslt 1.1.43-r3 -> 1.1.43-r4). Sem isso o
# Trivy acusa a versao antiga mesmo com o digest mais recente da tag.
#
# Para atualizar o digest: ./scripts/pin-images.sh --apply
ARG NGINX_IMAGE=nginx:stable-alpine@sha256:8eb2b5540eae779603cc3db84b3986a15ecb5308b54303146eee9837c137b51a

FROM ${NGINX_IMAGE}

# hadolint ignore=DL3018
RUN apk upgrade --no-cache
