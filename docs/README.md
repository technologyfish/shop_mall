# 技术文档目录

## 文档列表

| 文档 | 说明 |
|------|------|
| [DEPLOYMENT.md](./DEPLOYMENT.md) | 部署指南（环境配置、服务器部署、检查清单） |
| [STRIPE_PAYMENT.md](./STRIPE_PAYMENT.md) | Stripe 支付与订阅（一次性支付、订阅、Webhook） |
| [EMAIL_SERVICE.md](./EMAIL_SERVICE.md) | 邮件服务（SMTP 配置、各服务商配置、模板） |

---

## 快速开始

### 1. 本地开发

**后端**：
```bash
cd backend
composer install
cp .env.example .env
# 配置 .env
php -S localhost:8000 -t public
```

**前端**：
```bash
cd frontend
npm install
# 配置 .env
npm run dev
```

### 2. 生产部署

参考 [DEPLOYMENT.md](./DEPLOYMENT.md)

---

## 技术栈

### 后端
- PHP 8.0+ / Lumen
- MySQL 5.7+
- Stripe PHP SDK

### 前端
- Vue 3 + Vite
- Element Plus
- Pinia

---

## 关键配置项

### 必须配置

| 配置项 | 说明 | 位置 |
|--------|------|------|
| `DB_*` | 数据库连接 | 后端 .env |
| `JWT_SECRET` | JWT 密钥 | 后端 .env |
| `STRIPE_SECRET_KEY` | Stripe 密钥 | 后端 .env |
| `STRIPE_WEBHOOK_SECRET` | Webhook 密钥 | 后端 .env |
| `MAIL_*` | 邮件服务 | 后端 .env |
| `VITE_API_BASE_URL` | API 地址 | 前端 .env |

### Stripe 后台配置

1. 创建产品和价格（订阅计划）
2. 配置 Webhook endpoint
3. 复制 Price ID 到后台订阅计划管理

---

## 联系支持

如有问题，请查看各文档的"常见问题"部分。



