<template>
  <div class="announcements-container">
    <div class="page-header">
      <h2>公告管理</h2>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        添加公告
      </el-button>
    </div>

    <!-- 搜索筛选 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input
            v-model="searchForm.keyword"
            placeholder="搜索公告内容"
            clearable
            @clear="handleSearch"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable>
            <el-option label="启用" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 公告列表 -->
    <el-card class="table-card">
      <el-table
        :data="announcements"
        v-loading="loading"
        border
        stripe
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="content" label="公告内容" min-width="300" />
        <el-table-column prop="link" label="链接" width="150" />
        <el-table-column prop="sort" label="排序" width="100" sortable />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button
              size="small"
              :type="row.status === 1 ? 'warning' : 'success'"
              @click="handleToggleStatus(row)"
            >
              {{ row.status === 1 ? '禁用' : '启用' }}
            </el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSearch"
        @current-change="handleSearch"
      />
    </el-card>

    <!-- 添加/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="100px"
      >
        <el-form-item label="公告内容" prop="content">
          <el-input
            v-model="form.content"
            type="textarea"
            :rows="4"
            placeholder="请输入公告内容"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
        <el-form-item label="链接" prop="link">
          <el-input
            v-model="form.link"
            placeholder="请输入链接地址（可选）"
          />
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number
            v-model="form.sort"
            :min="0"
            :max="999"
            controls-position="right"
          />
          <span class="form-tip">数字越大越靠前</span>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-switch
            v-model="form.status"
            :active-value="1"
            :inactive-value="0"
            active-text="启用"
            inactive-text="禁用"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import axios from '@/utils/request'
import { formatDate } from '@/utils/format'

const loading = ref(false)
const submitting = ref(false)
const announcements = ref([])

const searchForm = reactive({
  keyword: '',
  status: null
})

const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0
})

const dialogVisible = ref(false)
const dialogTitle = ref('添加公告')
const formRef = ref(null)
const form = reactive({
  id: null,
  content: '',
  link: '',
  sort: 100,
  status: 1
})

const rules = {
  content: [
    { required: true, message: '请输入公告内容', trigger: 'blur' },
    { min: 1, max: 500, message: '长度在 1 到 500 个字符', trigger: 'blur' }
  ]
}

// 获取公告列表
const getAnnouncements = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/admin/announcements', {
      params: {
        page: pagination.page,
        limit: pagination.limit,
        keyword: searchForm.keyword || undefined,
        status: searchForm.status !== null ? searchForm.status : undefined
      }
    })
    announcements.value = data.data.data || []
    pagination.total = data.data.total || 0
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '获取公告列表失败')
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  pagination.page = 1
  getAnnouncements()
}

// 重置
const handleReset = () => {
  searchForm.keyword = ''
  searchForm.status = null
  handleSearch()
}

// 添加
const handleAdd = () => {
  dialogTitle.value = '添加公告'
  Object.assign(form, {
    id: null,
    content: '',
    link: '',
    sort: 100,
    status: 1
  })
  dialogVisible.value = true
}

// 编辑
const handleEdit = (row) => {
  dialogTitle.value = '编辑公告'
  Object.assign(form, {
    id: row.id,
    content: row.content,
    link: row.link,
    sort: row.sort,
    status: row.status
  })
  dialogVisible.value = true
}

// 提交
const handleSubmit = async () => {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitting.value = true
  try {
    if (form.id) {
      await axios.put(`/api/admin/announcements/${form.id}`, form)
      ElMessage.success('更新成功')
    } else {
      await axios.post('/api/admin/announcements', form)
      ElMessage.success('添加成功')
    }
    dialogVisible.value = false
    getAnnouncements()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

// 切换状态
const handleToggleStatus = async (row) => {
  const newStatus = Number(row.status) === 1 ? 0 : 1
  try {
    await axios.put(`/api/admin/announcements/${row.id}`, {
      content: row.content,
      link: row.link || '',
      sort: row.sort || 100,
      status: newStatus
    })
    ElMessage.success('状态更新成功')
    getAnnouncements()
  } catch (error) {
    console.error('Toggle status error:', error)
    ElMessage.error(error.response?.data?.message || '操作失败')
  }
}

// 删除
const handleDelete = (row) => {
  ElMessageBox.confirm('确定要删除这条公告吗？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      await axios.delete(`/api/admin/announcements/${row.id}`)
      ElMessage.success('删除成功')
      getAnnouncements()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '删除失败')
    }
  })
}

onMounted(() => {
  getAnnouncements()
})
</script>

<style scoped lang="scss">
.announcements-container {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;

  h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
  }
}

.search-card {
  margin-bottom: 20px;
}

.table-card {
  .el-pagination {
    margin-top: 20px;
    justify-content: flex-end;
  }
}

.form-tip {
  margin-left: 10px;
  font-size: 12px;
  color: #909399;
}
</style>

