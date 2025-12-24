<template>
  <div class="articles-container">
    <div class="page-header">
      <h2>文章管理</h2>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        添加文章
      </el-button>
    </div>

    <!-- 文章列表 -->
    <el-card class="table-card">
      <el-table :data="articles" v-loading="loading" border stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="image" label="图片" width="100">
          <template #default="{ row }">
            <el-image
              v-if="row.image"
              :src="getImageUrl(row.image)"
              :preview-src-list="[getImageUrl(row.image)]"
              fit="cover"
              style="width: 60px; height: 60px; border-radius: 4px"
            />
          </template>
        </el-table-column>
        <el-table-column prop="title" label="标题" min-width="200" show-overflow-tooltip />
        <el-table-column prop="subtitle" label="副标题" min-width="250" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status == 1 || row.status === true ? 'success' : 'info'">
              {{ row.status == 1 || row.status === true ? '发布' : '草稿' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="220" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button
              size="small"
              :type="row.status == 1 || row.status === true ? 'warning' : 'success'"
              @click="handleToggleStatus(row)"
            >
              {{ row.status == 1 || row.status === true ? '下架' : '发布' }}
            </el-button>
            <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[10, 20, 50]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="getArticles"
        @current-change="getArticles"
      />
    </el-card>

    <!-- 添加/编辑对话框 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="1000px" :close-on-click-modal="false">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" placeholder="请输入标题" />
        </el-form-item>
        
        <el-form-item label="副标题" prop="subtitle">
          <el-input v-model="form.subtitle" placeholder="请输入副标题" />
        </el-form-item>
        
        <el-form-item label="封面图片" prop="image">
          <ImageUpload v-model="form.image" tip="建议尺寸：800x600，支持jpg、png、gif、webp，最大5MB" />
        </el-form-item>

        <el-form-item label="内容" prop="content">
          <RichTextEditor 
            v-model="form.content"
            placeholder="请输入文章内容..."
            height="600px"
          />
        </el-form-item>

        <el-form-item label="状态" prop="status">
          <el-switch
            v-model="form.status"
            :active-value="1"
            :inactive-value="0"
            active-text="发布"
            inactive-text="草稿"
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
import { getImageUrl } from '@/utils/image'
import axios from '@/utils/request'
import ImageUpload from '@/components/ImageUpload.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { formatDate } from '@/utils/format'

const loading = ref(false)
const submitting = ref(false)
const articles = ref([])

const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0
})

const dialogVisible = ref(false)
const dialogTitle = ref('添加文章')
const formRef = ref(null)
const form = reactive({
  id: null,
  title: '',
  subtitle: '',
  image: '',
  content: '',
  status: 1
})

const rules = {
  title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
  subtitle: [{ required: true, message: '请输入副标题', trigger: 'blur' }],
  image: [{ required: true, message: '请上传封面图片', trigger: 'change' }],
  content: [{ required: true, message: '请输入内容', trigger: 'blur' }]
}

const getArticles = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/admin/articles', {
      params: {
        page: pagination.page,
        limit: pagination.limit
      }
    })
    articles.value = data.data.data || []
    pagination.total = data.data.total || 0
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '获取文章列表失败')
  } finally {
    loading.value = false
  }
}

const handleAdd = () => {
  dialogTitle.value = '添加文章'
  Object.assign(form, {
    id: null,
    title: '',
    subtitle: '',
    image: '',
    content: '',
    status: 1
  })
  dialogVisible.value = true
}

const handleEdit = (row) => {
  dialogTitle.value = '编辑文章'
  Object.assign(form, {
    id: row.id,
    title: row.title,
    subtitle: row.subtitle,
    image: row.image,
    content: row.content,
    status: row.status
  })
  dialogVisible.value = true
}

const handleSubmit = async () => {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitting.value = true
  try {
    // 构建提交数据，自动生成slug，默认type为article
    const submitData = {
      title: form.title,
      subtitle: form.subtitle,
      slug: form.title.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, ''),
      image: form.image,
      content: form.content,
      type: 'article',
      sort: 100,
      status: form.status
    }

    if (form.id) {
      await axios.put(`/api/admin/articles/${form.id}`, submitData)
      ElMessage.success('更新成功')
    } else {
      await axios.post('/api/admin/articles', submitData)
      ElMessage.success('添加成功')
    }
    dialogVisible.value = false
    getArticles()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

const handleToggleStatus = async (row) => {
  const currentStatus = row.status === true || row.status == 1
  const newStatus = currentStatus ? 0 : 1
  try {
    await axios.put(`/api/admin/articles/${row.id}`, { 
      title: row.title,
      subtitle: row.subtitle,
      slug: row.slug,
      image: row.image,
      content: row.content,
      type: row.type || 'article',
      sort: row.sort || 100,
      status: newStatus 
    })
    ElMessage.success('状态更新成功')
    getArticles()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败')
  }
}

const handleDelete = (row) => {
  ElMessageBox.confirm('确定要删除这篇文章吗？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      await axios.delete(`/api/admin/articles/${row.id}`)
      ElMessage.success('删除成功')
      getArticles()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '删除失败')
    }
  })
}

onMounted(() => {
  getArticles()
})
</script>

<style scoped lang="scss">
.articles-container {
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

.table-card {
  .el-pagination {
    margin-top: 20px;
    justify-content: flex-end;
  }
}
</style>
