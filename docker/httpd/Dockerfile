FROM httpd:2.4-alpine

# Copy custom Apache configuration
COPY httpd.conf /usr/local/apache2/conf/httpd.conf

# Enable mod_rewrite
RUN sed -i 's/#LoadModule rewrite_module/LoadModule rewrite_module/' /usr/local/apache2/conf/httpd.conf

# Create document root
RUN mkdir -p /var/www/html

EXPOSE 80 443
