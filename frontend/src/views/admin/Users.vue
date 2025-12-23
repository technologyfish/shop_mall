<template>
  <div class="admin-users-page">
    <h1 class="page-title">用户管理</h1>

    <div class="card">
      <div class="filter-bar">
        <el-input v-model="searchParams.keyword" placeholder="搜索用户名/邮箱" style="width: 300px" @keyup.enter="fetchUsers" />
        <el-select v-model="searchParams.status" placeholder="用户状态" clearable @change="fetchUsers">
          <el-option label="正常" :value="1" />
          <el-option label="禁用" :value="0" />
        </el-select>
        <el-select v-model="searchParams.is_subscriber" placeholder="订阅状态" clearable @change="fetchUsers">
          <el-option label="订阅用户" :value="1" />
          <el-option label="非订阅用户" :value="0" />
        </el-select>
        <el-button @click="fetchUsers">搜索</el-button>
      </div>

      <el-table :data="users" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="username" label="用户名" />
        <el-table-column prop="email" label="邮箱" />
        <el-table-column prop="phone" label="手机号" />
        <el-table-column label="用户标识" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.is_subscriber > 0" type="success" size="small">订阅用户</el-tag>
            <el-tag v-else type="info" size="small">普通用户</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="首单优惠" width="100">
          <template #default="{ row }">
            <el-tag v-if="row.first_order_discount === 1" type="success" size="small">激活</el-tag>
            <el-tag v-else-if="row.off_code" type="info" size="small">已使用</el-tag>
            <span v-else class="text-gray">-</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatusChange(row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="注册时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="100">
          <template #default="{ row }">
            <el-button text @click="handleView(row.id)">查看</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="searchParams.page"
          :total="total"
          :page-size="searchParams.per_page"
          layout="total, prev, pager, next"
          @current-change="fetchUsers"
        />
      </div>
    </div>

    <!-- User Detail Dialog -->
    <el-dialog v-model="viewDialogVisible" title="用户详情" width="800px">
      <el-descriptions :column="2" border>
        <el-descriptions-item label="用户名">{{ viewUser.username }}</el-descriptions-item>
        <el-descriptions-item label="昵称">{{ viewUser.nickname || '-' }}</el-descriptions-item>
        <el-descriptions-item label="邮箱">{{ viewUser.email }}</el-descriptions-item>
        <el-descriptions-item label="手机号">{{ viewUser.phone || '-' }}</el-descriptions-item>
        <el-descriptions-item label="注册时间">{{ formatDate(viewUser.created_at) }}</el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="viewUser.status === 1 ? 'success' : 'danger'">
            {{ viewUser.status === 1 ? '正常' : '禁用' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="首单优惠">
          <el-tag v-if="viewUser.first_order_discount === 1" type="success">激活</el-tag>
          <el-tag v-else-if="viewUser.off_code" type="info">已使用</el-tag>
          <span v-else>-</span>
        </el-descriptions-item>
        <el-descriptions-item label="Off Code">
          {{ viewUser.off_code || '-' }}
        </el-descriptions-item>
      </el-descriptions>

      <div class="section-title">收货地址</div>
      <el-table :data="viewUser.addresses || []" border style="margin-top: 10px">
        <el-table-column prop="name" label="收货人" width="100" />
        <el-table-column prop="phone" label="电话" width="120" />
        <el-table-column label="地址">
          <template #default="{ row }">
            {{ row.province }} {{ row.city }} {{ row.district }} {{ row.detail }}
          </template>
        </el-table-column>
      </el-table>

      <div class="section-title">最近订单</div>
      <el-table :data="viewUser.orders || []" border style="margin-top: 10px">
        <el-table-column prop="order_no" label="订单号" width="180" />
        <el-table-column prop="pay_amount" label="金额" width="100" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getOrderStatusType(row.status)" size="small">
              {{ getOrderStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="下单时间">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
      </el-table>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getUsers, updateUserStatus, getUserDetail } from '@/api/admin'
import { formatDate } from '@/utils/format'

const users = ref([])
const total = ref(0)
const viewDialogVisible = ref(false)
const viewUser = ref({})

const searchParams = reactive({
  keyword: '',
  status: null,
  is_subscriber: null,
  page: 1,
  per_page: 15
})

onMounted(() => {
  fetchUsers()
})

const fetchUsers = async () => {
  const res = await getUsers(searchParams)
  users.value = res.data.data.data || []
  total.value = res.data.data.total || 0
}

const handleStatusChange = async (user) => {
  try {
    const newStatus = Number(user.status)
    await updateUserStatus(user.id, { status: newStatus })
    ElMessage.success('状态更新成功')
  } catch (error) {
    ElMessage.error(error.message || '操作失败')
    // Revert status
    user.status = Number(user.status) === 1 ? 0 : 1
  }
}

const handleView = async (id) => {
  try {
    const res = await getUserDetail(id)
    viewUser.value = res.data.data
    viewDialogVisible.value = true
  } catch (error) {
    ElMessage.error('获取详情失败')
  }
}

// 订单状态
const getOrderStatusType = (status) => {
  const types = { 
    0: 'warning',   // 待支付
    1: 'primary',   // 待发货
    3: 'success',   // 已完成
    4: 'info'       // 已取消
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
</script>

<style scoped lang="scss">
.admin-users-page {
  .filter-bar {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
  }
  
  .section-title {
    font-size: 16px;
    font-weight: bold;
    margin-top: 20px;
    margin-bottom: 10px;
    padding-left: 10px;
    border-left: 4px solid #409eff;
  }
  
  .text-gray {
    color: #999;
  }
}
</style>


