import request from '@/utils/request'

// 注册
export function register(data) {
  return request({
    url: '/api/auth/register',
    method: 'post',
    data
  })
}

// 登录
export function login(data) {
  return request({
    url: '/api/auth/login',
    method: 'post',
    data
  })
}

// 获取用户信息
export function getUserInfo() {
  return request({
    url: '/api/auth/me',
    method: 'get'
  })
}

// 退出登录
export function logout() {
  return request({
    url: '/api/auth/logout',
    method: 'post'
  })
}

// 更新个人资料
export function updateProfile(data) {
  return request({
    url: '/api/auth/profile',
    method: 'put',
    data
  })
}

// 更新头像
export function updateAvatar(formData) {
  return request({
    url: '/api/auth/avatar',
    method: 'post',
    data: formData,
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

// 修改密码
export function changePassword(data) {
  return request({
    url: '/api/auth/password',
    method: 'put',
    data
  })
}

// 忘记密码
export function forgotPassword(data) {
  return request({
    url: '/api/auth/forgot-password',
    method: 'post',
    data
  })
}

// 重置密码
export function resetPassword(data) {
  return request({
    url: '/api/auth/reset-password',
    method: 'post',
    data
  })
}
