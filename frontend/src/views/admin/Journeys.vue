<template>
  <div class="journeys-container">
    <div class="page-header">
      <h2>Journey管理</h2>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        添加记录
      </el-button>
    </div>

    <!-- 列表 -->
    <el-card class="table-card">
      <el-table :data="journeys" v-loading="loading" border stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="image" label="图片" width="120">
          <template #default="{ row }">
            <el-image
              v-if="row.image"
              :src="getImageUrl(row.image)"
              :preview-src-list="[getImageUrl(row.image)]"
              fit="cover"
              style="width: 80px; height: 60px; border-radius: 4px"
            />
          </template>
        </el-table-column>
        <el-table-column prop="year" label="时间标签" width="150" sortable />
        <el-table-column prop="title" label="标题" min-width="150" show-overflow-tooltip />
        <el-table-column prop="description" label="描述" min-width="250" show-overflow-tooltip />
        <el-table-column prop="sort" label="排序" width="100" sortable />
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
        @size-change="fetchJourneys"
        @current-change="fetchJourneys"
      />
    </el-card>

    <!-- 添加/编辑对话框 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="700px">
      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
        <el-form-item label="时间标签" prop="year">
          <el-input v-model="form.year" placeholder="如：Oct 2024" />
        </el-form-item>
        
        <el-form-item label="标题" prop="title">
          <el-input v-model="form.title" placeholder="请输入标题" />
        </el-form-item>
        
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="form.description"
            type="textarea"
            :rows="4"
            placeholder="请输入描述内容"
          />
        </el-form-item>

        <el-form-item label="图片" prop="image">
          <ImageUpload v-model="form.image" tip="建议尺寸：600x400" />
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
import { getJourneys, createJourney, updateJourney, deleteJourney } from '@/api/journey'
import ImageUpload from '@/components/ImageUpload.vue'

const loading = ref(false)
const submitting = ref(false)
const journeys = ref([])

const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0
})

const dialogVisible = ref(false)
const dialogTitle = ref('添加记录')
const formRef = ref(null)
const form = reactive({
  id: null,
  year: '',
  title: '',
  description: '',
  image: '',
  sort: 0
})

const rules = {
  year: [{ required: true, message: '请输入时间标签', trigger: 'blur' }],
  title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
  image: [{ required: true, message: '请上传图片', trigger: 'change' }]
}

const fetchJourneys = async () => {
  loading.value = true
  try {
    const { data } = await getJourneys({
      page: pagination.page,
      limit: pagination.limit
    })
    journeys.value = data.data.data || []
    pagination.total = data.data.total || 0
  } catch (error) {
    ElMessage.error('获取列表失败')
  } finally {
    loading.value = false
  }
}

const handleAdd = () => {
  dialogTitle.value = '添加记录'
  Object.assign(form, {
    id: null,
    year: '',
    title: '',
    description: '',
    image: '',
    sort: 0
  })
  dialogVisible.value = true
}

const handleEdit = (row) => {
  dialogTitle.value = '编辑记录'
  Object.assign(form, row)
  dialogVisible.value = true
}

const handleSubmit = async () => {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitting.value = true
  try {
    if (form.id) {
      await updateJourney(form.id, form)
      ElMessage.success('更新成功')
    } else {
      await createJourney(form)
      ElMessage.success('添加成功')
    }
    dialogVisible.value = false
    fetchJourneys()
  } catch (error) {
    ElMessage.error('操作失败')
  } finally {
    submitting.value = false
  }
}

const handleDelete = (row) => {
  ElMessageBox.confirm('确定要删除这条记录吗？', '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(async () => {
    try {
      await deleteJourney(row.id)
      ElMessage.success('删除成功')
      fetchJourneys()
    } catch (error) {
      ElMessage.error('删除失败')
    }
  })
}

onMounted(() => {
  fetchJourneys()
})
</script>

<style scoped lang="scss">
.journeys-container {
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






