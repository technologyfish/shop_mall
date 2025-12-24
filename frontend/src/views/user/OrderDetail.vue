<template>
  <div class="order-detail-page">
    <div class="container" v-if="order">
      <!-- Mobile back button -->
      <el-button class="back-btn-mobile" @click="goBack" type="primary" plain>
        <el-icon><ArrowLeft /></el-icon>
        Back to Orders
      </el-button>
      
      <h1 class="page-title">Order Details</h1>

      <div class="order-info card">
        <div class="info-row">
          <span class="label">Order No.:</span>
          <span>{{ order.order_no }}</span>
        </div>
        <div class="info-row">
          <span class="label">Status:</span>
          <el-tag :type="getStatusType(order.status)">{{ getStatusText(order.status) }}</el-tag>
          <span v-if="order.status === 0" class="countdown-text">
            <el-icon><Timer /></el-icon>
            {{ countdown === 'Expired' ? 'Expired' : countdown }}
          </span>
        </div>
        <div class="info-row">
          <span class="label">Order Time:</span>
          <span>{{ order.created_at }}</span>
        </div>
        <div class="info-row" v-if="order.pay_time">
          <span class="label">Paid At:</span>
          <span>{{ order.pay_time }}</span>
        </div>
      </div>

      <div class="shipping-info card">
        <h3 class="page-title">Shipping Information</h3>
        <div class="info-row">
          <span class="label">Recipient:</span>
          <span>{{ order.shipping_name }}</span>
        </div>
        <div class="info-row">
          <span class="label">Phone:</span>
          <span>{{ order.shipping_phone }}</span>
        </div>
        <div class="info-row">
          <span class="label">Address:</span>
          <span>{{ order.shipping_address }}</span>
        </div>
        
        <!-- Tracking Info -->
        <div v-if="order.status === 3 && (order.shipping_company || order.shipping_no)" class="shipping-tracking">
          <div class="divider"></div>
          <div class="info-row">
            <span class="label">Carrier:</span>
            <span>{{ order.shipping_company || 'N/A' }}</span>
          </div>
          <div class="info-row">
            <span class="label">Tracking No.:</span>
            <span>{{ order.shipping_no || 'N/A' }}</span>
          </div>
          <div class="info-row" v-if="order.shipped_at">
            <span class="label">Shipped At:</span>
            <span>{{ formatDate(order.shipped_at) }}</span>
          </div>
        </div>
      </div>

      <div class="items-info card">
        <h3 class="page-title">Products</h3>
        <!-- PC Table -->
        <el-table :data="order.items" class="pc-table">
          <el-table-column label="Product">
            <template #default="{ row }">
              <div class="product-info">
                <img :src="getProductImage(row)" />
                <span>{{ row.product_name }}</span>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="price" label="Price" width="120">
            <template #default="{ row }">£{{ row.price }}</template>
          </el-table-column>
          <el-table-column prop="quantity" label="Qty" width="100" />
          <el-table-column label="Subtotal" width="120">
            <template #default="{ row }">£{{ row.total_amount }}</template>
          </el-table-column>
        </el-table>

        <!-- Mobile Cards -->
        <div class="mobile-items">
          <div v-for="item in order.items" :key="item.id" class="item-card">
            <img :src="getProductImage(item)" :alt="item.product_name" class="item-image" />
            <div class="item-details">
              <h4 class="item-name">{{ item.product_name }}</h4>
              <div class="item-info-row">
                <span class="label">Price:</span>
                <span class="value">£{{ item.price }}</span>
              </div>
              <div class="item-info-row">
                <span class="label">Qty:</span>
                <span class="value">{{ item.quantity }}</span>
              </div>
              <div class="item-info-row total">
                <span class="label">Subtotal:</span>
                <span class="value">£{{ item.total_amount }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="order-total">
          <div class="total-row">
            <span>Subtotal:</span>
            <span>£{{ order.total_amount }}</span>
          </div>
          <div class="total-row" v-if="order.shipping_fee > 0">
            <span>Shipping:</span>
            <span>£{{ order.shipping_fee }}</span>
          </div>
          <div class="total-row discount" v-if="order.discount_amount > 0">
            <span>{{ order.promotion_id === 1 ? 'First Order Discount:' : 'Discount:' }}</span>
            <span class="discount-amount">-£{{ order.discount_amount }}</span>
          </div>
          <div class="total-row final">
            <span>Total:</span>
            <span class="amount">£{{ order.pay_amount }}</span>
          </div>
        </div>
      </div>

      <div class="actions" v-if="order.status === 0">
        <el-button type="primary" size="large">Pay Now</el-button>
        <el-button size="large" @click="handleCancel">Cancel Order</el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessageBox } from 'element-plus'
import { ArrowLeft, Timer } from '@element-plus/icons-vue'
import message from '@/utils/message'
import { getOrder, cancelOrder } from '@/api/order'
import {getProductImage} from "@/utils/image";

const route = useRoute()
const router = useRouter()
const order = ref(null)
const countdown = ref('')
let timer = null

const ORDER_TIMEOUT_MS = 60000 // 1分钟超时（测试用），正式环境改为 3600000（1小时）

