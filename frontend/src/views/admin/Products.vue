<template>
  <div class="products-container">
    <div class="page-header">
      <h2>商品管理</h2>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        添加商品
      </el-button>
    </div>

    <!-- 搜索筛选 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="商品名称">
          <el-input
            v-model="searchForm.keyword"
            placeholder="搜索商品名称"
            clearable
            @clear="handleSearch"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable>
            <el-option label="上架" :value="1" />
            <el-option label="下架" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item label="精选">
          <el-select v-model="searchForm.is_featured" placeholder="全部" clearable>
            <el-option label="是" :value="1" />
            <el-option label="否" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 商品列表 -->
    <el-card class="table-card">
      <el-table
        :data="products"
        v-loading="loading"
        border
        stripe
      >
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
        <el-table-column prop="name" label="商品名称" min-width="200" show-overflow-tooltip />
        <el-table-column prop="price" label="价格" width="100">
          <template #default="{ row }">
            £{{ row.price }}
          </template>
        </el-table-column>
        <el-table-column prop="original_price" label="原价" width="100">
          <template #default="{ row }">
            <span v-if="row.original_price">£{{ row.original_price }}</span>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column prop="stock" label="库存" width="80" />
        <el-table-column prop="sales" label="销量" width="80" />
        <el-table-column label="标签" width="150">
          <template #default="{ row }">
            <el-tag v-if="row.is_featured" type="warning" size="small">精选</el-tag>
            <el-tag v-if="row.is_new" type="success" size="small" style="margin-left: 4px">新品</el-tag>
            <span v-if="!row.is_featured && !row.is_new" style="color: #999">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="80" sortable />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status ? 'success' : 'danger'">
              {{ row.status ? '上架' : '下架' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="220" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button
              size="small"
              :type="row.status ? 'warning' : 'success'"
              @click="handleToggleStatus(row)"
            >
              {{ row.status ? '下架' : '上架' }}
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
      width="800px"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="120px"
        label-position="top"
      >
        <el-form-item label="商品名称" prop="name">
          <el-input
            v-model="form.name"
            placeholder="请输入商品名称"
          />
        </el-form-item>

        <el-form-item label="Slug" prop="slug">
          <el-input
            v-model="form.slug"
            placeholder="URL友好名称"
          />
        </el-form-item>

        <el-form-item label="商品图片" prop="images">
          <div class="image-upload-container">
            <div class="upload-tip">可上传多张图片，第一张为主图</div>
            <el-upload
              v-model:file-list="imageList"
              :action="uploadUrl"
              :headers="uploadHeaders"
              list-type="picture-card"
              :on-success="handleImageSuccess"
              :on-remove="handleImageRemove"
              :limit="5"
              accept="image/*"
              multiple
            >
              <el-icon><Plus /></el-icon>
            </el-upload>
          </div>
        </el-form-item>

        <el-form-item label="商品描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="3"
            placeholder="请输入商品描述"
          />
        </el-form-item>

        <el-form-item label="详细内容" prop="content">
          <el-input
            v-model="form.content"
            type="textarea"
            :rows="4"
            placeholder="支持HTML格式"
          />
        </el-form-item>

        <el-form-item label="价格" prop="price">
          <el-input-number
            v-model="form.price"
            :min="0"
            :precision="2"
            :step="0.1"
            controls-position="right"
            style="width: 200px"
          />
        </el-form-item>

        <el-form-item label="原价" prop="original_price">
          <el-input-number
            v-model="form.original_price"
            :min="0"
            :precision="2"
            :step="0.1"
            controls-position="right"
            style="width: 200px"
          />
        </el-form-item>

        <el-form-item label="库存" prop="stock">
          <el-input-number
            v-model="form.stock"
            :min="0"
            controls-position="right"
            style="width: 200px"
          />
        </el-form-item>

        <el-form-item label="排序" prop="sort">
          <el-input-number
            v-model="form.sort"
            :min="0"
            :max="999"
            controls-position="right"
            style="width: 200px"
          />
        </el-form-item>

        <el-form-item label="销量" prop="sales">
          <el-input-number
            v-model="form.sales"
            :min="0"
            controls-position="right"
            style="width: 200px"
          />
        </el-form-item>

        <el-form-item label="商品状态" prop="status" required>
          <el-radio-group v-model="form.status">
            <el-radio :label="1">上架</el-radio>
            <el-radio :label="0">下架</el-radio>
          </el-radio-group>
        </el-form-item>

        <el-form-item label="商品标签">
          <el-checkbox-group v-model="form.tags">
            <el-checkbox label="featured">精选商品</el-checkbox>
            <el-checkbox label="new">新品</el-checkbox>
          </el-checkbox-group>
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
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import axios from '@/utils/request'
import ImageUpload from '@/components/ImageUpload.vue'

// API 基础地址
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || ''

// 获取完整图片 URL
const getImageUrl = (path) => {
  if (!path) return ''
  if (path.startsWith('http')) return path
  return `${apiBaseUrl}${path}`
}

const loading = ref(false)
const submitting = ref(false)
const products = ref([])

const searchForm = reactive({
  keyword: '',
  status: null,
  is_featured: null
})

const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0
})

