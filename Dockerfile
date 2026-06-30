FROM php:8.2-cli

# امتداد الاتصال بـ MySQL
RUN docker-php-ext-install pdo_mysql

WORKDIR /var/www/html
COPY . /var/www/html/

# مجلد الرفع قابل للكتابة
RUN chmod -R 0777 uploads || true

# عدد عمّال السيرفر المدمج (لأداء أفضل مع أكتر من مستخدم)
ENV PHP_CLI_SERVER_WORKERS=4
ENV PORT=80
EXPOSE 80

# سيرفر PHP المدمج — لا توجد به مشكلة MPM إطلاقاً
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-80} -t /var/www/html"]
