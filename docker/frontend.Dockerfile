FROM node:22-alpine AS build
WORKDIR /app

COPY frontend/package.json ./
RUN npm install

COPY frontend/ ./
RUN npm run build


FROM nginx:1.27-alpine AS runtime

COPY --from=build /app/dist/ /usr/share/nginx/html/
COPY docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/frontend/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh \
  && chown -R nginx:nginx /usr/share/nginx/html

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]

