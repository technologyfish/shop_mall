<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="visible" class="message-modal-overlay" @click.self="handleClose">
        <div class="message-modal-content" :class="type">
          <button class="close-btn" @click="handleClose">
            <span>&times;</span>
          </button>
<!--          <div class="modal-icon" v-if="showIcon">-->
<!--            <el-icon v-if="type === 'success'" class="icon-success"><CircleCheckFilled /></el-icon>-->
<!--            <el-icon v-else-if="type === 'error'" class="icon-error"><CircleCloseFilled /></el-icon>-->
<!--            <el-icon v-else-if="type === 'warning'" class="icon-warning"><WarningFilled /></el-icon>-->
<!--            <el-icon v-else class="icon-info"><InfoFilled /></el-icon>-->
<!--          </div>-->
          <div class="modal-message">{{ message }}</div>
          <button v-if="showConfirmBtn" class="confirm-btn" @click="handleClose">
            OK
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { CircleCheckFilled, CircleCloseFilled, WarningFilled, InfoFilled } from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  message: {
    type: String,
    default: ''
  },
  type: {
    type: String,
    default: 'info', // success, error, warning, info
    validator: (val) => ['success', 'error', 'warning', 'info'].includes(val)
  },
  showIcon: {
    type: Boolean,
    default: true
  },
  showConfirmBtn: {
    type: Boolean,
    default: true
  },
  duration: {
    type: Number,
    default: 0 // 0 表示不自动关闭
  }
})

const emit = defineEmits(['update:modelValue', 'close'])

const visible = ref(props.modelValue)

watch(() => props.modelValue, (val) => {
  visible.value = val
  if (val && props.duration > 0) {
    setTimeout(() => {
      handleClose()
    }, props.duration)
  }
})

const handleClose = () => {
  visible.value = false
  emit('update:modelValue', false)
  emit('close')
}
</script>

<style scoped lang="scss">
.message-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10000;
}

.message-modal-content {
  background: white;
  border-radius: 12px;
  padding: 30px 40px;
  min-width: 320px;
  max-width: 500px;
  text-align: center;
  position: relative;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);

  .close-btn {
    position: absolute;
    top: 12px;
    right: 15px;
    background: none;
    border: none;
    font-size: 24px;
    color: #999;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    
    &:hover {
      color: #333;
    }
  }

  .modal-icon {
    margin-bottom: 15px;
    
    .el-icon {
      font-size: 48px;
    }
    
    .icon-success {
      color: #67c23a;
    }
    
    .icon-error {
      color: #f56c6c;
    }
    
    .icon-warning {
      color: #e6a23c;
    }
    
    .icon-info {
      color: #409eff;
    }
  }

  .modal-message {
    font-size: 16px;
    color: #333;
    line-height: 1.6;
    margin-bottom: 20px;
    word-break: break-word;
  }

  .confirm-btn {
    background: var(--primary-color, #a73121);
    color: white;
    border: none;
    padding: 10px 40px;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.3s;
    
    &:hover {
      background: var(--primary-dark, #8a2919);
    }
  }
}

@media (max-width: 768px) {
  .message-modal-content {
    max-width: 80%;
  }
}

// 过渡动画
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active .message-modal-content,
.modal-fade-leave-active .message-modal-content {
  transition: transform 0.3s ease;
}

.modal-fade-enter-from .message-modal-content,
.modal-fade-leave-to .message-modal-content {
  transform: scale(0.9);
}


</style>




