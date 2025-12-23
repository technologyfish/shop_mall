import request from '@/utils/request'

// 获取邮件任务列表
export function getEmailTasks(params) {
  return request({
    url: '/api/admin/email-tasks',
    method: 'get',
    params
  })
}

// 获取邮件任务详情
export function getEmailTask(id) {
  return request({
    url: `/api/admin/email-tasks/${id}`,
    method: 'get'
  })
}

// 创建邮件任务
export function createEmailTask(data) {
  return request({
    url: '/api/admin/email-tasks',
    method: 'post',
    data
  })
}

// 更新邮件任务
export function updateEmailTask(id, data) {
  return request({
    url: `/api/admin/email-tasks/${id}`,
    method: 'put',
    data
  })
}

// 删除邮件任务
export function deleteEmailTask(id) {
  return request({
    url: `/api/admin/email-tasks/${id}`,
    method: 'delete'
  })
}

// 手动发送邮件
export function sendEmailTask(id) {
  return request({
    url: `/api/admin/email-tasks/${id}/send`,
    method: 'post'
  })
}