const updateCountdown = () => {
  if (!order.value || order.value.status !== 0) {
    countdown.value = ''
    return
  }
  
  // 解析时间，处理多种格式
  let createdTime = order.value.created_at
  if (typeof createdTime === 'string') {
    // 处理 "2025-12-19 17:26:00" 格式
    createdTime = createdTime.replace(' ', 'T')
  }
  const created = new Date(createdTime).getTime()
  const now = Date.now()
  const diff = created + ORDER_TIMEOUT_MS - now

  if (diff <= 0) {
    countdown.value = 'Expired'
  } else {
    const minutes = Math.floor(diff / (1000 * 60))
    const seconds = Math.floor((diff % (1000 * 60)) / 1000)
    countdown.value = `${minutes}m ${seconds}s`
  }
}

onMounted(async () => {
  const res = await getOrder(route.params.id)
  order.value = res.data.data
  
  // 启动倒计时
  updateCountdown()
  timer = setInterval(updateCountdown, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

const goBack = () => {
  router.push('/user-center/orders')
}

const getStatusType = (status) => {
  const types = { 
    0: 'warning',  // Pending
    1: 'primary',  // Processing
    3: 'success',  // Completed
    4: 'info'      // Cancelled
  }
  return types[status] || 'info'
}

const getStatusText = (status) => {
  const texts = { 
    0: 'Pending', 
    1: 'Processing', 
    3: 'Completed', 
    4: 'Cancelled' 
  }
  return texts[status] || 'Unknown'
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${year}/${month}/${day} ${hours}:${minutes}`
}



const handleCancel = async () => {
  try {
    await ElMessageBox.confirm('Are you sure you want to cancel this order?', 'Confirm', {
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      type: 'warning'
    })
    await cancelOrder(order.value.id)
    message.success('Order cancelled')
    router.push('/user-center/orders')
  } catch (error) {
    if (error !== 'cancel') {
      message.error(error.message || 'Operation failed')
    }
  }
}
</script>

<style scoped lang="scss">
.order-detail-page {
  // 返回按钮默认隐藏
  .back-btn-mobile {
    display: none;
  }

  .info-row {
    margin-bottom: 15px;
    font-size: 16px;

    .label {
      color: #666;
      margin-right: 10px;
    }
    
    .countdown-text {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      margin-left: 15px;
      font-size: 14px;
      color: #e6a23c;
      font-weight: 500;
    }
  }

  .shipping-info,
  .items-info {
    margin-top: 20px;

    h3 {
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }
  }

  .product-info {
    display: flex;
    align-items: center;
    gap: 15px;

    img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 4px;
    }
  }

  .order-total {
    margin-top: 20px;
    text-align: right;

    .total-row {
      margin-bottom: 10px;
      font-size: 16px;
      
      &.discount {
        color: #67c23a;
        
        .discount-amount {
          font-weight: 500;
        }
      }

      &.final {
        font-size: 18px;
        font-weight: bold;

        .amount {
          color: #f56c6c;
          font-size: 24px;
        }
      }
    }
  }

  .actions {
    margin-top: 30px;
    text-align: center;

    .el-button {
      min-width: 150px;
    }
  }
}

// PC端隐藏移动端元素
.mobile-items {
  display: none;
}

// 移动端样式
@media (max-width: 768px) {
  .main-content{
    width: 100%;
  }
  .order-detail-page {
    .container{
      width: 100%;
      max-width: 100%;
    }
    // 显示返回按钮
    .back-btn-mobile {
      display: inline-flex;
      margin-bottom: 15px;
    }

    .page-title {
      font-size: 20px;
      margin-bottom: 20px;
    }

    .card {
      padding: 15px;

      h3 {
        font-size: 18px;
        margin-bottom: 15px;
      }
    }

    .info-row {
      font-size: 14px;
      margin-bottom: 12px;
    }

    .items-info {
      // 隐藏PC端表格
      .pc-table {
        display: none;
      }

      // 显示移动端卡片
      .mobile-items {
        display: block;

        .item-card {
          display: flex;
          gap: 15px;
          padding: 15px;
          background: #f9f9f9;
          border-radius: 8px;
          margin-bottom: 12px;

          &:last-child {
            margin-bottom: 0;
          }

          .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
          }

          .item-details {
            flex: 1;
            min-width: 0;

            .item-name {
              font-size: 16px;
              font-weight: 600;
              margin: 0 0 10px 0;
              color: #333;
              overflow: hidden;
              text-overflow: ellipsis;
              display: -webkit-box;
              -webkit-line-clamp: 2;
              -webkit-box-orient: vertical;
            }

            .item-info-row {
              display: flex;
              justify-content: space-between;
              font-size: 14px;
              margin-bottom: 6px;

              .label {
                color: #666;
              }

              .value {
                color: #333;
                font-weight: 500;
              }

              &.total {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px solid #e0e0e0;

                .value {
                  color: var(--primary-color);
                  font-size: 16px;
                  font-weight: 600;
                }
              }

              &:last-child {
                margin-bottom: 0;
              }
            }
          }
        }
      }
    }

    .order-total {
      margin-top: 15px;

      .total-row {
        font-size: 14px;
        margin-bottom: 8px;

        &.final {
          font-size: 16px;

          .amount {
            font-size: 20px;
          }
        }
      }
    }

    .product-info {
      img {
        width: 50px;
        height: 50px;
      }
    }

    .actions {
      .el-button {
        width: 100%;
        min-width: unset;
        margin-bottom: 10px;

        &:last-child {
          margin-bottom: 0;
        }
      }
    }
  }
}
</style>




