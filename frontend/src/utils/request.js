import axios from 'axios'
import { useAdminStore } from '@/store/admin'
import router from '@/router'
import message from '@/utils/message'

// 创建axios实例
const request = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '',
  timeout: 15000
})

// 请求拦截器
request.interceptors.request.use(
  config => {
    // 判断是否是管理后台请求
    if (config.url?.startsWith('/api/admin')) {
      // 从pinia store获取admin token
      const adminStore = useAdminStore()
      if (adminStore.token) {
        config.headers.Authorization = `Bearer ${adminStore.token}`
      }
    } else if (config.url?.startsWith('/api') && !config.url.includes('/login') && !config.url.includes('/register')) {
      // 用户端请求（非登录注册接口）
      const token = localStorage.getItem('token')
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }
    }
    return config
  },
  error => {
    console.error('Request error:', error)
    return Promise.reject(error)
  }
)

// 响应拦截器
request.interceptors.response.use(
  response => {
    return response
  },
  error => {
    // 处理错误响应
    if (error.response) {
      const { status, data } = error.response
      
      switch (status) {
        case 401:
          // 未授权，清除token并跳转登录
          if (error.config.url?.startsWith('/api/admin')) {
            // 管理后台401
            const adminStore = useAdminStore()
            adminStore.logout()
            message.error('登录已过期，请重新登录')
            router.push('/admin/login')
          } else {
            // 用户端401
            localStorage.removeItem('token')
            message.error('登录已过期，请重新登录')
            router.push('/login')
          }
          break
          
        case 403:
          message.error(data.message || '没有权限访问')
          break
          
        case 404:
          message.error(data.message || '请求的资源不存在')
          break
          
        case 500:
          message.error(data.message || '服务器错误')
          break
          
        default:
          // 不在这里显示错误，让业务代码处理
          break
      }
    } else if (error.request) {
      message.error('网络错误，请检查网络连接')
    } else {
      message.error('请求配置错误')
    }
    
    return Promise.reject(error)
  }
)

export default request
