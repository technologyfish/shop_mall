<template>
  <div class="email-tasks-container">
    <div class="header">
      <h2>邮件任务管理</h2>
      <el-button type="primary" @click="handleAdd">
        <el-icon><Plus /></el-icon>
        新增任务
      </el-button>
    </div>

    <el-card class="filter-card">
      <el-form :inline="true" :model="filters">
        <el-form-item label="任务类型">
          <el-select v-model="filters.type" placeholder="全部" clearable @change="handleFilter" style="width: 180px">
            <el-option label="全部" value="" />
            <el-option label="注册欢迎" value="register" />
            <el-option label="下单确认" value="order" />
            <el-option label="节假日/新品" value="holiday" />
          </el-select>
        </el-form-item>
      </el-form>
    </el-card>

    <el-table :data="tableData" style="width: 100%" v-loading="loading">
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="name" label="任务名称" min-width="150" />
      <el-table-column label="任务类型" width="120">
        <template #default="{ row }">
          <el-tag :type="getTypeTag(row.type)">{{ getTypeLabel(row.type) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="subject" label="邮件标题" min-width="200" show-overflow-tooltip />
      <el-table-column label="状态" width="100">
        <template #default="{ row }">
          <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status === 1 ? '启用' : '禁用' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="220" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" @click="handleEdit(row)">编辑</el-button>
          <el-button 
            v-if="row.type === 'holiday'" 
            link 
            type="success" 
            @click="handleSend(row)"
          >发送</el-button>
          <el-button link type="danger" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="800px"
      @close="resetForm"
    >
      <el-form :model="form" :rules="rules" ref="formRef" label-width="120px">
        <el-form-item label="任务名称" prop="name">
          <el-input v-model="form.name" placeholder="请输入任务名称" />
        </el-form-item>
        <el-form-item label="任务类型" prop="type">
          <el-select v-model="form.type" placeholder="请选择任务类型" style="width: 100%" @change="handleTypeChange">
            <el-option label="注册欢迎" value="register" />
            <el-option label="下单确认" value="order" />
            <el-option label="节假日/新品" value="holiday" />
          </el-select>
        </el-form-item>
        <el-form-item label="邮件标题" prop="subject">
          <el-input v-model="form.subject" placeholder="请输入邮件标题" />
          <div class="form-tip">
            可用变量：
            <span v-for="(desc, key) in currentVariables" :key="key" class="variable-tag" @click="insertVariable('subject', key)">
              {{ key }} ({{ desc }})
            </span>
          </div>
        </el-form-item>
        <el-form-item label="邮件内容" prop="content">
          <el-input
            v-model="form.content"
            type="textarea"
            :rows="10"
            placeholder="请输入邮件内容（支持HTML）"
            id="content-input"
          />
          <div class="form-tip">
            可用变量：
            <span v-for="(desc, key) in currentVariables" :key="key" class="variable-tag" @click="insertVariable('content', key)">
              {{ key }} ({{ desc }})
            </span>
          </div>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="form.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-if="form.type === 'holiday'" label="计划发送时间" prop="schedule_time">
          <el-date-picker
            v-model="form.schedule_time"
            type="datetime"
            placeholder="选择发送时间"
            style="width: 100%"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
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
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import {
  getEmailTasks,
  createEmailTask,
  updateEmailTask,
  deleteEmailTask,
  sendEmailTask
} from '@/api/admin/emailTask'

const loading = ref(false)
const tableData = ref([])

const filters = reactive({
  type: ''
})

const dialogVisible = ref(false)
const dialogTitle = ref('新增任务')
const formRef = ref(null)
const submitting = ref(false)

const form = reactive({
  id: null,
  name: '',
  type: 'register',
  subject: '',
  content: '',
  status: 1,
  schedule_time: ''
})

const rules = {
  name: [{ required: true, message: '请输入任务名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择任务类型', trigger: 'change' }],
  subject: [{ required: true, message: '请输入邮件标题', trigger: 'blur' }],
  content: [{ required: true, message: '请输入邮件内容', trigger: 'blur' }]
}

const variablesMap = {
  register: { '{username}': '用户名称', '{email}': '用户邮箱' },
  order: { '{username}': '用户名称', '{order_no}': '订单号', '{amount}': '订单金额' },
  holiday: { '{username}': '用户名称' }
}

const currentVariables = computed(() => variablesMap[form.type] || {})

onMounted(() => {
  fetchData()
})

const fetchData = async () => {
  loading.value = true
  try {
    // 只传递非空的筛选参数
    const params = {}
    if (filters.type) {
      params.type = filters.type
    }
    const res = await getEmailTasks(params)
    tableData.value = res.data.data || []
  } catch (error) {
    ElMessage.error('获取数据失败')
  } finally {
    loading.value = false
  }
}

const handleFilter = () => {
  fetchData()
}

const handleAdd = () => {
  dialogTitle.value = '新增任务'
  resetForm()
  dialogVisible.value = true
}

const handleEdit = (row) => {
  dialogTitle.value = '编辑任务'
  Object.assign(form, row)
  dialogVisible.value = true
}

const handleSubmit = async () => {
  await formRef.value.validate()
  submitting.value = true
  try {
    if (form.id) {
      await updateEmailTask(form.id, form)
      ElMessage.success('更新成功')
    } else {
      await createEmailTask(form)
      ElMessage.success('创建成功')
    }
    dialogVisible.value = false
    fetchData()
  } catch (error) {
    ElMessage.error(error.message || '操作失败')
  } finally {
    submitting.value = false
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm('确定要删除此任务吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    await deleteEmailTask(row.id)
    ElMessage.success('删除成功')
    fetchData()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

const handleSend = async (row) => {
  try {
    await ElMessageBox.confirm('确定要立即发送邮件给所有用户吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    const res = await sendEmailTask(row.id)
    ElMessage.success(res.data.message || '发送成功')
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error(error.message || '发送失败')
    }
  }
}

const handleTypeChange = () => {
  // 切换类型时，可以清空或重置内容，这里暂时保留
}

const insertVariable = (field, variable) => {
  form[field] += variable
}

const resetForm = () => {
  form.id = null
  form.name = ''
  form.type = 'register'
  form.subject = ''
  form.content = ''
  form.status = 1
  form.schedule_time = ''
  formRef.value?.clearValidate()
}

const getTypeLabel = (type) => {
  const map = { register: '注册欢迎', order: '下单确认', holiday: '节假日/新品' }
  return map[type] || type
}

const getTypeTag = (type) => {
  const map = { register: 'primary', order: 'warning', holiday: 'success' }
  return map[type] || 'info'
}
</script>

<style scoped lang="scss">
.email-tasks-container {
  padding: 20px;

  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;

    h2 {
      margin: 0;
      font-size: 20px;
    }
  }

  .filter-card {
    margin-bottom: 20px;
  }

  .form-tip {
    font-size: 12px;
    color: #909399;
    margin-top: 5px;

    .variable-tag {
      display: inline-block;
      padding: 2px 6px;
      background: #f4f4f5;
      border: 1px solid #e9e9eb;
      border-radius: 4px;
      margin-right: 8px;
      margin-bottom: 4px;
      cursor: pointer;
      color: #409eff;

      &:hover {
        background: #ecf5ff;
        border-color: #d9ecff;
      }
    }
  }
}
</style>




