<template>
  <div class="orders-page">
    <!-- 标题和Tab只在列表时显示 -->
    <div v-if="!currentOrder">
      <el-tabs v-model="activeTab" @tab-change="handleTabChange" class="order-tabs">
        <el-tab-pane label="All Orders" name="all" />
        <el-tab-pane label="Pending" name="0" />
        <el-tab-pane label="Processing" name="1" />
        <el-tab-pane label="Completed" name="3" />
        <el-tab-pane label="Cancelled" name="4" />
      </el-tabs>
    </div>

    <!-- 订单列表 -->
    <div v-if="!currentOrder" class="orders-table">
      <!-- PC端表格 -->
      <table>
        <thead>
          <tr>
            <th>Order No.</th>
            <th>Order Time</th>
            <th>Status</th>
            <th>Amount</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in orders" :key="order.id" class="order-row">
            <td>{{ order.order_no }}</td>
            <td>{{ formatDate(order.created_at) }}</td>
            <td>
              <el-tag :type="getStatusType(order.status)" size="small">
                {{ getStatusText(order.status) }}
              </el-tag>
            </td>
            <td>£{{ order.pay_amount }}</td>
            <td>
              <el-button link type="primary" @click="showDetail(order.id)">Details</el-button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- 移动端卡片 -->
      <div class="order-cards">
        <div v-for="order in orders" :key="order.id" class="order-card" @click="showDetail(order.id)">
          <div class="card-header">
            <div class="order-no">Order: {{ order.order_no }}</div>
            <el-tag :type="getStatusType(order.status)" size="small">
              {{ getStatusText(order.status) }}
            </el-tag>
          </div>
          <div class="card-body">
            <div class="order-info-row">
              <span class="label">Order Time:</span>
              <span class="value">{{ formatDate(order.created_at) }}</span>
            </div>
            <div class="order-info-row">
              <span class="label">Amount:</span>
              <span class="value amount">£{{ order.pay_amount }}</span>
            </div>
          </div>
          <div class="card-footer">
            <el-button type="primary" size="small" @click.stop="showDetail(order.id)">
              View Details
            </el-button>
          </div>
        </div>
      </div>

      <el-empty v-if="!orders.length" description="No orders yet" />

      <div class="pagination" v-if="total > 0">
        <el-pagination
          v-model:current-page="currentPage"
          :total="total"
          :page-size="15"
          layout="total, prev, pager, next"
          @current-change="fetchOrders"
        />
      </div>
    </div>

    <!-- Order Detail (inline view for PC) -->
    <div v-else class="order-detail-view pc-only">
      <el-button class="back-btn" @click="currentOrder = null" type="primary" plain>
        <el-icon><ArrowLeft /></el-icon>
        Back to Orders
      </el-button>

      <div class="order-detail">
        <!-- Order Info -->
        <div class="detail-section">
          <h3>Order Information</h3>
          <el-descriptions :column="2" border size="small">
            <el-descriptions-item label="Order No.">{{ currentOrder.order_no }}</el-descriptions-item>
            <el-descriptions-item label="Order Time">{{ formatDate(currentOrder.created_at) }}</el-descriptions-item>
            <el-descriptions-item label="Status">
              <el-tag :type="getStatusType(currentOrder.status)" size="small">
                {{ getStatusText(currentOrder.status) }}
              </el-tag>
              <div v-if="currentOrder.status === 0 && getCountdown(currentOrder.created_at) !== 'Expired'" class="countdown-inline">
                <el-icon><Timer /></el-icon>
                {{ getCountdown(currentOrder.created_at) }}
              </div>
            </el-descriptions-item>
            <el-descriptions-item label="Paid At">{{ currentOrder.paid_at ? formatDate(currentOrder.paid_at) : '-' }}</el-descriptions-item>
          </el-descriptions>
        </div>

        <!-- Products -->
        <div class="detail-section">
          <h3>Products</h3>
          <div v-for="item in currentOrder.items" :key="item.id" class="product-item">
            <img :src="getProductImage(item)" :alt="item.product_name" />
            <div class="product-info">
              <p class="name">{{ item.product_name }}</p>
              <p class="spec">£{{ item.price }} x {{ item.quantity }}</p>
            </div>
            <div class="product-total">£{{ (item.price * item.quantity).toFixed(2) }}</div>
          </div>
        </div>

        <!-- Shipping Info -->
        <div class="detail-section">
          <h3>Shipping Information</h3>
          <el-descriptions :column="1" border size="small">
            <el-descriptions-item label="Recipient">{{ currentOrder.shipping_name }}</el-descriptions-item>
            <el-descriptions-item label="Phone">{{ currentOrder.shipping_phone }}</el-descriptions-item>
            <el-descriptions-item label="Address">{{ currentOrder.shipping_address }}</el-descriptions-item>
            <el-descriptions-item v-if="currentOrder.shipping_company" label="Carrier">{{ currentOrder.shipping_company }}</el-descriptions-item>
            <el-descriptions-item v-if="currentOrder.shipping_no" label="Tracking No.">{{ currentOrder.shipping_no }}</el-descriptions-item>
          </el-descriptions>
        </div>

        <!-- Amount Summary -->
        <div class="detail-section">
          <h3>Amount Summary</h3>
          <div class="amount-details">
            <div class="total-row">
              <span>Subtotal:</span>
              <span>£{{ currentOrder.total_amount }}</span>
            </div>
            <div class="total-row" v-if="currentOrder.shipping_fee > 0">
              <span>Shipping:</span>
              <span>£{{ currentOrder.shipping_fee }}</span>
            </div>
            <div class="total-row discount" v-if="currentOrder.discount_amount > 0">
              <span>{{ currentOrder.promotion_id === 1 ? 'First Order Discount:' : 'Discount:' }}</span>
              <span class="discount-text">-£{{ currentOrder.discount_amount }}</span>
            </div>
            <div class="total-row final">
              <span>Total:</span>
              <span class="amount">£{{ currentOrder.pay_amount }}</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="detail-section">
          <el-button v-if="currentOrder.status === 0" type="primary" @click="gotoPayment(currentOrder.id)">
            Pay Now
          </el-button>
          <el-button v-if="currentOrder.status === 0" @click="handleCancel(currentOrder.id)">
            Cancel Order
          </el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessageBox } from 'element-plus'
