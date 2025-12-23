<template>
  <div class="admin-photos-page">
    <h1 class="page-title">Photo管理</h1>

    <div class="card">
      <div class="filter-bar">
        <el-input v-model="searchParams.keyword" placeholder="搜索照片名称/描述" style="width: 300px" @keyup.enter="fetchPhotos" />
        <el-select v-model="searchParams.status" placeholder="状态" clearable @change="fetchPhotos" style="width: 150px">
          <el-option label="启用" :value="1" />
          <el-option label="禁用" :value="0" />
        </el-select>
        <el-button type="primary" @click="fetchPhotos">搜索</el-button>
        <el-button type="success" @click="handleAdd">添加照片</el-button>
      </div>

      <el-table :data="photos" style="width: 100%">
        <el-table-column label="图片" width="120">
          <template #default="{ row }">
            <img :src="getImageUrl(row.image)" :alt="row.name" class="photo-thumb" />
          </template>
        </el-table-column>
        <el-table-column prop="name" label="名称" width="200" />
        <el-table-column prop="description" label="描述" />
        <el-table-column prop="sort" label="排序" width="80" />
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="添加时间" width="180">
          <template #default="{ row }">{{ formatDate(row.created_at) }}</template>
        </el-table-column>
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
          @current-change="fetchPhotos"
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
      <el-form :model="form" label-width="100px">
        <el-form-item label="照片名称">
          <el-input v-model="form.name" placeholder="请输入照片名称" />
        </el-form-item>
        <el-form-item label="照片图片">
          <el-upload
            class="upload-demo"
            :action="uploadUrl"
            :headers="uploadHeaders"
            :on-success="handleUploadSuccess"
            :before-upload="beforeUpload"
            :show-file-list="false"
            accept="image/*"
          >
            <div v-if="form.image" class="preview-image">
              <img :src="getImageUrl(form.image)" alt="preview" />
              <div class="image-overlay">
                <span>点击替换图片</span>
              </div>
            </div>
            <el-button v-else type="primary">点击上传</el-button>
          </el-upload>
        </el-form-item>
        <el-form-item label="照片描述">
          <el-input v-model="form.description" type="textarea" :rows="4" placeholder="请输入照片描述" />
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
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getPhotos, addPhoto, updatePhoto, deletePhoto } from '@/api/admin/photo'
import { useAdminStore } from '@/store/admin'

const adminStore = useAdminStore()
const photos = ref([])
const total = ref(0)
const dialogVisible = ref(false)
const dialogTitle = ref('添加照片')
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
  image: '',
  description: '',
  sort: 0,
  status: 1
})

const uploadUrl = computed(() => {
  return `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}/api/admin/upload`
})

const uploadHeaders = computed(() => {
  return {
    'Authorization': `Bearer ${adminStore.token}`
  }
})

onMounted(() => {
  fetchPhotos()
})

const fetchPhotos = async () => {
  try {
    const res = await getPhotos(searchParams)
    photos.value = res.data.data.data || []
    total.value = res.data.data.total || 0
  } catch (error) {
    ElMessage.error('获取照片列表失败')
  }
}

const getImageUrl = (path) => {
  if (!path) return 'https://via.placeholder.com/100'
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}${path}`
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

const handleAdd = () => {
  dialogTitle.value = '添加照片'
  editingId.value = null
  resetForm()
  dialogVisible.value = true
}

const handleEdit = (row) => {
  dialogTitle.value = '编辑照片'
  editingId.value = row.id
  Object.assign(form, {
    name: row.name,
    image: row.image,
    description: row.description,
    sort: row.sort,
    status: row.status
  })
  dialogVisible.value = true
}

const handleUploadSuccess = (response) => {
  // 兼容不同的响应格式
  if (response.code === 0 || response.code === 200 || response.url) {
    form.image = response.data?.url || response.url
    ElMessage.success('Upload successful')
  } else {
    ElMessage.error(response.message || 'Upload failed')
  }
}

const beforeUpload = (file) => {
  const isImage = file.type.startsWith('image/')
  const isLt2M = file.size / 1024 / 1024 < 2

  if (!isImage) {
    ElMessage.error('只能上传图片文件!')
    return false
  }
  if (!isLt2M) {
    ElMessage.error('图片大小不能超过 2MB!')
    return false
  }
  return true
}

const handleSubmit = async () => {
  if (!form.name) {
    ElMessage.warning('请输入照片名称')
    return
  }
  if (!form.image) {
    ElMessage.warning('请上传照片图片')
    return
  }

  submitting.value = true
  try {
    if (editingId.value) {
      await updatePhoto(editingId.value, form)
      ElMessage.success('更新成功')
    } else {
      await addPhoto(form)
      ElMessage.success('添加成功')
    }
    dialogVisible.value = false
    fetchPhotos()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

const handleDelete = async (id) => {
  try {
    await ElMessageBox.confirm('确定要删除这张照片吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    await deletePhoto(id)
    ElMessage.success('删除成功')
    fetchPhotos()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.response?.data?.message || '删除失败')
    }
  }
}

const resetForm = () => {
  form.name = ''
  form.image = ''
  form.description = ''
  form.sort = 0
  form.status = 1
}
</script>

<style scoped lang="scss">
.admin-photos-page {
  .filter-bar {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
  }

  .photo-thumb {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #eee;
  }

  .preview-image {
    position: relative;
    width: 200px;
    height: 200px;
    cursor: pointer;
    border-radius: 4px;
    overflow: hidden;
    border: 1px solid #eee;

    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .image-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s;

      span {
        color: #fff;
        font-size: 14px;
      }
    }

    &:hover .image-overlay {
      opacity: 1;
    }
  }

  .pagination {
    margin-top: 20px;
    display: flex;
    justify-content: center;
  }
}
</style>




