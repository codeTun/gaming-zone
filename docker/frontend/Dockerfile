FROM nginx:alpine

# Copy custom nginx configuration
COPY docker/frontend/nginx.conf /etc/nginx/nginx.conf

# Copy static files
COPY pages /usr/share/nginx/html/pages
COPY assets /usr/share/nginx/html/assets

# Copy main index file if exists
COPY index.html /usr/share/nginx/html/ 

# Create a default index.html if it doesn't exist
RUN if [ ! -f /usr/share/nginx/html/index.html ]; then \
    echo '<!DOCTYPE html><html><head><title>Gaming Zone</title></head><body><h1>Welcome to Gaming Zone</h1><p><a href="/pages/login/login.html">Login</a> | <a href="/pages/createaccount/">Register</a></p></body></html>' > /usr/share/nginx/html/index.html; \
    fi

# Set permissions
RUN chown -R nginx:nginx /usr/share/nginx/html

# Expose port 80
EXPOSE 80

# Start nginx
CMD ["nginx", "-g", "daemon off;"]