import { ArrowLeft, Timer } from '@element-plus/icons-vue'
import message from '@/utils/message'
import { getOrders, getOrder, cancelOrder } from '@/api/order'

const router = useRouter()
const activeTab = ref('all')
const orders = ref([])
const currentPage = ref(1)
const total = ref(0)
const currentOrder = ref(null)
const timer = ref(null)

onMounted(() => {
  fetchOrders()
  startTimer()
})

onUnmounted(() => {
  if (timer.value) clearInterval(timer.value)
})

const startTimer = () => {
  timer.value = setInterval(() => {
    // 触发视图更新
    orders.value = [...orders.value]
  }, 1000)
}

const ORDER_TIMEOUT_MS = 60000 // 1分钟超时（测试用），正式环境改为 3600000（1小时）

const getCountdown = (createdAt) => {
  if (!createdAt) return ''
  // 处理时间格式
  let timeStr = createdAt
  if (typeof timeStr === 'string') {
    timeStr = timeStr.replace(' ', 'T')
  }
  const created = new Date(timeStr).getTime()
  const now = Date.now()
  const diff = created + ORDER_TIMEOUT_MS - now

  if (diff <= 0) return 'Expired'

  const minutes = Math.floor(diff / (1000 * 60))
  const seconds = Math.floor((diff % (1000 * 60)) / 1000)

  return `${minutes}m ${seconds}s`
}

