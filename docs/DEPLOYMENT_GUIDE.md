# The Chilli Trail 商城部署文档

本文档详细说明如何将项目部署到阿里云服务器（宝塔面板）。

## 目录

1. [服务器环境准备](#1-服务器环境准备)
2. [后端部署](#2-后端部署)
3. [前端部署](#3-前端部署)
4. [Nginx 配置](#4-nginx-配置)
5. [Stripe Webhook 配置](#5-stripe-webhook-配置)
6. [常见问题](#6-常见问题)

---

## 1. 服务器环境准备

### 1.1 登录宝塔面板

访问：`https://47.99.205.234:6787`

### 1.2 安装必要软件

在宝塔面板「软件商店」中安装：

| 软件 | 版本要求 | 说明 |
|------|----------|------|
| Nginx | 1.20+ | Web 服务器 |
| PHP | 7.4 或 8.0+ | 后端运行环境 |
| MySQL | 5.7 或 8.0 | 数据库 |
| Node.js | 16+ | 前端构建 |
| Composer | 2.x | PHP 包管理器 |

### 1.3 PHP 扩展配置

在宝塔面板中，进入 `软件商店` → `PHP-7.4` → `设置` → `安装扩展`，确保以下扩展已安装：

- fileinfo
- redis（可选，用于缓存）
- opcache
- mbstring
- openssl
- pdo_mysql
- curl
- json
- zip

### 1.4 创建数据库

1. 进入 `数据库` → `添加数据库`
2. 数据库名：`chilli_shop`
3. 用户名：`chilli_shop`
4. 密码：自动生成或自定义（记住它）
5. 访问权限：本地服务器

---

## 2. 后端部署

### 2.1 上传代码

**方法一：通过宝塔文件管理器**

1. 将 `backend` 文件夹压缩为 zip
2. 在宝塔 `文件` 中上传到 `/www/wwwroot/`
3. 解压到 `/www/wwwroot/chilli-api/`

**方法二：通过 Git（推荐）**

```bash
cd /www/wwwroot/
git clone <你的仓库地址> chilli-shop
cd chilli-shop/backend
```

### 2.2 安装依赖

```bash
cd /www/wwwroot/chilli-api
composer install --no-dev --optimize-autoloader
```

### 2.3 配置环境变量

复制并编辑 `.env` 文件：

```bash
cp .env.example .env
nano .env
```

**重要配置项：**

```env
APP_NAME="The Chilli Trail"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com
APP_FRONTEND_URL=https://www.yourdomain.com

# 数据库配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chilli_shop
DB_USERNAME=chilli_shop
DB_PASSWORD=你的数据库密码

# JWT 密钥（生成随机字符串）
JWT_SECRET=生成一个32位随机字符串

# 邮件配置
MAIL_MODE=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="The Chilli Trail"

# Stripe 配置（生产环境使用真实密钥）
STRIPE_PUBLISHABLE_KEY=pk_live_xxxxxxxxxxxx
STRIPE_SECRET_KEY=sk_live_xxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxx

# 支付模式
PAYMENT_MODE=live
```

### 2.4 设置目录权限

```bash
cd /www/wwwroot/chilli-api
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R www:www storage
chown -R www:www bootstrap/cache
```

### 2.5 创建数据库表

```bash
# 如果使用 Lumen
php artisan migrate

# 或者手动导入 SQL 文件
mysql -u chilli_shop -p chilli_shop < database/migrations/init.sql
```

### 2.6 生成密钥

```bash
# 生成 JWT 密钥
php artisan jwt:secret
```

---

## 3. 前端部署

### 3.1 本地构建（推荐）

在本地开发机器上：

```bash
cd frontend

# 安装依赖
npm install

# 修改环境变量
# 创建 .env.production 文件
```

**.env.production 内容：**

```env
VITE_API_BASE_URL=https://api.yourdomain.com
VITE_STRIPE_PUBLISHABLE_KEY=pk_live_xxxxxxxxxxxx
```

```bash
# 构建生产版本
npm run build

# 将 dist 文件夹上传到服务器
```

### 3.2 上传前端文件

将构建好的 `dist` 文件夹内容上传到：

```
/www/wwwroot/chilli-web/
```

或者通过宝塔 `文件` 功能上传 zip 包后解压。

### 3.3 服务器上构建（可选）

如果服务器性能足够：

```bash
cd /www/wwwroot/chilli-shop/frontend
npm install
npm run build
# 构建产物在 dist/ 目录
```

---

## 4. Nginx 配置

### 4.1 创建后端 API 站点

在宝塔面板 `网站` → `添加站点`：

- 域名：`api.yourdomain.com`
- 根目录：`/www/wwwroot/chilli-api/public`
- PHP版本：PHP-7.4

**Nginx 配置（点击站点设置 → 配置文件）：**

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name api.yourdomain.com;
    
    root /www/wwwroot/chilli-api/public;
    index index.php index.html;
    
    # SSL 配置（宝塔自动生成）
    ssl_certificate /www/server/panel/vhost/cert/api.yourdomain.com/fullchain.pem;
    ssl_certificate_key /www/server/panel/vhost/cert/api.yourdomain.com/privkey.pem;
    
    # 强制 HTTPS
    if ($scheme = http) {
        return 301 https://$host$request_uri;
    }
    
    # CORS 配置
    add_header Access-Control-Allow-Origin * always;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Authorization, Content-Type, X-Requested-With" always;
    
    if ($request_method = OPTIONS) {
        return 204;
    }
    
    # PHP 处理
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-74.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # 禁止访问敏感文件
    location ~ /\.(env|git|htaccess) {
        deny all;
    }
    
    # 静态文件上传目录
    location /uploads {
        alias /www/wwwroot/chilli-api/public/uploads;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
    
    # 日志
    access_log /www/wwwlogs/api.yourdomain.com.access.log;
    error_log /www/wwwlogs/api.yourdomain.com.error.log;
}
```

### 4.2 创建前端站点

在宝塔面板 `网站` → `添加站点`：

- 域名：`www.yourdomain.com`
- 根目录：`/www/wwwroot/chilli-web`
- PHP版本：纯静态

**Nginx 配置：**

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name www.yourdomain.com yourdomain.com;
    
    root /www/wwwroot/chilli-web;
    index index.html;
    
    # SSL 配置
    ssl_certificate /www/server/panel/vhost/cert/www.yourdomain.com/fullchain.pem;
    ssl_certificate_key /www/server/panel/vhost/cert/www.yourdomain.com/privkey.pem;
    
    # 强制 HTTPS
    if ($scheme = http) {
        return 301 https://$host$request_uri;
    }
    
    # 裸域名跳转到 www
    if ($host = yourdomain.com) {
        return 301 https://www.yourdomain.com$request_uri;
    }
    
    # Vue Router 历史模式支持
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # 静态资源缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
    
    # Gzip 压缩
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
    gzip_min_length 1000;
    
    # 日志
    access_log /www/wwwlogs/www.yourdomain.com.access.log;
    error_log /www/wwwlogs/www.yourdomain.com.error.log;
}
```

### 4.3 申请 SSL 证书

在宝塔面板中：

1. 进入站点设置 → SSL
2. 选择 `Let's Encrypt`
3. 勾选域名，申请证书
4. 开启 `强制HTTPS`

---

## 5. Stripe Webhook 配置

### 5.1 在 Stripe Dashboard 配置 Webhook

1. 登录 [Stripe Dashboard](https://dashboard.stripe.com)
2. 进入 `Developers` → `Webhooks`
3. 点击 `Add endpoint`
4. 配置：
   - Endpoint URL: `https://api.yourdomain.com/api/stripe/webhook`
   - Events to send:
     - `checkout.session.completed`
     - `customer.subscription.created`
     - `customer.subscription.updated`
     - `customer.subscription.deleted`
     - `invoice.payment_succeeded`
     - `invoice.payment_failed`

5. 保存后，复制 `Signing secret`（以 `whsec_` 开头）
6. 将其填入后端 `.env` 文件的 `STRIPE_WEBHOOK_SECRET`

### 5.2 测试 Webhook

```bash
# 使用 Stripe CLI 测试
stripe listen --forward-to https://api.yourdomain.com/api/stripe/webhook
stripe trigger checkout.session.completed
```

---

## 6. 常见问题

### 6.1 502 Bad Gateway

**原因：** PHP 进程未启动或配置错误

**解决：**
```bash
# 检查 PHP 服务
systemctl status php-fpm-74

# 重启 PHP
systemctl restart php-fpm-74
```

### 6.2 500 Internal Server Error

**原因：** 代码错误或权限问题

**解决：**
```bash
# 查看错误日志
tail -f /www/wwwroot/chilli-api/storage/logs/lumen.log

# 检查权限
chmod -R 755 storage
chown -R www:www storage
```

### 6.3 CORS 跨域问题

**解决：** 确保 Nginx 配置中包含 CORS 头部，并检查 `.env` 中 `APP_FRONTEND_URL` 是否正确。

### 6.4 Stripe Webhook 返回 401

**原因：** Webhook secret 配置错误

**解决：**
1. 检查 `.env` 中 `STRIPE_WEBHOOK_SECRET` 是否正确
2. 确保使用生产环境的 webhook secret（不是测试环境的）

### 6.5 上传图片失败

**解决：**
```bash
# 创建上传目录
mkdir -p /www/wwwroot/chilli-api/public/uploads/images
chmod -R 777 /www/wwwroot/chilli-api/public/uploads
chown -R www:www /www/wwwroot/chilli-api/public/uploads
```

### 6.6 数据库连接失败

**解决：**
1. 检查 `.env` 中数据库配置
2. 确保数据库用户有正确权限
3. 检查 MySQL 是否运行：`systemctl status mysqld`

---

## 7. 部署检查清单

部署完成后，按以下清单逐项检查：

- [ ] 后端 API 可访问：`https://api.yourdomain.com/`
- [ ] 前端页面可访问：`https://www.yourdomain.com/`
- [ ] 用户注册/登录正常
- [ ] 商品列表正常显示
- [ ] 购物车功能正常
- [ ] Stripe 支付流程正常
- [ ] 订阅功能正常
- [ ] 邮件发送正常
- [ ] 后台管理可登录
- [ ] 图片上传正常

---

## 8. 维护命令

```bash
# 清除缓存
php artisan cache:clear
php artisan config:clear

# 查看日志
tail -f storage/logs/lumen.log

# 重启服务
systemctl restart nginx
systemctl restart php-fpm-74

# 数据库备份
mysqldump -u root -p chilli_shop > backup_$(date +%Y%m%d).sql
```

---

## 9. 联系支持

如遇到问题，请检查：
1. Nginx 错误日志：`/www/wwwlogs/`
2. PHP 错误日志：`storage/logs/lumen.log`
3. Stripe Dashboard 的 Webhook 日志

---

**文档版本：** v1.0  
**最后更新：** 2024-12-22
