FROM php:8.2-apache

# التأكد من تفعيل MPM واحد فقط (يمنع خطأ "More than one MPM loaded")
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true; \
    a2enmod mpm_prefork

# امتداد الاتصال بـ MySQL
RUN docker-php-ext-install pdo_mysql

# نسخ ملفات المشروع
COPY . /var/www/html/

# مجلد الرفع قابل للكتابة من Apache
RUN chown -R www-data:www-data /var/www/html/uploads || true

# سكربت التشغيل (يضبط المنفذ حسب Railway)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENV PORT=80
EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