const dialogVisible = ref(false)
const dialogTitle = ref('添加商品')
const formRef = ref(null)
const form = reactive({
  id: null,
  name: '',
  slug: '',
  description: '',
  content: '',
  image: '',
  images: [],
  price: 0,
  original_price: null,
  stock: 100,
  sales: 0,
  tags: [], // featured, new
  is_featured: 0,
  is_new: 0,
  sort: 100,
  status: 1
})

// 图片列表
const imageList = ref([])

// 上传配置
const uploadUrl = computed(() => `${apiBaseUrl}/api/admin/upload`)
const uploadHeaders = ref({
  Authorization: 'Bearer ' + localStorage.getItem('admin_token')
})

const rules = {
  name: [
    { required: true, message: '请输入商品名称', trigger: 'blur' }
  ],
  slug: [
    { required: true, message: '请输入Slug', trigger: 'blur' }
  ],
  image: [
    { required: true, message: '请上传图片', trigger: 'change' }
  ],
  price: [
    { required: true, message: '请输入价格', trigger: 'blur' }
  ]
}

// 获取商品列表
const getProducts = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/admin/products', {
      params: {
        page: pagination.page,
        limit: pagination.limit,
        keyword: searchForm.keyword || undefined,
        status: searchForm.status !== null ? searchForm.status : undefined,
        is_featured: searchForm.is_featured !== null ? searchForm.is_featured : undefined
      }
    })
    products.value = data.data.data || []
    pagination.total = data.data.total || 0
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '获取商品列表失败')
  } finally {
    loading.value = false
  }
}

// 搜索
const handleSearch = () => {
  pagination.page = 1
  getProducts()
}

// 重置
const handleReset = () => {
  searchForm.keyword = ''
  searchForm.status = null
  searchForm.is_featured = null
  handleSearch()
}

// 添加
const handleAdd = () => {
  dialogTitle.value = '添加商品'
  Object.assign(form, {
    id: null,
    name: '',
    slug: '',
    description: '',
    content: '',
    image: '',
    images: [],
    price: 5.99,
    original_price: null,
    stock: 100,
    sales: 0,
    tags: [],
    is_featured: 0,
    is_new: 0,
    sort: 100,
    status: 1
  })
  imageList.value = []
  dialogVisible.value = true
}

