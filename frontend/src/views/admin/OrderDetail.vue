<template>
  <div class="admin-order-detail-page">
    <div class="page-header">
      <el-button link @click="router.back()">
        <el-icon><ArrowLeft /></el-icon>
        返回
      </el-button>
    </div>

    <div v-if="order" class="content">
      <!-- 订单信息 -->
      <div class="card">
        <h2 class="card-title">订单信息</h2>
        <el-descriptions :column="2" border>
          <el-descriptions-item label="订单号">{{ order.order_no }}</el-descriptions-item>
          <el-descriptions-item label="下单时间">{{ formatDate(order.created_at) }}</el-descriptions-item>
          <el-descriptions-item label="用户">{{ order.user?.username }}</el-descriptions-item>
          <el-descriptions-item label="订单状态">
            <el-tag :type="getOrderStatusType(order.status)">
              {{ getOrderStatusText(order.status) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="支付方式">{{ order.payment_method || '-' }}</el-descriptions-item>
          <el-descriptions-item label="支付时间">{{ order.paid_at ? formatDate(order.paid_at) : '-' }}</el-descriptions-item>
          <el-descriptions-item label="商品金额">¥{{ order.total_amount }}</el-descriptions-item>
          <el-descriptions-item label="运费" v-if="order.shipping_fee">
            <span :style="{ color: order.shipping_fee == 0 ? '#67c23a' : '#606266' }">
              {{ order.shipping_fee == 0 ? '免运费' : `¥${order.shipping_fee}` }}
            </span>
          </el-descriptions-item>
          <el-descriptions-item label="优惠折扣" v-if="order.discount_amount > 0">
            <span style="color: #e74c3c">-¥{{ order.discount_amount }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="实付金额">
            <span style="color: #e74c3c; font-size: 18px; font-weight: bold">¥{{ order.pay_amount }}</span>
          </el-descriptions-item>
        </el-descriptions>
      </div>

      <!-- 商品列表 -->
      <div class="card">
        <h2 class="card-title">商品信息</h2>
        <el-table :data="order.items" style="width: 100%">
          <el-table-column label="商品" width="400">
            <template #default="{ row }">
              <div class="product-cell">
                <img :src="getProductImage(row)" :alt="row.product_name" class="product-img" />
                <div class="product-name">{{ row.product_name }}</div>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="price" label="单价" width="120">
            <template #default="{ row }">¥{{ row.price }}</template>
          </el-table-column>
          <el-table-column prop="quantity" label="数量" width="100" />
          <el-table-column label="小计" width="120">
            <template #default="{ row }">¥{{ (row.price * row.quantity).toFixed(2) }}</template>
          </el-table-column>
        </el-table>
      </div>

      <!-- 收货信息 -->
      <div class="card">
        <h2 class="card-title">收货信息</h2>
        <el-descriptions :column="1" border>
          <el-descriptions-item label="收货人">{{ order.shipping_name }}</el-descriptions-item>
          <el-descriptions-item label="邮箱" v-if="order.shipping_email">{{ order.shipping_email }}</el-descriptions-item>
          <el-descriptions-item label="联系电话">{{ order.shipping_phone }}</el-descriptions-item>
          <el-descriptions-item label="收货地址">{{ order.shipping_address }}</el-descriptions-item>
          <el-descriptions-item label="城市" v-if="order.shipping_city">{{ order.shipping_city }}</el-descriptions-item>
          <el-descriptions-item label="邮政编码" v-if="order.shipping_postal_code">{{ order.shipping_postal_code }}</el-descriptions-item>
          <el-descriptions-item v-if="order.shipping_company" label="物流公司">{{ order.shipping_company }}</el-descriptions-item>
          <el-descriptions-item v-if="order.shipping_no" label="物流单号">{{ order.shipping_no }}</el-descriptions-item>
          <el-descriptions-item v-if="order.shipped_at" label="发货时间">{{ formatDate(order.shipped_at) }}</el-descriptions-item>
          <el-descriptions-item label="备注">{{ order.remark || '无' }}</el-descriptions-item>
        </el-descriptions>
      </div>
    </div>

    <div v-else class="loading">
      <el-skeleton :rows="10" animated />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ArrowLeft } from '@element-plus/icons-vue'
import { getAdminOrder, shipOrder } from '@/api/admin'

const route = useRoute()
const router = useRouter()
const order = ref(null)

onMounted(() => {
  fetchOrderDetail()
})

const fetchOrderDetail = async () => {
  try {
    const res = await getAdminOrder(route.params.id)
    order.value = res.data.data
  } catch (error) {
    ElMessage.error('获取订单详情失败')
    router.back()
  }
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  const seconds = String(date.getSeconds()).padStart(2, '0')
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
}

const getOrderStatusType = (status) => {
  const types = { 
    0: 'warning',  // 待支付
    1: 'primary',  // 待发货
    3: 'success',  // 已完成
    4: 'info'      // 已取消
  }
  return types[status] || 'info'
}

const getOrderStatusText = (status) => {
  const texts = { 
    0: '待支付', 
    1: '待发货', 
    3: '已完成', 
    4: '已取消' 
  }
  return texts[status] || '未知'
}

const getProductImage = (item) => {
  // 优先使用product关联的图片，否则使用order_item的product_image字段
  const imagePath = item.product?.image || item.product_image
  if (!imagePath) return 'https://via.placeholder.com/80'
  if (imagePath.startsWith('http')) return imagePath
  return `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}${imagePath}`
}
</script>

<style scoped lang="scss">
.admin-order-detail-page {
  .page-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
  }

  .content {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  .card {
    background: white;
    border-radius: 8px;
    padding: 20px;

    .card-title {
      font-size: 16px;
      font-weight: bold;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #f0f0f0;
    }
  }

  .product-cell {
    display: flex;
    align-items: center;
    gap: 15px;

    .product-img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 4px;
      border: 1px solid #eee;
    }

    .product-name {
      flex: 1;
      font-size: 14px;
      color: #333;
    }
  }

  .loading {
    background: white;
    border-radius: 8px;
    padding: 20px;
  }
}
</style>

