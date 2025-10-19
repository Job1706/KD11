# ใช้ image PHP
FROM php:8.2-apache

# คัดลอกไฟล์ทั้งหมดเข้า container
COPY . /var/www/html

# ตั้ง working directory
WORKDIR /var/www/html

# เปิดพอร์ต 1000 สำหรับ Render
EXPOSE 1000

# คำสั่งรันเซิร์ฟเวอร์
CMD ["php", "-S", "0.0.0.0:10000", "-t", "/var/www/html"]