// 编辑
const handleEdit = (row) => {
  dialogTitle.value = '编辑商品'
  
  // 判断标签
  const tags = []
  if (row.is_featured) tags.push('featured')
  if (row.is_new) tags.push('new')
  
  Object.assign(form, {
    id: row.id,
    name: row.name,
    slug: row.slug,
    description: row.description,
    content: row.content,
    image: row.image,
    images: row.images || [],
    price: row.price,
    original_price: row.original_price,
    stock: row.stock,
    sales: row.sales,
    tags: tags,
    is_featured: row.is_featured,
    is_new: row.is_new,
    sort: row.sort,
    status: row.status
  })
  
  // 设置图片列表
  if (row.images && Array.isArray(row.images) && row.images.length > 0) {
    imageList.value = row.images.map((url, index) => {
      // 如果URL是相对路径，需要转换为完整URL用于显示
      let displayUrl = url
      if (url && !url.startsWith('http')) {
        displayUrl = getImageUrl(url)
      }
      return {
        uid: Date.now() + index,
        name: `image-${index}`,
        status: 'success',
        url: displayUrl,
        // 保存原始相对路径，用于提交时使用
        _originalUrl: url
      }
    })
  } else {
    imageList.value = []
  }
  
  dialogVisible.value = true
}

// 图片上传成功
const handleImageSuccess = (response, file, fileList) => {
  if (response.code === 200) {
    // 更新图片数组
    updateImagesFromList(fileList)
    // 不要显示成功消息，el-upload会自动处理
  } else {
    ElMessage.error(response.message || '图片上传失败')
  }
}

// 图片删除
const handleImageRemove = (file, fileList) => {
  updateImagesFromList(fileList)
}

// 从文件列表更新图片数组
const updateImagesFromList = (fileList) => {
  form.images = fileList.map(file => {
    let url = ''
    
    // 优先使用原始URL（编辑时保存的相对路径）
    if (file._originalUrl) {
      url = file._originalUrl
    }
    // 新上传的图片，从response获取
    else if (file.response && file.response.data && file.response.data.url) {
      url = file.response.data.url
    } 
    // 已存在的图片，从file.url获取
    else if (file.url) {
      url = file.url
    }
    
    // 去掉URL中可能存在的域名部分，只保留路径
    if (url) {
      // 如果URL包含http://或https://，提取路径部分
      if (url.includes('http://') || url.includes('https://')) {
        try {
          const urlObj = new URL(url)
          url = urlObj.pathname
        } catch (e) {
          // 如果解析失败，尝试简单的字符串处理
          const match = url.match(/\/uploads\/.*/)
          if (match) {
            url = match[0]
          }
        }
      }
    }
    
    return url
  }).filter(url => url)
  
  // 设置第一张为主图
  form.image = form.images[0] || ''
  
  console.log('Updated images:', form.images)
}

// 提交
const handleSubmit = async () => {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  // 准备提交数据
  const submitData = { ...form }
  
  // 根据标签设置标识
  submitData.is_featured = form.tags.includes('featured') ? 1 : 0
  submitData.is_new = form.tags.includes('new') ? 1 : 0
  
  // 确保status是数字类型
  submitData.status = submitData.status ? 1 : 0

  submitting.value = true
  try {
    if (form.id) {
      await axios.put(`/api/admin/products/${form.id}`, submitData)
      ElMessage.success('更新成功')
    } else {
      await axios.post('/api/admin/products', submitData)
      ElMessage.success('添加成功')
    }
    dialogVisible.value = false
    getProducts()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

// 切换状态
const handleToggleStatus = async (row) => {
  const newStatus = row.status ? 0 : 1
  try {
    await axios.put(`/api/admin/products/${row.id}/status`, {
      status: newStatus
    })
    ElMessage.success('状态更新成功')
    getProducts()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败')
  }
}

// 删除
const handleDelete = (row) => {
  ElMessageBox.confirm('确定要删除这个商品吗？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      await axios.delete(`/api/admin/products/${row.id}`)
      ElMessage.success('删除成功')
      getProducts()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '删除失败')
    }
  })
}

onMounted(() => {
  getProducts()
})
</script>

<style scoped lang="scss">
.products-container {
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

.settings-group {
  display: flex;
  gap: 20px;
}

.subscription-config {
  padding: 20px;
  background-color: #f5f7fa;
  border-radius: 4px;
  margin-top: 20px;
}

.image-upload-container {
  .upload-tip {
    color: #999;
    font-size: 12px;
    margin-bottom: 10px;
  }
}
</style>
