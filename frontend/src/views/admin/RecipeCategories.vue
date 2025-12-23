<template>
  <div class="recipe-categories-page">
    <el-card>
      <template #header>
        <div class="card-header">
          <h2>Recipe Categories</h2>
          <el-button type="primary" @click="handleAdd">
            <el-icon><Plus /></el-icon>
            Add Category
          </el-button>
        </div>
      </template>

      <!-- 表格 -->
      <el-table :data="tableData" border v-loading="loading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="Name" width="150" />
        <el-table-column prop="slug" label="Slug" width="150" />
        <el-table-column prop="description" label="Description" show-overflow-tooltip />
        <el-table-column prop="recipes_count" label="Recipes" width="100" align="center" />
        <el-table-column prop="sort" label="Sort" width="100" align="center" />
        <el-table-column prop="status" label="Status" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'">
              {{ row.status === 1 ? 'Active' : 'Inactive' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="180" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" size="small" @click="handleEdit(row)">
              Edit
            </el-button>
            <el-button link type="danger" size="small" @click="handleDelete(row)">
              Delete
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

    <!-- 添加/编辑对话框 -->
    <el-dialog 
      v-model="dialogVisible" 
      :title="isEdit ? 'Edit Category' : 'Add Category'" 
      width="600px"
    >
      <el-form :model="form" :rules="rules" ref="formRef" label-width="120px">
        <el-form-item label="Name" prop="name">
          <el-input v-model="form.name" placeholder="Enter category name" />
        </el-form-item>
        <el-form-item label="Slug" prop="slug">
          <el-input v-model="form.slug" placeholder="URL slug (auto-generated if empty)" />
        </el-form-item>
        <el-form-item label="Description">
          <el-input 
            v-model="form.description" 
            type="textarea" 
            :rows="3"
            placeholder="Enter description"
          />
        </el-form-item>
        <el-form-item label="Sort">
          <el-input-number v-model="form.sort" :min="0" />
        </el-form-item>
        <el-form-item label="Status">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">Cancel</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="saving">
          Save
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
  getRecipeCategories,
  createRecipeCategory,
  updateRecipeCategory,
  deleteRecipeCategory
} from '@/api/admin/recipeCategory'

const tableData = ref([])
const loading = ref(false)
const saving = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const formRef = ref()

const pagination = reactive({
  current: 1,
  pageSize: 15,
  total: 0
})

const form = reactive({
  id: null,
  name: '',
  slug: '',
  description: '',
  sort: 0,
  status: 1
})

const rules = {
  name: [{ required: true, message: 'Please enter category name', trigger: 'blur' }],
}

// 获取数据
const fetchData = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.current,
      per_page: pagination.pageSize
    }
    
    const res = await getRecipeCategories(params)
    const data = res.data.data
    
    tableData.value = data.data || []
    pagination.total = data.total || 0
  } catch (error) {
    ElMessage.error('Failed to load categories')
  } finally {
    loading.value = false
  }
}

// 添加
const handleAdd = () => {
  isEdit.value = false
  resetForm()
  dialogVisible.value = true
}

// 编辑
const handleEdit = (row) => {
  isEdit.value = true
  Object.assign(form, row)
  dialogVisible.value = true
}

// 提交
const handleSubmit = async () => {
  if (!formRef.value) return
  
  await formRef.value.validate(async (valid) => {
    if (valid) {
      saving.value = true
      try {
        if (isEdit.value) {
          await updateRecipeCategory(form.id, form)
          ElMessage.success('Category updated successfully')
        } else {
          await createRecipeCategory(form)
          ElMessage.success('Category created successfully')
        }
        dialogVisible.value = false
        fetchData()
      } catch (error) {
        ElMessage.error(error.response?.data?.message || 'Operation failed')
      } finally {
        saving.value = false
      }
    }
  })
}

// 删除
const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm(
      'Are you sure you want to delete this category?',
      'Confirm',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )
    
    await deleteRecipeCategory(row.id)
    ElMessage.success('Category deleted successfully')
    fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('Failed to delete category')
    }
  }
}

// 重置表单
const resetForm = () => {
  Object.assign(form, {
    id: null,
    name: '',
    slug: '',
    description: '',
    sort: 0,
    status: 1
  })
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped lang="scss">
.recipe-categories-page {
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    
    h2 {
      margin: 0;
      font-size: 18px;
    }
  }

  .pagination {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
  }
}
</style>





