#!/usr/bin/env sh
set -eu

TARGET="/usr/share/nginx/html/env.js"

# Generate runtime env.js (frontend reads window.__ENV__)
# Add vars here as needed (keep secrets OUT of frontend env).
cat > "$TARGET" <<EOF
(function () {
  window.__ENV__ = window.__ENV__ || {};
  window.__ENV__.VITE_API_BASE_URL = "${VITE_API_BASE_URL:-}";
})();
EOF

exec nginx -g 'daemon off;'

