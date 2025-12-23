<template>
  <div class="subscription-plans-page">
    <h1 class="page-title">订阅计划管理</h1>

    <div class="card">
      <div class="filter-bar">
        <el-input v-model="searchParams.keyword" placeholder="搜索计划名称" style="width: 300px" @keyup.enter="fetchPlans" />
        <el-select v-model="searchParams.status" placeholder="状态" clearable @change="fetchPlans" style="width: 150px">
          <el-option label="启用" :value="1" />
          <el-option label="禁用" :value="0" />
        </el-select>
        <el-button type="primary" @click="fetchPlans">搜索</el-button>
        <el-button type="success" @click="handleAdd">添加计划</el-button>
      </div>

      <el-table :data="plans" style="width: 100%">
        <el-table-column prop="name" label="计划名称" width="200" />
        <el-table-column prop="description" label="描述" />
        <el-table-column prop="price" label="价格" width="100">
          <template #default="{ row }">
            ${{ row.price }}
          </template>
        </el-table-column>
        <el-table-column prop="plan_type" label="周期" width="100">
          <template #default="{ row }">
            {{ getPlanTypeText(row.plan_type) }}
          </template>
        </el-table-column>
        <el-table-column prop="bottles_per_delivery" label="配送瓶数" width="100" />
        <el-table-column prop="stripe_price_id" label="Stripe Price ID" width="200">
          <template #default="{ row }">
            <el-tag v-if="row.stripe_price_id" type="success" size="small">{{ row.stripe_price_id }}</el-tag>
            <el-tag v-else type="danger" size="small">未配置</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button link type="danger" @click="handleDelete(row.id)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="searchParams.page"
          :total="total"
          :page-size="searchParams.per_page"
          layout="total, prev, pager, next"
          @current-change="fetchPlans"
        />
      </div>
    </div>

    <!-- 添加/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
      @close="resetForm"
    >
      <el-form :model="form" label-width="120px">
        <el-form-item label="计划名称">
          <el-input v-model="form.name" placeholder="如：3 Bottles Monthly" />
        </el-form-item>
        <el-form-item label="计划描述">
          <el-input v-model="form.description" type="textarea" :rows="3" placeholder="详细描述" />
        </el-form-item>
        <el-form-item label="计划图片">
          <el-upload
            class="avatar-uploader"
            :action="uploadUrl"
            :headers="{ Authorization: `Bearer ${adminStore.token}` }"
            :show-file-list="false"
            :on-success="handleImageSuccess"
            accept="image/*"
          >
            <img v-if="form.image" :src="getImageUrl(form.image)" class="avatar" />
            <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
          </el-upload>
        </el-form-item>
        <el-form-item label="价格">
          <el-input-number v-model="form.price" :min="0" :precision="2" :step="0.01" />
        </el-form-item>
        <el-form-item label="计划类型">
          <el-select v-model="form.plan_type" placeholder="请选择">
            <el-option label="月度订阅" value="monthly" />
            <el-option label="季度订阅" value="quarterly" />
            <el-option label="年度订阅" value="yearly" />
          </el-select>
        </el-form-item>
        <el-form-item label="配送瓶数">
          <el-input-number v-model="form.bottles_per_delivery" :min="1" />
        </el-form-item>
        <el-form-item label="Stripe Price ID">
          <el-input v-model="form.stripe_price_id" placeholder="price_xxxxxxxxxxxxx" />
          <div class="tip">在Stripe Dashboard创建产品后获取</div>
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="form.sort" :min="0" />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { useAdminStore } from '@/store/admin'
import { 
  getSubscriptionPlans, 
  createSubscriptionPlan, 
  updateSubscriptionPlan, 
  deleteSubscriptionPlan 
} from '@/api/admin/subscription'

const adminStore = useAdminStore()
const uploadUrl = 'http://localhost:8000/api/admin/upload'

const plans = ref([])
const total = ref(0)
const dialogVisible = ref(false)
const dialogTitle = ref('添加订阅计划')
const submitting = ref(false)
const editingId = ref(null)

const searchParams = reactive({
  keyword: '',
  status: null,
  page: 1,
  per_page: 15
})

const form = reactive({
  name: '',
  description: '',
  image: '',
  price: 29.99,
  plan_type: 'monthly',
  bottles_per_delivery: 3,
  stripe_price_id: '',
  sort: 0,
  status: 1
})

// 图片上传成功
const handleImageSuccess = (response) => {
  if (response.code === 200) {
    form.image = response.data.path
    ElMessage.success('图片上传成功')
  } else {
    ElMessage.error(response.message || '图片上传失败')
  }
}

// 获取图片URL
const getImageUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_API_BASE_URL}${path}`
}

onMounted(() => {
  fetchPlans()
})

const fetchPlans = async () => {
  try {
    const res = await getSubscriptionPlans(searchParams)
    plans.value = res.data.data.data || []
    total.value = res.data.data.total || 0
  } catch (error) {
    ElMessage.error('获取计划列表失败')
  }
}

const getPlanTypeText = (type) => {
  const types = {
    'monthly': '月度',
    'quarterly': '季度',
    'yearly': '年度'
  }
  return types[type] || type
}

const handleAdd = () => {
  dialogTitle.value = '添加订阅计划'
  editingId.value = null
  resetForm()
  dialogVisible.value = true
}

const handleEdit = (row) => {
  dialogTitle.value = '编辑订阅计划'
  editingId.value = row.id
  form.image = row.image || ''
  Object.assign(form, {
    name: row.name,
    description: row.description,
    price: row.price,
    plan_type: row.plan_type,
    bottles_per_delivery: row.bottles_per_delivery,
    stripe_price_id: row.stripe_price_id,
    sort: row.sort,
    status: row.status
  })
  dialogVisible.value = true
}

const handleSubmit = async () => {
  if (!form.name) {
    ElMessage.warning('请输入计划名称')
    return
  }

  submitting.value = true
  try {
    if (editingId.value) {
      await updateSubscriptionPlan(editingId.value, form)
      ElMessage.success('更新成功')
    } else {
      await createSubscriptionPlan(form)
      ElMessage.success('添加成功')
    }
    dialogVisible.value = false
    fetchPlans()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

const handleDelete = async (id) => {
  try {
    await ElMessageBox.confirm('确定要删除这个订阅计划吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    await deleteSubscriptionPlan(id)
    ElMessage.success('删除成功')
    fetchPlans()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.response?.data?.message || '删除失败')
    }
  }
}

const resetForm = () => {
  form.name = ''
  form.description = ''
  form.image = ''
  form.price = 29.99
  form.plan_type = 'monthly'
  form.bottles_per_delivery = 3
  form.stripe_price_id = ''
  form.sort = 0
  form.status = 1
}
</script>

<style scoped lang="scss">
.subscription-plans-page {
  .filter-bar {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
  }

  .pagination {
    margin-top: 20px;
    display: flex;
    justify-content: center;
  }

  .tip {
    font-size: 12px;
    color: #999;
    margin-left: 10px;
  }
}

.avatar-uploader {
  :deep(.el-upload) {
    border: 1px dashed var(--el-border-color);
    border-radius: 6px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: var(--el-transition-duration-fast);
  }

  :deep(.el-upload:hover) {
    border-color: var(--el-color-primary);
  }

  .avatar-uploader-icon {
    font-size: 28px;
    color: #8c939d;
    width: 178px;
    height: 178px;
    text-align: center;
    line-height: 178px;
  }

  .avatar {
    width: 178px;
    height: 178px;
    display: block;
    object-fit: cover;
  }
}
</style>


