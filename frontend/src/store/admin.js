import { defineStore } from 'pinia'
import { adminLogin, getAdminInfo, adminLogout } from '@/api/admin'

export const useAdminStore = defineStore('admin', {
  state: () => ({
    token: localStorage.getItem('admin_token') || '',
    adminInfo: null
  }),

  getters: {
    isLoggedIn: (state) => !!state.token
  },

  actions: {
    // 登录
    async login(loginForm) {
      const res = await adminLogin(loginForm)
      // 后端返回格式: { code: 0, data: { admin: {...}, token: "..." } }
      const data = res.data.data || res.data
      this.token = data.token
      this.adminInfo = data.admin
      localStorage.setItem('admin_token', data.token)
      return res
    },

    // 获取管理员信息
    async fetchAdminInfo() {
      const res = await getAdminInfo()
      const data = res.data.data || res.data
      this.adminInfo = data
      return res
    },

    // 退出登录
    async logout() {
      try {
        await adminLogout()
      } finally {
        this.token = ''
        this.adminInfo = null
        localStorage.removeItem('admin_token')
      }
    }
  }
})


