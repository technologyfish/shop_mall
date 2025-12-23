<template>
  <div class="promotions-page">
    <h1 class="page-title">促销活动管理</h1>

    <div class="card">
      <div class="filter-bar">
        <el-input v-model="searchParams.keyword" placeholder="搜索活动名称" style="width: 300px" @keyup.enter="fetchPromotions" />
        <el-select v-model="searchParams.status" placeholder="状态" clearable @change="fetchPromotions" style="width: 150px">
          <el-option label="启用" :value="1" />
          <el-option label="禁用" :value="0" />
        </el-select>
        <el-button type="primary" @click="fetchPromotions">搜索</el-button>
        <el-button type="success" @click="handleAdd">添加活动</el-button>
      </div>

      <el-table :data="promotions" style="width: 100%">
        <el-table-column prop="name" label="活动名称" width="200" />
        <el-table-column prop="discount_value" label="折扣值" width="120">
          <template #default="{ row }">
            {{ row.discount_value }}%
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
            <el-button 
              v-if="row.id !== 1" 
              link 
              type="danger" 
              @click="handleDelete(row.id)"
            >
              删除
            </el-button>
            <el-tag v-else size="small" type="info">系统保留</el-tag>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="searchParams.page"
          :total="total"
          :page-size="searchParams.per_page"
          layout="total, prev, pager, next"
          @current-change="fetchPromotions"
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
      <el-form :model="form" label-width="140px">
        <el-form-item label="活动名称">
          <el-input v-model="form.name" placeholder="如：首单折扣" />
        </el-form-item>
        <el-form-item label="折扣值">
          <el-input-number v-model="form.discount_value" :min="0" :max="100" :precision="2" />
          <span class="tip">（百分比，如10表示10%折扣）</span>
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
import axios from 'axios'

const promotions = ref([])
const total = ref(0)
const dialogVisible = ref(false)
const dialogTitle = ref('添加促销活动')
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
  discount_type: 'percentage',
  discount_value: 10,
  status: 1
})

onMounted(() => {
  fetchPromotions()
})

const fetchPromotions = async () => {
  try {
    const token = localStorage.getItem('admin_token')
    const res = await axios.get('http://localhost:8000/api/admin/promotions', {
      params: searchParams,
      headers: { Authorization: `Bearer ${token}` }
    })
    promotions.value = res.data.data.data || []
    total.value = res.data.data.total || 0
  } catch (error) {
    ElMessage.error('获取促销活动列表失败')
  }
}

const getUserTypeText = (type) => {
  const types = {
    'all': '所有用户',
    'registered': '注册用户',
    'unregistered': '未注册用户'
  }
  return types[type] || type
}

const handleAdd = () => {
  dialogTitle.value = '添加促销活动'
  editingId.value = null
  resetForm()
  dialogVisible.value = true
}

const handleEdit = (row) => {
  dialogTitle.value = '编辑促销活动'
  editingId.value = row.id
  Object.keys(form).forEach(key => {
    if (row[key] !== undefined) {
      form[key] = row[key]
    }
  })
  dialogVisible.value = true
}

const handleSubmit = async () => {
  if (!form.name) {
    ElMessage.warning('请输入活动名称')
    return
  }

  submitting.value = true
  try {
    const token = localStorage.getItem('admin_token')
    const url = editingId.value
      ? `http://localhost:8000/api/admin/promotions/${editingId.value}`
      : 'http://localhost:8000/api/admin/promotions'
    
    const method = editingId.value ? 'put' : 'post'
    
    await axios[method](url, form, {
      headers: { Authorization: `Bearer ${token}` }
    })
    
    ElMessage.success(editingId.value ? '更新成功' : '添加成功')
    dialogVisible.value = false
    fetchPromotions()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

const handleDelete = async (id) => {
  try {
    await ElMessageBox.confirm('确定要删除这个促销活动吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    
    const token = localStorage.getItem('admin_token')
    await axios.delete(`http://localhost:8000/api/admin/promotions/${id}`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    
    ElMessage.success('删除成功')
    fetchPromotions()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.response?.data?.message || '删除失败')
    }
  }
}

const resetForm = () => {
  form.name = ''
  form.discount_type = 'percentage'
  form.discount_value = 10
  form.status = 1
}
</script>

<style scoped lang="scss">
.promotions-page {
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
</style>
