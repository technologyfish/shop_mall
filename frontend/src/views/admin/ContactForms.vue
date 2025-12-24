<template>
  <div class="contact-forms-page">
    <el-card>
      <template #header>
        <div class="card-header">
          <h2>Contact表单提交记录</h2>
        </div>
      </template>

      <!-- 搜索栏 -->
      <el-form :inline="true" class="search-form">
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 120px">
            <el-option label="未处理" :value="0" />
            <el-option label="已处理" :value="1" />
          </el-select>
        </el-form-item>
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="姓名/邮箱/电话" clearable style="width: 200px" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <!-- 表格 -->
      <el-table :data="tableData" border v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="姓名" width="120" />
        <el-table-column prop="email" label="邮箱" width="200" />
        <el-table-column prop="phone" label="电话" width="150" />
        <el-table-column prop="message" label="留言内容" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '已处理' : '未处理' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="提交时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="handleView(row)">
              查看
            </el-button>
            <el-button 
              link 
              type="primary" 
              size="small" 
              @click="handleChangeStatus(row)"
              v-if="row.status === 0"
            >
              标记已处理
            </el-button>
            <el-button link type="danger" size="small" @click="handleDelete(row)">
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination
        v-model:current-page="pagination.current"
        v-model:page-size="pagination.pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :total="pagination.total"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="fetchData"
        @current-change="fetchData"
        class="pagination"
      />
    </el-card>

    <!-- 详情对话框 -->
    <el-dialog v-model="dialogVisible" title="表单详情" width="600px">
      <el-descriptions :column="1" border v-if="currentForm">
        <el-descriptions-item label="姓名">{{ currentForm.name }}</el-descriptions-item>
        <el-descriptions-item label="邮箱">{{ currentForm.email }}</el-descriptions-item>
        <el-descriptions-item label="电话">{{ currentForm.phone || '-' }}</el-descriptions-item>
        <el-descriptions-item label="留言内容">
          <div style="white-space: pre-wrap;">{{ currentForm.message }}</div>
        </el-descriptions-item>
        <el-descriptions-item label="IP地址">{{ currentForm.ip || '-' }}</el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="currentForm.status === 1 ? 'success' : 'info'">
            {{ currentForm.status === 1 ? '已处理' : '未处理' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="提交时间">{{ formatDate(currentForm.created_at) }}</el-descriptions-item>
      </el-descriptions>
      <template #footer>
        <el-button @click="dialogVisible = false">关闭</el-button>
        <el-button 
          v-if="currentForm && currentForm.status === 0" 
          type="primary" 
          @click="handleChangeStatusInDialog"
        >
          标记已处理
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getContactForms, updateFormStatus, deleteContactForm } from '@/api/admin/contact'
import { formatDate } from '@/utils/format'

const searchForm = reactive({
  status: null,
  keyword: ''
})

const tableData = ref([])
const loading = ref(false)

const pagination = reactive({
  current: 1,
  pageSize: 15,
  total: 0
})

const dialogVisible = ref(false)
const currentForm = ref(null)

// 获取数据
const fetchData = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.current,
      per_page: pagination.pageSize,
      status: searchForm.status,
      keyword: searchForm.keyword
    }
    
    const res = await getContactForms(params)
    const data = res.data.data
    
    tableData.value = data.data || []
    pagination.total = data.total || 0
  } catch (error) {
    ElMessage.error('获取数据失败')
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  pagination.current = 1
  fetchData()
}

// 重置
const handleReset = () => {
  searchForm.status = null
  searchForm.keyword = ''
  pagination.current = 1
  fetchData()
}

// 查看详情
const handleView = (row) => {
  currentForm.value = row
  dialogVisible.value = true
}

// 修改状态
const handleChangeStatus = async (row) => {
  try {
    await updateFormStatus(row.id, 1)
    ElMessage.success('状态更新成功')
    fetchData()
  } catch (error) {
    ElMessage.error('状态更新失败')
  }
}

// 在对话框中修改状态
const handleChangeStatusInDialog = async () => {
  await handleChangeStatus(currentForm.value)
  dialogVisible.value = false
}

// 删除
const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除这条记录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    
    await deleteContactForm(row.id)
    ElMessage.success('删除成功')
    fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped lang="scss">
.contact-forms-page {
  .card-header {
    h2 {
      margin: 0;
      font-size: 18px;
    }
  }

  .search-form {
    margin-bottom: 20px;
  }

  .pagination {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
  }
}
</style>





