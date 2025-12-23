<template>
  <div class="shipping-settings-page">
    <h1 class="page-title">运费设置</h1>

    <div class="card">
      <el-form :model="form" label-width="150px" style="max-width: 600px">
        <el-form-item label="基础运费">
          <el-input-number v-model="form.shipping_fee" :min="0" :precision="2" :step="0.01" />
          <span class="tip">（单位：£）</span>
        </el-form-item>

        <el-form-item label="免运费门槛">
          <el-input-number v-model="form.free_shipping_threshold" :min="0" :precision="2" :step="1" />
          <span class="tip">（订单金额达到此金额免运费，设为0表示不免运费）</span>
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="handleSave" :loading="saving">保存设置</el-button>
        </el-form-item>
      </el-form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import axios from '@/utils/request'

const form = reactive({
  shipping_fee: 0.00,
  free_shipping_threshold: 0.00,
  status: 1
})

const saving = ref(false)

onMounted(() => {
  fetchSettings()
})

const fetchSettings = async () => {
  try {
    const res = await axios.get('/api/admin/shipping-settings')
    if (res.data.code === 0 && res.data.data) {
      Object.assign(form, res.data.data)
    }
  } catch (error) {
    console.error('获取运费设置失败:', error)
    ElMessage.error('获取运费设置失败')
  }
}

const handleSave = async () => {
  saving.value = true
  try {
    const res = await axios.put('/api/admin/shipping-settings', form)
    
    if (res.data.code === 0) {
      ElMessage.success('保存成功')
      fetchSettings()
    } else {
      ElMessage.error(res.data.message || '保存失败')
    }
  } catch (error) {
    console.error('保存失败:', error)
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped lang="scss">
.shipping-settings-page {
  .card {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
  }

  .tip {
    font-size: 12px;
    color: #999;
    margin-left: 10px;
  }
}
</style>