const fetchOrders = async () => {
  const params = { page: currentPage.value }
  if (activeTab.value !== 'all') {
    params.status = activeTab.value
  }
  
  const res = await getOrders(params)
  orders.value = res.data.data.data || []
  total.value = res.data.data.total || 0
}

const handleTabChange = () => {
  currentPage.value = 1
  fetchOrders()
}

const getStatusType = (status) => {
  const types = { 
    0: 'warning',   // Pending
    1: 'primary',   // Processing
    3: 'success',   // Completed
    4: 'info'       // Cancelled
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

const showDetail = async (id) => {
  // 移动端直接跳转到详情页面
  if (window.innerWidth <= 768) {
    router.push(`/user-center/orders/${id}`)
    return
  }
  
  // PC端使用内嵌详情
  try {
    const res = await getOrder(id)
    currentOrder.value = res.data.data
  } catch (error) {
    message.error('获取订单详情失败')
  }
}

const gotoPayment = (id) => {
  router.push(`/payment/${id}`)
}

const getProductImage = (item) => {
  // 优先使用product关联的图片，否则使用order_item的product_image字段
  const imagePath = item.product?.image || item.product_image
  if (!imagePath) return 'https://via.placeholder.com/80'
  if (imagePath.startsWith('http')) return imagePath
  return `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}${imagePath}`
}

const handleCancel = async (id) => {
  try {
    await ElMessageBox.confirm('Are you sure you want to cancel this order?', 'Confirm', {
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      type: 'warning'
    })
    await cancelOrder(id)
    message.success('Order cancelled')
    // 返回订单列表
    currentOrder.value = null
    fetchOrders()
  } catch (error) {
    if (error !== 'cancel') {
      message.error(error.message || '操作失败')
    }
  }
}
</script>

<style scoped lang="scss">
.orders-page {
  .page-title {
    font-size: 24px;
    font-weight: bold;
    margin: 0 0 20px 0;
  }

  .order-tabs {
    margin-bottom: 20px;
  }

  .orders-table {
    table {
      width: 100%;
      border-collapse: collapse;

      thead {
        background: #f5f5f5;

        th {
          padding: 12px;
          text-align: left;
          font-weight: 600;
          color: #666;
          font-size: 14px;
        }
      }

      tbody {
        .order-row {
          border-bottom: 1px solid #f0f0f0;

          td {
            padding: 16px 12px;
            vertical-align: middle;
            color: #333;
            font-size: 14px;
          }

          &:hover {
            background: #f9f9f9;
          }
        }
      }
    }

    .pagination {
      margin-top: 20px;
      display: flex;
      justify-content: center;
    }
  }

  .order-detail-view {
    .back-btn {
      margin-bottom: 20px;
    }
  }

  .countdown-inline {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-left: 10px;
    font-size: 12px;
    color: #e74c3c;
  }
  
  .countdown-cell {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 6px;
    font-size: 12px;
    color: #e6a23c;
    
    .timeout {
      color: #909399;
    }
  }

  .order-detail {
    .detail-section {
      margin-bottom: 30px;

      h3 {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
      }

      .product-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid #f5f5f5;

        &:last-child {
          border-bottom: none;
        }

        img {
          width: 80px;
          height: 80px;
          object-fit: cover;
          border-radius: 8px;
          border: 1px solid #eee;
        }

        .product-info {
          flex: 1;

          .name {
            font-size: 15px;
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
          }

          .spec {
            font-size: 13px;
            color: #999;
          }
        }

        .product-total {
          font-size: 16px;
          font-weight: 600;
          color: var(--primary-color);
        }
      }

      .amount-details {
        background: #fafafa;
        padding: 20px;
        border-radius: 8px;

        .total-row {
          display: flex;
          justify-content: space-between;
          align-items: center;
          font-size: 15px;
          padding: 10px 0;
          border-bottom: 1px solid #eee;

          &:last-child {
            border-bottom: none;
          }

          &.discount {
            color: #e74c3c;

            .discount-text {
              font-weight: 600;
              color: #e74c3c;
            }
          }

          &.final {
            font-size: 18px;
            font-weight: 600;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #ddd;

            .amount {
              color: #e74c3c;
              font-size: 24px;
            }
          }
        }
      }
    }
  }
}

// 移动端卡片默认隐藏
.order-cards {
  display: none;
}

// PC端隐藏内嵌详情
.pc-only {
  display: block;
}

// 移动端适配 - 卡片式布局
@media (max-width: 768px) {
  .orders-page {
    padding: 0;

    // 隐藏PC端内嵌详情
    .pc-only {
      display: none;
    }

    .order-tabs {
      margin-bottom: .1rem;
      :deep(.el-tabs__nav-wrap) {
        padding: 0 10px;
      }

      :deep(.el-tabs__item) {
        font-size: 14px;
        padding: 0 10px;
      }
    }

    // 隐藏表格，显示卡片
    .orders-table {
      table {
        display: none;
      }

      // 卡片式布局
      .order-cards {
        display: block;
        padding: 0 0.2rem;

        .order-card {
          background: white;
          border-radius: .08rem;
          margin-bottom: .3rem;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
          overflow: hidden;
          cursor: pointer;
          transition: box-shadow 0.3s;

          &:active {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
          }

          .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: #f8f8f8;
            border-bottom: 1px solid #e8e8e8;

            .order-no {
              font-size: 13px;
              color: #666;
              font-weight: 500;
              flex: 1;
              overflow: hidden;
              text-overflow: ellipsis;
              white-space: nowrap;
              margin-right: 10px;
            }
            
            .status-wrapper {
              display: flex;
              flex-direction: column;
              align-items: flex-end;
              gap: 4px;
              
              .countdown-mobile {
                font-size: 11px;
                color: #e6a23c;
                
                &.timeout {
                  color: #909399;
                }
              }
            }
          }

          .card-body {
            padding: 12px 15px;

            .order-info-row {
              display: flex;
              justify-content: space-between;
              align-items: center;
              margin-bottom: 8px;
              font-size: 14px;

              &:last-child {
                margin-bottom: 0;
              }

              .label {
                color: #999;
              }

              .value {
                color: #333;
                font-weight: 500;

                &.amount {
                  color: #e74c3c;
                  font-size: 16px;
                  font-weight: 600;
                }
              }
            }
          }

          .card-footer {
            padding: 10px 15px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: flex-end;

            .el-button {
              padding: 8px 20px;
            }
          }
        }
      }

      .pagination {
        margin-top: 0rem;
        padding: 0 10px;

        :deep(.el-pagination) {
          justify-content: center;
          
          .el-pager li {
            min-width: 28px;
            height: 28px;
            line-height: 28px;
          }
        }
      }
    }

    // 订单详情页面优化
    .order-detail-view {
      .back-btn {
        margin: 10px;
      }

      .order-detail {
        padding: 0 10px;

        .detail-section {
          margin-bottom: 20px;

          h3 {
            font-size: 15px;
          }

          :deep(.el-descriptions) {
            font-size: 13px;

            .el-descriptions__label {
              width: 80px;
            }
          }

          .product-item {
            gap: 10px;
            padding: 10px 0;

            img {
              width: 60px;
              height: 60px;
            }

            .product-info {
              .name {
                font-size: 14px;
                margin-bottom: 5px;
              }

              .spec {
                font-size: 12px;
              }
            }

            .product-total {
              font-size: 14px;
            }
          }

          .amount-details {
            padding: 15px;

            .total-row {
              font-size: 14px;
              padding: 8px 0;

              &.final {
                font-size: 16px;

                .amount {
                  font-size: 20px;
                }
              }
            }
          }

          .el-button {
            width: 100%;
            margin-bottom: 10px;

            &:last-child {
              margin-bottom: 0;
            }
          }
        }
      }
    }
  }
}
</style>


