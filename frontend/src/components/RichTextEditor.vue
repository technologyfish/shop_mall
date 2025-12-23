<template>
  <div class="rich-text-editor">
    <Toolbar
      :editor="editorRef"
      :defaultConfig="toolbarConfig"
      :mode="mode"
      class="editor-toolbar"
    />
    <Editor
      v-model="valueHtml"
      :defaultConfig="editorConfig"
      :mode="mode"
      class="editor-content"
      @onCreated="handleCreated"
      @onChange="handleChange"
    />
  </div>
</template>

<script setup>
import { ref, watch, onBeforeUnmount, shallowRef } from 'vue'
import { Editor, Toolbar } from '@wangeditor/editor-for-vue'
import '@wangeditor/editor/dist/css/style.css'
import { useAdminStore } from '@/store/admin'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: '请输入内容...'
  },
  height: {
    type: String,
    default: '400px'
  }
})

const emit = defineEmits(['update:modelValue'])

const adminStore = useAdminStore()
const editorRef = shallowRef()
const valueHtml = ref(props.modelValue)
const mode = 'default' // 或 'simple'

// 工具栏配置
const toolbarConfig = {
  excludeKeys: [
    'group-video', // 排除视频
  ]
}

// 编辑器配置
const editorConfig = {
  placeholder: props.placeholder,
  MENU_CONF: {
    // 配置上传图片
    uploadImage: {
      server: '/api/admin/upload',
      fieldName: 'file',
      headers: {
        Authorization: `Bearer ${adminStore.token}`
      },
      maxFileSize: 5 * 1024 * 1024, // 5MB
      maxNumberOfFiles: 10,
      allowedFileTypes: ['image/*'],
      customInsert(res, insertFn) {
        // 从res中找到url，插入到编辑器
        if (res.code === 0 && res.data && res.data.url) {
          const url = res.data.url.startsWith('http') 
            ? res.data.url 
            : window.location.origin + res.data.url
          insertFn(url, '', url)
        }
      },
      onError(file, err, res) {
        console.error('图片上传错误:', err, res)
      }
    }
  }
}

watch(() => props.modelValue, (newVal) => {
  if (newVal !== valueHtml.value) {
    valueHtml.value = newVal
  }
})

const handleCreated = (editor) => {
  editorRef.value = editor
}

const handleChange = (editor) => {
  emit('update:modelValue', valueHtml.value)
}

onBeforeUnmount(() => {
  const editor = editorRef.value
  if (editor == null) return
  editor.destroy()
})
</script>

<style scoped lang="scss">
.rich-text-editor {
  border: 1px solid #ccc;
  border-radius: 4px;

  .editor-toolbar {
    border-bottom: 1px solid #ccc;
  }

  .editor-content {
    height: v-bind(height);
    overflow-y: auto;
  }
}
</style>

<style>
/* 编辑器内容样式 */
.w-e-text-container {
  background-color: #fff;
}

.w-e-text-placeholder {
  font-style: normal;
  color: #999;
}
</style>






