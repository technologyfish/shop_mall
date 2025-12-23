<template>
  <div class="subscriptions-page">
    <h1 class="page-title">订阅用户列表</h1>

    <div class="card">
      <div class="filter-bar">
        <el-input v-model="searchParams.keyword" placeholder="搜索用户名/邮箱" style="width: 300px" @keyup.enter="fetchSubscriptions" />
        <el-select v-model="searchParams.status" placeholder="订阅状态" clearable @change="fetchSubscriptions" style="width: 150px">
          <el-option label="活跃" value="active" />
          <el-option label="已暂停" value="paused" />
          <el-option label="已取消" value="cancelled" />
          <el-option label="逾期" value="past_due" />
        </el-select>
        <el-button type="primary" @click="fetchSubscriptions">搜索</el-button>
      </div>

      <el-table :data="subscriptions" style="width: 100%">
        <el-table-column label="用户信息" width="200">
          <template #default="{ row }">
            <div><strong>{{ row.user?.username }}</strong></div>
            <div class="text-secondary">{{ row.user?.email }}</div>
          </template>
        </el-table-column>
        <el-table-column label="订阅计划" width="200">
          <template #default="{ row }">
            <div><strong>{{ row.plan_name }}</strong></div>
            <div class="text-secondary">{{ row.bottles_per_delivery }}瓶/次</div>
          </template>
        </el-table-column>
        <el-table-column prop="price" label="价格" width="100">
          <template #default="{ row }">
            ${{ row.price }}/{{ getPlanTypeText(row.plan_type) }}
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="下次发货" width="120">
          <template #default="{ row }">
            {{ formatDate(row.next_delivery_date) }}
          </template>
        </el-table-column>
        <el-table-column label="当前周期" width="200">
          <template #default="{ row }">
            <div class="text-small">
              {{ formatDate(row.current_period_start) }}<br/>
              至 {{ formatDate(row.current_period_end) }}
            </div>
          </template>
        </el-table-column>
        <el-table-column label="Stripe订阅ID" width="180">
          <template #default="{ row }">
            <el-tooltip :content="row.stripe_subscription_id" placement="top">
              <span class="text-small">{{ row.stripe_subscription_id?.substring(0, 20) }}...</span>
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column label="订阅时间" width="180">
          <template #default="{ row }">
            {{ formatDateTime(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="100" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="viewDetails(row)">查看详情</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="searchParams.page"
          :total="total"
          :page-size="searchParams.per_page"
          layout="total, prev, pager, next"
          @current-change="fetchSubscriptions"
        />
      </div>
    </div>

    <!-- 详情对话框 -->
    <el-dialog v-model="detailVisible" title="订阅详情" width="800px">
      <div v-if="currentSubscription" class="subscription-detail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="用户">
            {{ currentSubscription.user?.username }} ({{ currentSubscription.user?.email }})
          </el-descriptions-item>
          <el-descriptions-item label="订阅计划">
            {{ currentSubscription.plan_name }}
          </el-descriptions-item>
          <el-descriptions-item label="价格">
            ${{ currentSubscription.price }}/{{ getPlanTypeText(currentSubscription.plan_type) }}
          </el-descriptions-item>
          <el-descriptions-item label="配送瓶数">
            {{ currentSubscription.bottles_per_delivery }}瓶/次
          </el-descriptions-item>
          <el-descriptions-item label="订阅状态">
            <el-tag :type="getStatusType(currentSubscription.status)">
              {{ getStatusText(currentSubscription.status) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="当前周期开始">
            {{ formatDate(currentSubscription.current_period_start) }}
          </el-descriptions-item>
          <el-descriptions-item label="当前周期结束">
            {{ formatDate(currentSubscription.current_period_end) }}
          </el-descriptions-item>
          <el-descriptions-item label="Stripe订阅ID" :span="2">
            {{ currentSubscription.stripe_subscription_id }}
          </el-descriptions-item>
          <el-descriptions-item label="Stripe客户ID" :span="2">
            {{ currentSubscription.stripe_customer_id }}
          </el-descriptions-item>
          <el-descriptions-item label="订阅时间" :span="2">
            {{ formatDateTime(currentSubscription.created_at) }}
          </el-descriptions-item>
        </el-descriptions>

        <h3 style="margin-top: 20px;">配送记录</h3>
        <el-table :data="currentSubscription.deliveries" style="width: 100%" size="small">
          <el-table-column label="配送日期" width="120">
            <template #default="{ row }">
              {{ formatDate(row.delivery_date) }}
            </template>
          </el-table-column>
          <el-table-column label="状态" width="100">
            <template #default="{ row }">
              <el-tag :type="getDeliveryStatusType(row.status)" size="small">
                {{ getDeliveryStatusText(row.status) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="tracking_company" label="物流公司" width="120" />
          <el-table-column prop="tracking_number" label="物流单号" width="150" />
          <el-table-column label="发货时间" width="180">
            <template #default="{ row }">
              {{ row.shipped_at ? formatDateTime(row.shipped_at) : '-' }}
            </template>
          </el-table-column>
          <el-table-column prop="notes" label="备注" />
        </el-table>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getSubscriptions, getSubscription } from '@/api/admin/subscription'

const subscriptions = ref([])
const total = ref(0)
const detailVisible = ref(false)
const currentSubscription = ref(null)

const searchParams = reactive({
  keyword: '',
  status: '',
  page: 1,
  per_page: 15
})

onMounted(() => {
  fetchSubscriptions()
})

const fetchSubscriptions = async () => {
  try {
    const res = await getSubscriptions(searchParams)
    subscriptions.value = res.data.data.data || []
    total.value = res.data.data.total || 0
  } catch (error) {
    ElMessage.error('获取订阅列表失败')
  }
}

const viewDetails = async (row) => {
  try {
    const res = await getSubscription(row.id)
    currentSubscription.value = res.data.data
    detailVisible.value = true
  } catch (error) {
    ElMessage.error('获取订阅详情失败')
  }
}

const getStatusType = (status) => {
  const types = {
    'active': 'success',
    'paused': 'warning',
    'cancelled': 'info',
    'past_due': 'danger'
  }
  return types[status] || 'info'
}

const getStatusText = (status) => {
  const texts = {
    'active': '活跃',
    'paused': '已暂停',
    'cancelled': '已取消',
    'past_due': '逾期'
  }
  return texts[status] || status
}

const getPlanTypeText = (type) => {
  const types = {
    'monthly': '月',
    'quarterly': '季',
    'yearly': '年'
  }
  return types[type] || type
}

const getDeliveryStatusType = (status) => {
  const types = {
    'pending': 'warning',
    'shipped': 'primary',
    'delivered': 'success',
    'failed': 'danger'
  }
  return types[status] || 'info'
}

const getDeliveryStatusText = (status) => {
  const texts = {
    'pending': '待处理',
    'shipped': '已发货',
    'delivered': '已送达',
    'failed': '失败'
  }
  return texts[status] || status
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('zh-CN')
}

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('zh-CN')
}
</script>

<style scoped lang="scss">
.subscriptions-page {
  .filter-bar {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
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

  .subscription-detail {
    h3 {
      font-size: 16px;
      margin-bottom: 15px;
    }
  }
}
</style>




