<template>
  <div class="admin-orders-page">
    <h1 class="page-title">订单管理</h1>

    <div class="card">
      <div class="filter-bar">
        <el-input v-model="searchParams.keyword" placeholder="搜索订单号/收货人" style="width: 300px" @keyup.enter="fetchOrders" />
        <el-select v-model="searchParams.status" placeholder="订单状态" clearable @change="fetchOrders" style="width: 150px">
          <el-option label="待支付" :value="0" />
          <el-option label="待发货" :value="1" />
          <el-option label="已完成" :value="3" />
          <el-option label="已取消" :value="4" />
        </el-select>
        <el-select v-model="searchParams.is_subscription" placeholder="订单类型" clearable @change="fetchOrders" style="width: 150px">
          <el-option label="普通订单" :value="0" />
          <el-option label="订阅订单" :value="1" />
        </el-select>
        <el-button type="primary" @click="fetchOrders">搜索</el-button>
      </div>

      <el-table :data="orders" style="width: 100%">
        <el-table-column label="订单号" width="200">
          <template #default="{ row }">
            <div>{{ row.order_no }}</div>
            <el-tag v-if="row.is_subscription == 1" type="warning" size="small" style="margin-top: 5px;">
              订阅订单
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="user.username" label="用户" width="120" />
        <el-table-column label="商品" width="300">
          <template #default="{ row }">
            <div class="order-products">
              <div v-for="item in row.items" :key="item.id" class="product-item">
                <img :src="getProductImage(item)" :alt="item.product_name" class="product-img" />
                <div class="product-info">
                  <div class="product-name">{{ item.product_name }}</div>
                  <div class="product-spec">¥{{ item.price }} x {{ item.quantity }}</div>
                </div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="pay_amount" label="订单金额" width="120">
          <template #default="{ row }">¥{{ row.pay_amount }}</template>
        </el-table-column>
        <el-table-column label="订单状态" width="120">
          <template #default="{ row }">
            <el-tag :type="getOrderStatusType(row.status)">{{ getOrderStatusText(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="收货人" width="150">
          <template #default="{ row }">{{ row.shipping_name }}</template>
        </el-table-column>
        <el-table-column label="下单时间" width="180">
          <template #default="{ row }">{{ formatDate(row.created_at) }}</template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleView(row.id)">查看</el-button>
            <el-button 
              link 
              v-if="row.status === 1 || row.status === 3" 
              type="success" 
              @click="handleShip(row)"
            >
              {{ row.status === 3 ? '查看物流' : '填写物流' }}
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="searchParams.page"
          :total="total"
          :page-size="searchParams.per_page"
          layout="total, prev, pager, next"
          @current-change="fetchOrders"
        />
      </div>
    </div>

    <!-- 填写物流对话框 -->
    <el-dialog
      v-model="shipDialogVisible"
      :title="shipForm.status === 3 ? '物流信息' : '填写物流信息'"
      width="500px"
      @close="resetShipForm"
    >
      <el-form :model="shipForm" label-width="100px">
        <el-form-item label="快递公司">
          <el-input v-model="shipForm.shipping_company" placeholder="请输入快递公司" :disabled="shipForm.status === 3" />
        </el-form-item>
        <el-form-item label="快递单号">
          <el-input v-model="shipForm.shipping_no" placeholder="请输入快递单号" :disabled="shipForm.status === 3" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="shipDialogVisible = false">{{ shipForm.status === 3 ? '关闭' : '取消' }}</el-button>
        <el-button v-if="shipForm.status !== 3" type="primary" @click="confirmShip" :loading="shipping">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getAdminOrders, shipOrder } from '@/api/admin'

const router = useRouter()
const orders = ref([])
const total = ref(0)

const searchParams = reactive({
  keyword: '',
  status: null,
  is_subscription: null,
  page: 1,
  per_page: 15
})

const shipDialogVisible = ref(false)
const shipping = ref(false)
const shipForm = reactive({
  id: null,
  status: 1,
  shipping_company: '',
  shipping_no: ''
})

onMounted(() => {
  fetchOrders()
})

const fetchOrders = async () => {
  try {
    const res = await getAdminOrders(searchParams)
    orders.value = res.data.data.data || []
    total.value = res.data.data.total || 0
  } catch (error) {
    ElMessage.error('获取订单列表失败')
  }
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

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleString('zh-CN', { 
    year: 'numeric', 
    month: '2-digit', 
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getProductImage = (item) => {
  // 优先使用product关联的图片，否则使用order_item的product_image字段
  const imagePath = item.product?.image || item.product_image
  if (!imagePath) return 'https://via.placeholder.com/60'
  if (imagePath.startsWith('http')) return imagePath
  return `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}${imagePath}`
}

const handleView = (id) => {
  router.push({ name: 'AdminOrderDetail', params: { id } })
}

const handleShip = (row) => {
  shipForm.id = row.id
  shipForm.status = row.status
  shipForm.shipping_company = row.shipping_company || ''
  shipForm.shipping_no = row.shipping_no || ''
  shipDialogVisible.value = true
}

const confirmShip = async () => {
  if (!shipForm.shipping_company || !shipForm.shipping_no) {
    ElMessage.warning('请填写快递公司和快递单号')
    return
  }

  shipping.value = true
  try {
    await shipOrder(shipForm.id, {
      status: 3, // 填写物流单号后设置为已完成
      shipping_company: shipForm.shipping_company,
      shipping_no: shipForm.shipping_no
    })
    ElMessage.success('操作成功')
    shipDialogVisible.value = false
    fetchOrders()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败')
  } finally {
    shipping.value = false
  }
}

const resetShipForm = () => {
  shipForm.id = null
  shipForm.status = 1
  shipForm.shipping_company = ''
  shipForm.shipping_no = ''
}
</script>

<style scoped lang="scss">
.admin-orders-page {
  .filter-bar {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
  }

  .order-products {
    display: flex;
    flex-direction: column;
    gap: 10px;

    .product-item {
      display: flex;
      align-items: center;
      gap: 10px;

      .product-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #eee;
      }

      .product-info {
        flex: 1;

        .product-name {
          font-size: 14px;
          color: #333;
          margin-bottom: 4px;
        }

        .product-spec {
          font-size: 12px;
          color: #999;
        }
      }
    }
  }
}
</style>


