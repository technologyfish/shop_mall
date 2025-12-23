# 部署指南

## 一、环境要求

### 服务器要求
- PHP >= 8.0
- MySQL >= 5.7 或 MariaDB >= 10.3
- Nginx 或 Apache
- Node.js >= 16 (仅构建前端用)
- Composer

### PHP 扩展
- OpenSSL
- PDO
- Mbstring
- JSON
- cURL
- fileinfo

---

## 二、后端部署

### 1. 上传代码
```bash
# 将 backend 目录上传到服务器
scp -r backend/ user@server:/var/www/shop-api/
```

### 2. 安装依赖
```bash
cd /var/www/shop-api
composer install --no-dev --optimize-autoloader
```

### 3. 配置环境变量
```bash
cp .env.example .env
vim .env
```

### 4. 生成密钥
```bash
php artisan key:generate   # Laravel
# 或手动设置 JWT_SECRET
```

### 5. 数据库迁移
```bash
php artisan migrate --force
```

### 6. 目录权限
```bash
chmod -R 775 storage
chmod -R 775 public/uploads
chown -R www-data:www-data storage public/uploads
```

### 7. Nginx 配置
```nginx
server {
    listen 80;
    server_name api.your-domain.com;
    root /var/www/shop-api/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 三、前端部署

### 1. 构建
```bash
cd frontend
npm install
npm run build
```

### 2. 上传 dist 目录
```bash
scp -r dist/ user@server:/var/www/shop-web/
```

### 3. Nginx 配置
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/shop-web;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        proxy_pass http://api.your-domain.com;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

---

## 四、环境配置模板

### 后端 `.env` 完整配置
```env
# ========== 基础配置 ==========
APP_NAME="Your Shop"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.your-domain.com
APP_KEY=base64:生成的随机密钥

# ========== 数据库配置 ==========
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# ========== JWT 配置 ==========
JWT_SECRET=生成一个64位随机字符串
JWT_TTL=10080

# ========== Stripe 支付配置 ==========
# 从 Stripe Dashboard -> Developers -> API keys 获取
# 正式环境使用 sk_live_ 和 pk_live_ 开头的密钥
STRIPE_SECRET_KEY=sk_live_xxxxxxxxxxxxxxxx
STRIPE_PUBLISHABLE_KEY=pk_live_xxxxxxxxxxxxxxxx

# Webhook 密钥（配置 Webhook 后获取）
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxx

# ========== 邮件配置 ==========
MAIL_DRIVER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.你的SendGrid_API_Key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="Your Shop"

# ========== 前端地址（用于邮件链接等）==========
APP_FRONTEND_URL=https://your-domain.com

# ========== 缓存和队列（可选）==========
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

# ========== 日志配置 ==========
LOG_CHANNEL=daily
LOG_LEVEL=error
```

### 前端 `.env.production`
```env
VITE_API_BASE_URL=https://api.your-domain.com
```

---

## 五、SSL 证书配置

推荐使用 Let's Encrypt 免费证书：

```bash
# 安装 certbot
apt install certbot python3-certbot-nginx

# 获取证书
certbot --nginx -d your-domain.com -d api.your-domain.com

# 自动续期
certbot renew --dry-run
```

---

## 六、部署检查清单

### 上线前必须完成

| 项目 | 状态 | 说明 |
|------|------|------|
| □ Stripe Live 密钥 | | 替换测试密钥为正式密钥 |
| □ Stripe Webhook | | 配置正式域名的 webhook |
| □ Stripe Price ID | | 后台订阅计划使用正式 Price ID |
| □ 邮件服务 | | 配置真实 SMTP 服务 |
| □ 数据库备份 | | 设置定时备份 |
| □ SSL 证书 | | 配置 HTTPS |
| □ 日志监控 | | 配置日志收集和报警 |
| □ APP_DEBUG=false | | 关闭调试模式 |

---

## 七、常见问题

### 500 错误
1. 检查日志：`tail -f storage/logs/lumen-*.log`
2. 检查目录权限
3. 检查 `.env` 配置

### 跨域问题
确保 `CorsMiddleware` 配置正确，或在 Nginx 中添加 CORS 头

### Webhook 不工作
1. 确认 Webhook URL 可公网访问
2. 检查 `STRIPE_WEBHOOK_SECRET` 配置
3. 查看 Stripe Dashboard 的 Webhook 日志

---

## 八、监控和维护

### 日志查看
```bash
tail -f /var/www/shop-api/storage/logs/lumen-$(date +%Y-%m-%d).log
```

### 清理缓存
```bash
php artisan cache:clear
php artisan config:clear
```

### 数据库备份
```bash
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql
```



