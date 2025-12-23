<template>
  <div class="recipes-container">
    <div class="page-header">
      <h2>食谱管理</h2>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        添加食谱
      </el-button>
    </div>

    <!-- 食谱列表 -->
    <el-card class="table-card">
      <el-table :data="recipes" v-loading="loading" border stripe>
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
        <el-table-column prop="subtitle" label="副标题" min-width="200" show-overflow-tooltip />
        <el-table-column label="分类" width="120">
          <template #default="{ row }">
            <el-tag v-if="row.category">{{ row.category.name }}</el-tag>
            <span v-else class="text-muted">未分类</span>
          </template>
        </el-table-column>
        <el-table-column prop="cook_time" label="制作时间" width="100">
          <template #default="{ row }">
            {{ row.cook_time }} 分钟
          </template>
        </el-table-column>
        <el-table-column prop="servings" label="服务人数" width="100" />
        <el-table-column prop="created_at" label="发布时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="180" fixed="right">
          <template #default="{ row }">
            <el-button size="small" @click="handleEdit(row)">编辑</el-button>
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
        @size-change="getRecipes"
        @current-change="getRecipes"
      />
    </el-card>

    <!-- 添加/编辑对话框 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="1000px" :close-on-click-modal="false">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" placeholder="请输入标题" style="width: 60%" />
        </el-form-item>
        
        <el-form-item label="副标题" prop="subtitle">
          <el-input v-model="form.subtitle" placeholder="请输入副标题" style="width: 60%" />
        </el-form-item>
        
        <el-form-item label="封面图片" prop="image">
          <ImageUpload v-model="form.image" tip="建议尺寸：800x600，支持jpg、png、gif、webp，最大5MB" />
        </el-form-item>

        <el-form-item label="分类" prop="category_id">
          <el-select v-model="form.category_id" placeholder="请选择分类" style="width: 60%">
            <el-option label="未分类" :value="null" />
            <el-option
              v-for="cat in categories"
              :key="cat.id"
              :label="cat.name"
              :value="cat.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="准备时间" prop="prep_time">
          <el-input-number
            v-model="form.prep_time"
            :min="0"
            :step="5"
            controls-position="right"
          />
          <span style="margin-left: 10px; color: #909399">分钟</span>
        </el-form-item>
        
        <el-form-item label="制作时间" prop="cook_time">
          <el-input-number
            v-model="form.cook_time"
            :min="0"
            :step="5"
            controls-position="right"
          />
          <span style="margin-left: 10px; color: #909399">分钟</span>
        </el-form-item>
        
        <el-form-item label="服务人数" prop="servings">
          <el-input-number
            v-model="form.servings"
            :min="1"
            :step="1"
            controls-position="right"
          />
          <span style="margin-left: 10px; color: #909399">人</span>
        </el-form-item>

        <el-form-item label="内容" prop="content">
          <RichTextEditor 
            v-model="form.content"
            placeholder="请输入食谱的详细内容，包括食材、步骤等..."
            height="600px"
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
import { ElMessageBox } from 'element-plus'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { getImageUrl } from '@/utils/image'
import axios from '@/utils/request'
import ImageUpload from '@/components/ImageUpload.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { formatDate } from '@/utils/format'

const loading = ref(false)
const submitting = ref(false)
const recipes = ref([])
const categories = ref([])

const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0
})

const dialogVisible = ref(false)
const dialogTitle = ref('添加食谱')
const formRef = ref(null)
const form = reactive({
  id: null,
  category_id: null,
  title: '',
  subtitle: '',
  image: '',
  prep_time: 10,
  cook_time: 30,
  servings: 4,
  content: ''
})

const rules = {
  title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
  subtitle: [{ required: true, message: '请输入副标题', trigger: 'blur' }],
  image: [{ required: true, message: '请上传封面图片', trigger: 'change' }],
  content: [{ required: true, message: '请输入内容', trigger: 'blur' }]
}

const getRecipes = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/admin/recipes', {
      params: {
        page: pagination.page,
        limit: pagination.limit
      }
    })
    recipes.value = data.data.data || []
    pagination.total = data.data.total || 0
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '获取食谱列表失败')
  } finally {
    loading.value = false
  }
}

const handleAdd = () => {
  dialogTitle.value = '添加食谱'
  Object.assign(form, {
    id: null,
    category_id: null,
    title: '',
    subtitle: '',
    image: '',
    prep_time: 10,
    cook_time: 30,
    servings: 4,
    content: ''
  })
  dialogVisible.value = true
}

const handleEdit = (row) => {
  dialogTitle.value = '编辑食谱'
  Object.assign(form, {
    id: row.id,
    category_id: row.category_id,
    title: row.title,
    subtitle: row.subtitle,
    image: row.image,
    prep_time: row.prep_time || 10,
    cook_time: row.cook_time,
    servings: row.servings,
    content: row.instructions || row.content || ''
  })
  dialogVisible.value = true
}

const handleSubmit = async () => {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitting.value = true
  try {
    // 构建提交数据，兼容后端字段
    const submitData = {
      category_id: form.category_id,
      title: form.title,
      subtitle: form.subtitle,
      slug: form.title.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, ''),
      image: form.image,
      description: form.subtitle,
      ingredients: `<p>准备时间：${form.prep_time}分钟</p>`,
      instructions: form.content,
      prep_time: form.prep_time,
      cook_time: form.cook_time,
      servings: form.servings,
      difficulty: 'medium',
      is_featured: 1,
      sort: 100,
      status: 1
    }

    if (form.id) {
      await axios.put(`/api/admin/recipes/${form.id}`, submitData)
      ElMessage.success('更新成功')
    } else {
      await axios.post('/api/admin/recipes', submitData)
      ElMessage.success('添加成功')
    }
    dialogVisible.value = false
    getRecipes()
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

const handleDelete = (row) => {
  ElMessageBox.confirm('确定要删除这个食谱吗？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      await axios.delete(`/api/admin/recipes/${row.id}`)
      ElMessage.success('删除成功')
      getRecipes()
    } catch (error) {
      ElMessage.error(error.response?.data?.message || '删除失败')
    }
  })
}

// 获取分类列表
const getCategories = async () => {
  try {
    const { data } = await axios.get('/api/admin/recipe-categories', {
      params: { per_page: 100 }
    })
    categories.value = data.data.data || []
  } catch (error) {
    console.error('获取分类列表失败', error)
  }
}

onMounted(() => {
  getRecipes()
  getCategories()
})
</script>

<style scoped lang="scss">
.recipes-container {
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
