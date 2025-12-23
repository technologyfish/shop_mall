import { defineStore } from 'pinia'
import { 
  login, 
  register, 
  getUserInfo, 
  logout, 
  updateProfile, 
  changePassword,
  updateAvatar 
} from '@/api/auth'

export const useUserStore = defineStore('user', {
  state: () => ({
    token: localStorage.getItem('token') || '',
    userInfo: null
  }),

  getters: {
    isLoggedIn: (state) => !!state.token,
    user: (state) => state.userInfo
  },

  actions: {
    // 登录
    async login(loginForm) {
      const res = await login(loginForm)
      // Response structure: { code: 0, data: { token: '...', user: ... } }
      // Axios response.data is the JSON body
      const data = res.data.data || res.data
      this.token = data.token
      this.userInfo = data.user
      localStorage.setItem('token', data.token)
      return res
    },

    // 注册
    async register(registerForm) {
      const res = await register(registerForm)
      const data = res.data.data || res.data
      this.token = data.token
      this.userInfo = data.user
      localStorage.setItem('token', data.token)
      return res
    },

    // 获取用户信息
    async fetchUserInfo() {
      const res = await getUserInfo()
      // Response structure: { code: 0, data: { ...user... } }
      this.userInfo = res.data.data || res.data
      return res
    },

    // 更新个人资料
    async updateProfile(profileData) {
      const res = await updateProfile(profileData)
      this.userInfo = res.data.data || res.data
      return res
    },

    // 更新头像
    async updateAvatar(formData) {
      const res = await updateAvatar(formData)
      // Update local user info with new data
      this.userInfo = res.data.data || res.data
      return res
    },

    // 修改密码
    async changePassword(passwordData) {
      const res = await changePassword(passwordData)
      return res
    },

    // 退出登录
    async logout() {
      try {
        await logout()
      } finally {
        this.token = ''
        this.userInfo = null
        localStorage.removeItem('token')
      }
    }
  }
})


