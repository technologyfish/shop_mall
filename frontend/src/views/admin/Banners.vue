<template>
  <div class="banners-container">
    <div class="page-header">
      <h2>Banner管理</h2>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        添加Banner
      </el-button>
    </div>

    <!-- Banner列表 -->
    <el-card class="table-card">
      <el-table :data="banners" v-loading="loading" border stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="image" label="图片" width="150">
          <template #default="{ row }">
            <el-image
              v-if="row.image"
              :src="getImageUrl(row.image)"
              :preview-src-list="[getImageUrl(row.image)]"
              fit="cover"
              style="width: 120px; height: 60px; border-radius: 4px"
            />
          </template>
        </el-table-column>
        <el-table-column prop="title" label="标题" min-width="150" />
        <el-table-column prop="subtitle" label="副标题" min-width="200" show-overflow-tooltip />
        <el-table-column prop="button_text" label="按钮文字" width="120" />
        <el-table-column prop="link" label="链接" width="120" show-overflow-tooltip />
        <el-table-column prop="sort" label="排序" width="100" sortable />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
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

      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[10, 20, 50]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="getBanners"
        @current-change="getBanners"
      />
    </el-card>

    <!-- 添加/编辑对话框 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="800px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="Banner图片" prop="image">
          <ImageUpload v-model="form.image" tip="建议尺寸：1920x600，支持jpg、png、gif、webp，最大5MB" />
        </el-form-item>
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" placeholder="请输入标题" />
        </el-form-item>
        <el-form-item label="副标题" prop="subtitle">
          <el-input
            v-model="form.subtitle"
            type="textarea"
            :rows="2"
            placeholder="请输入副标题"
          />
        </el-form-item>
        <el-form-item label="链接" prop="link">
          <el-input v-model="form.link" placeholder="请输入跳转链接，如：/shop" />
        </el-form-item>
        <el-form-item label="按钮文字" prop="button_text">
          <el-input v-model="form.button_text" placeholder="请输入按钮文字，如：SHOP NOW" />
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
import { getImageUrl } from '@/utils/image'
import axios from '@/utils/request'
import ImageUpload from '@/components/ImageUpload.vue'

const loading = ref(false)
const submitting = ref(false)
const banners = ref([])

const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0
})

const dialogVisible = ref(false)
const dialogTitle = ref('添加Banner')
const formRef = ref(null)
const form = reactive({
  id: null,
  title: '',
  subtitle: '',
  image: '',
  link: '',
  button_text: '',
  sort: 100,
  status: 1
})

const rules = {
  title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
  image: [{ required: true, message: '请上传Banner图片', trigger: 'change' }]
}

const getBanners = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/admin/banners', {
      params: {
        page: pagination.page,
        limit: pagination.limit
      }
    })
    banners.value = data.data.data || []
    pagination.total = data.data.total || 0
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '获取Banner列表失败')
  } finally {
    loading.value = false
  }
}

const handleAdd = () => {
  dialogTitle.value = '添加Banner'
  Object.assign(form, {
    id: null,
    title: '',
    subtitle: '',
    image: '',
    link: '/shop',
    button_text: 'SHOP NOW',
    sort: 100,
    status: 1
  })
  dialogVisible.value = true
}

const handleEdit = (row) => {
  dialogTitle.value = '编辑Banner'
  Object.assign(form, row)
  dialogVisible.value = true
}

const handleSubmit = async () => {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitting.value = true
  try {
    const data = { ...form, position: 'home' } // 保留position字段给后端
    if (form.id) {
      await axios.put(`/api/admin/banners/${form.id}`, data)
      ElMessage.success('更新成功')
    } else {
      await axios.post('/api/admin/banners', data)
      ElMessage.success('添加成功')
    }
    dialogVisible.value = false
    getBanners()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

const handleToggleStatus = async (row) => {
  const oldStatus = row.status
  const newStatus = Number(row.status) === 1 ? 0 : 1
  try {
    // 先更新本地显示
    row.status = newStatus
    // 调用API更新
    await axios.put(`/api/admin/banners/${row.id}`, {
      ...row,
      status: newStatus
    })
    ElMessage.success('状态更新成功')
    // 重新获取列表确保一致性
    await getBanners()
  } catch (error) {
    // 失败则恢复原状态
    row.status = oldStatus
    ElMessage.error(error.response?.data?.message || '操作失败')
  }
}

const handleDelete = (row) => {
  ElMessageBox.confirm('确定要删除这个Banner吗？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      await axios.delete(`/api/admin/banners/${row.id}`)
      ElMessage.success('删除成功')
      getBanners()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '删除失败')
    }
  })
}

onMounted(() => {
  getBanners()
})
</script>

<style scoped lang="scss">
.banners-container {
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

.form-tip {
  margin-left: 10px;
  font-size: 12px;
  color: #909399;
}
</style>
<style>
.image-upload .image-preview .image-actions .el-icon{
  font-size: 42px !important;
}
</style>
