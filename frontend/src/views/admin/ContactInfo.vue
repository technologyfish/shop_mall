<template>
  <div class="contact-info-page">
    <el-card>
      <template #header>
        <div class="card-header">
          <h2>联系信息管理</h2>
        </div>
      </template>

      <el-form :model="form" label-width="120px" v-loading="loading">
        <el-form-item label="邮箱地址">
          <el-input v-model="form.email" placeholder="请输入邮箱地址" />
        </el-form-item>

        <el-form-item label="联系电话">
          <el-input v-model="form.phone" placeholder="请输入联系电话" />
        </el-form-item>

        <el-form-item label="地址位置">
          <el-input v-model="form.address" placeholder="请输入地址位置" />
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="handleSave" :loading="saving">
            保存
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getContactInfo, updateContactInfo } from '@/api/admin/contact'

const form = ref({
  email: '',
  phone: '',
  address: ''
})

const loading = ref(false)
const saving = ref(false)

// 获取联系信息
const fetchContactInfo = async () => {
  loading.value = true
  try {
    const res = await getContactInfo()
    const data = res.data.data
    
    // 后端返回的是数组格式，需要转换
    if (Array.isArray(data)) {
      data.forEach(item => {
        if (item.key === 'email') form.value.email = item.value
        if (item.key === 'phone') form.value.phone = item.value
        if (item.key === 'address') form.value.address = item.value
      })
    } else {
      // 如果是对象格式（兼容处理）
      if (data.email) form.value.email = data.email.value
      if (data.phone) form.value.phone = data.phone.value
      if (data.address) form.value.address = data.address.value
    }
  } catch (error) {
    ElMessage.error('获取联系信息失败')
  } finally {
    loading.value = false
  }
}

// 保存联系信息
const handleSave = async () => {
  saving.value = true
  try {
    const items = [
      { key: 'email', value: form.value.email, label: 'Email', type: 'email', sort: 1 },
      { key: 'phone', value: form.value.phone, label: 'Phone', type: 'text', sort: 2 },
      { key: 'address', value: form.value.address, label: 'Location', type: 'text', sort: 3 }
    ]
    
    await updateContactInfo({ items })
    ElMessage.success('保存成功')
  } catch (error) {
    ElMessage.error(error.response?.data?.message || '保存失败')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchContactInfo()
})
</script>

<style scoped lang="scss">
.contact-info-page {
  .card-header {
    h2 {
      margin: 0;
      font-size: 18px;
    }
  }

  .el-form {
    max-width: 600px;
  }
}
</style>

