FROM node:22-alpine AS build
WORKDIR /app

# `npm ci` runs lifecycle scripts (including `postinstall`), so we must copy any
# referenced scripts before installing dependencies.
COPY frontend/package.json frontend/package-lock.json ./
COPY frontend/scripts/ ./scripts/
RUN npm ci --no-audit --no-fund

COPY frontend/ ./
RUN npm run build


FROM nginx:1.27-alpine AS runtime

# Pull in latest security patches available for this Alpine release
RUN apk upgrade --no-cache

COPY --from=build /app/dist/ /usr/share/nginx/html/
COPY docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/frontend/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh \
  && chown -R nginx:nginx /usr/share/nginx/html

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]

