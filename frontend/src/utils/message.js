import { createApp, h, ref } from 'vue'
import MessageModal from '@/components/MessageModal.vue'

// 创建消息实例
let messageInstance = null
let messageContainer = null

const createMessageContainer = () => {
  if (messageContainer) return messageContainer
  messageContainer = document.createElement('div')
  messageContainer.id = 'global-message-modal'
  document.body.appendChild(messageContainer)
  return messageContainer
}

const showMessage = (options) => {
  const container = createMessageContainer()
  
  // 如果已存在实例，先销毁
  if (messageInstance) {
    messageInstance.unmount()
    messageInstance = null
  }
  
  const visible = ref(true)
  
  const props = {
    modelValue: true,
    message: typeof options === 'string' ? options : options.message,
    type: options.type || 'info',
    showIcon: options.showIcon !== false,
    showConfirmBtn: options.showConfirmBtn !== false,
    duration: options.duration || 0,
    'onUpdate:modelValue': (val) => {
      visible.value = val
      if (!val && messageInstance) {
        setTimeout(() => {
          if (messageInstance) {
            messageInstance.unmount()
            messageInstance = null
          }
        }, 300) // 等待动画完成
      }
    },
    onClose: () => {
      if (options.onClose) {
        options.onClose()
      }
    }
  }
  
  messageInstance = createApp({
    render() {
      return h(MessageModal, props)
    }
  })
  
  messageInstance.mount(container)
  
  return {
    close: () => {
      if (messageInstance) {
        messageInstance.unmount()
        messageInstance = null
      }
    }
  }
}

// 便捷方法
export const message = {
  success: (msg, options = {}) => showMessage({ ...options, message: msg, type: 'success' }),
  error: (msg, options = {}) => showMessage({ ...options, message: msg, type: 'error' }),
  warning: (msg, options = {}) => showMessage({ ...options, message: msg, type: 'warning' }),
  info: (msg, options = {}) => showMessage({ ...options, message: msg, type: 'info' }),
  show: showMessage
}

export default message




