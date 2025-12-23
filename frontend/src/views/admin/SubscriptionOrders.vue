<template>
  <div class="subscription-orders-page">
    <h1 class="page-title">订阅订单管理</h1>

    <div class="card">
      <div class="filter-bar">
        <el-input v-model="searchParams.keyword" placeholder="搜索订单号/收货人" style="width: 300px" @keyup.enter="fetchOrders" />
        <el-select v-model="searchParams.status" placeholder="订单状态" clearable @change="fetchOrders" style="width: 150px">
          <el-option label="待发货" :value="1" />
          <el-option label="已完成" :value="3" />
          <el-option label="已取消" :value="4" />
        </el-select>
        <el-button type="primary" @click="fetchOrders">搜索</el-button>
      </div>

      <el-table :data="orders" style="width: 100%">
        <el-table-column label="订单号" width="200">
          <template #default="{ row }">
            <div>{{ row.order_no }}</div>
            <el-tag type="warning" size="small" style="margin-top: 5px;">
              订阅订单
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="user.username" label="用户" width="120" />
        <el-table-column label="订阅计划" width="300">
          <template #default="{ row }">
            <div class="subscription-info">
              <div v-if="row.subscription">
                <div class="plan-name">{{ row.subscription.plan_name }}</div>
                <div class="plan-detail">
                  <span>{{ row.subscription.plan_type === 'monthly' ? '月度' : row.subscription.plan_type === 'quarterly' ? '季度' : '年度' }}</span>
                  <span style="margin-left: 10px;">{{ row.subscription.bottles_per_delivery }}瓶/次</span>
                </div>
              </div>
              <div v-else class="text-secondary">未关联订阅</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="pay_amount" label="订单金额" width="120">
          <template #default="{ row }">£{{ row.pay_amount }}</template>
        </el-table-column>
        <el-table-column label="订单状态" width="120">
          <template #default="{ row }">
            <el-tag :type="getOrderStatusType(row.status)">{{ getOrderStatusText(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="收货人" width="150">
          <template #default="{ row }">
            <div>{{ row.shipping_name }}</div>
            <div class="text-secondary text-small">{{ row.shipping_phone }}</div>
          </template>
        </el-table-column>
        <el-table-column label="下单时间" width="180">
          <template #default="{ row }">{{ formatDate(row.created_at) }}</template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="handleView(row.id)">查看</el-button>
            <el-button 
              v-if="row.status === 1" 
              link 
              type="success" 
              size="small" 
              @click="handleShip(row)"
            >
              发货
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="searchParams.page"
          v-model:page-size="searchParams.per_page"
          :total="total"
          :page-sizes="[10, 15, 20, 50]"
          layout="total, sizes, prev, pager, next"
          @current-change="fetchOrders"
          @size-change="fetchOrders"
        />
      </div>
    </div>

    <!-- 发货对话框 -->
    <el-dialog v-model="shipDialogVisible" title="订单发货" width="500px">
      <el-form :model="shipForm" label-width="120px">
        <el-form-item label="物流公司">
          <el-input v-model="shipForm.shipping_company" placeholder="如：顺丰快递" />
        </el-form-item>
        <el-form-item label="物流单号">
          <el-input v-model="shipForm.shipping_no" placeholder="请输入物流单号" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="shipDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="shipping" @click="handleConfirmShip">确认发货</el-button>
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
  is_subscription: 1, // 固定为订阅订单
  page: 1,
  per_page: 15
})

const shipDialogVisible = ref(false)
const shipping = ref(false)
const shipForm = reactive({
  id: null,
  status: 3,
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

const handleView = (id) => {
  router.push({ name: 'AdminOrderDetail', params: { id } })
}

const handleShip = (row) => {
  shipForm.id = row.id
  shipForm.shipping_company = ''
  shipForm.shipping_no = ''
  shipDialogVisible.value = true
}

const handleConfirmShip = async () => {
  if (!shipForm.shipping_company || !shipForm.shipping_no) {
    ElMessage.warning('请填写完整的物流信息')
    return
  }

  shipping.value = true
  try {
    await shipOrder(shipForm.id, {
      status: 3,
      shipping_company: shipForm.shipping_company,
      shipping_no: shipForm.shipping_no
    })
    ElMessage.success('发货成功')
    shipDialogVisible.value = false
    fetchOrders()
  } catch (error) {
    ElMessage.error('发货失败')
  } finally {
    shipping.value = false
  }
}
</script>

<style scoped lang="scss">
.subscription-orders-page {
  .page-title {
    font-size: 24px;
    margin-bottom: 20px;
  }

  .card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
  }

  .filter-bar {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
  }

  .subscription-info {
    .plan-name {
      font-weight: 600;
      margin-bottom: 5px;
    }

    .plan-detail {
      font-size: 12px;
      color: #666;
    }
  }

  .text-secondary {
    font-size: 12px;
    color: #999;
  }

  .text-small {
    font-size: 12px;
  }

  .pagination {
    margin-top: 20px;
    display: flex;
    justify-content: center;
  }
}
</style>




