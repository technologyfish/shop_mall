<template>
  <div class="mail-transfer-forms-page">
    <h1 class="page-title">MailTransfer收集</h1>

    <div class="card">
      <div class="filter-bar">
        <el-input v-model="searchParams.keyword" placeholder="搜索昵称/邮箱" style="width: 300px" @keyup.enter="fetchForms" />
        <el-button type="primary" @click="fetchForms">搜索</el-button>
      </div>

      <el-table :data="forms" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="nickname" label="昵称" width="150" />
        <el-table-column prop="email" label="邮箱" width="250" />
        <el-table-column prop="off_code" label="Off Code" width="150">
          <template #default="{ row }">
            <el-tag v-if="row.off_code" type="success" size="small">{{ row.off_code }}</el-tag>
            <span v-else class="text-gray">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="提交时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="100" fixed="right">
          <template #default="{ row }">
            <el-button text type="danger" @click="handleDelete(row.id)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="searchParams.page"
          :total="total"
          :page-size="searchParams.per_page"
          layout="total, prev, pager, next"
          @current-change="fetchForms"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import axios from 'axios'
import { formatDate } from '@/utils/format'

const forms = ref([])
const total = ref(0)

const searchParams = reactive({
  keyword: '',
  page: 1,
  per_page: 15
})

onMounted(() => {
  fetchForms()
})

const fetchForms = async () => {
  try {
    const token = localStorage.getItem('admin_token')
    const res = await axios.get('http://localhost:8000/api/admin/mail-transfer/forms', {
      params: searchParams,
      headers: { Authorization: `Bearer ${token}` }
    })
    forms.value = res.data.data.data || []
    total.value = res.data.data.total || 0
  } catch (error) {
    ElMessage.error('获取表单列表失败')
  }
}

// formatDate 已从 @/utils/format 导入

const handleDelete = async (id) => {
  try {
    await ElMessageBox.confirm('确定要删除这条记录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    
    const token = localStorage.getItem('admin_token')
    await axios.delete(`http://localhost:8000/api/admin/mail-transfer/forms/${id}`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    
    ElMessage.success('删除成功')
    fetchForms()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.response?.data?.message || '删除失败')
    }
  }
}
</script>

<style scoped lang="scss">
.mail-transfer-forms-page {
  padding: 20px;

  .page-title {
    font-size: 24px;
    margin-bottom: 20px;
  }

  .card {
    background: white;
    padding: 20px;
    border-radius: 4px;

    .filter-bar {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .pagination {
      margin-top: 20px;
      display: flex;
      justify-content: center;
    }
  }
  
  .text-gray {
    color: #999;
  }
}
</style>



