<template>
  <div class="image-upload">
    <el-upload
      :action="uploadUrl"
      :headers="uploadHeaders"
      :show-file-list="false"
      :on-success="handleSuccess"
      :on-error="handleError"
      :before-upload="beforeUpload"
      accept="image/*"
    >
      <div v-if="imageUrl" class="image-preview">
        <el-image :src="imageUrl" fit="cover" :preview-src-list="[imageUrl]" />
        <div class="image-actions">
          <el-icon @click.stop="handleRemove"><Delete /></el-icon>
        </div>
      </div>
      <div v-else class="upload-trigger">
        <el-icon class="avatar-uploader-icon"><Plus /></el-icon>
        <div class="upload-text">点击上传图片</div>
      </div>
    </el-upload>
    <div v-if="tip" class="upload-tip">{{ tip }}</div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Delete } from '@element-plus/icons-vue'
import { useAdminStore } from '@/store/admin'
import { getImageUrl } from '@/utils/image'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  tip: {
    type: String,
    default: '建议尺寸：800x600，支持jpg、png、gif、webp，最大5MB'
  }
})

const emit = defineEmits(['update:modelValue'])

const adminStore = useAdminStore()
// 内部存储相对路径
const internalValue = ref(props.modelValue)
// 显示用的完整 URL
const imageUrl = computed(() => getImageUrl(internalValue.value))
// API 基础地址
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || ''
const uploadUrl = computed(() => `${apiBaseUrl}/api/admin/upload`)
const uploadHeaders = ref({
  Authorization: `Bearer ${adminStore.token}`
})

watch(() => props.modelValue, (newVal) => {
  internalValue.value = newVal
})

watch(internalValue, (newVal) => {
  emit('update:modelValue', newVal)
})

const beforeUpload = (file) => {
  const isImage = file.type.startsWith('image/')
  const isLt5M = file.size / 1024 / 1024 < 5

  if (!isImage) {
    ElMessage.error('只能上传图片文件！')
    return false
  }
  if (!isLt5M) {
    ElMessage.error('图片大小不能超过 5MB！')
    return false
  }
  return true
}

const handleSuccess = (response) => {
  // 支持 code === 0 或 code === 200 都视为成功
  if (response.code === 0 || response.code === 200 || response.url) {
    // 获取图片URL（保存相对路径）
    const responseUrl = response.data?.url || response.url
    if (!responseUrl) {
      ElMessage.error('上传失败：未返回图片地址')
      return
    }
    // 保存相对路径（如 /uploads/images/xxx.jpg）
    internalValue.value = responseUrl
    ElMessage.success('上传成功')
  } else {
    ElMessage.error(response.message || '上传失败')
  }
}

const handleError = (error) => {
  console.error('Upload error:', error)
  ElMessage.error('上传失败，请重试')
}

const handleRemove = () => {
  internalValue.value = ''
}
</script>

<style scoped lang="scss">
.image-upload {
  .image-preview {
    position: relative;
    width: 200px;
    height: 200px;
    border: 1px solid #dcdfe6;
    border-radius: 6px;
    overflow: hidden;
    cursor: pointer;

    .el-image {
      width: 100%;
      height: 100%;
    }

    .image-actions {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(0, 0, 0, 0.5);
      opacity: 0;
      transition: opacity 0.3s;

      .el-icon {
        font-size: 24px;
        color: #fff;
        cursor: pointer;
        padding: 10px;

        &:hover {
          color: #f56c6c;
        }
      }
    }

    &:hover .image-actions {
      opacity: 1;
    }
  }

  .upload-trigger {
    width: 200px;
    height: 200px;
    border: 1px dashed #d9d9d9;
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: border-color 0.3s;

    &:hover {
      border-color: #409eff;
    }

    .avatar-uploader-icon {
      font-size: 28px;
      color: #8c939d;
    }

    .upload-text {
      margin-top: 10px;
      font-size: 14px;
      color: #8c939d;
    }
  }

  .upload-tip {
    margin-top: 8px;
    font-size: 12px;
    color: #909399;
    line-height: 1.5;
  }
}
</style>

