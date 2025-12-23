import request from '@/utils/request'

// 获取联系信息
export function getContactInfo() {
  return request({
    url: '/api/admin/contact/info',
    method: 'get'
  })
}

// 更新联系信息
export function updateContactInfo(data) {
  return request({
    url: '/api/admin/contact/info',
    method: 'put',
    data
  })
}

// 获取表单提交列表
export function getContactForms(params) {
  return request({
    url: '/api/admin/contact/forms',
    method: 'get',
    params
  })
}

// 获取表单详情
export function getContactForm(id) {
  return request({
    url: `/api/admin/contact/forms/${id}`,
    method: 'get'
  })
}

// 更新表单状态
export function updateFormStatus(id, status) {
  return request({
    url: `/api/admin/contact/forms/${id}/status`,
    method: 'put',
    data: { status }
  })
}

// 删除表单
export function deleteContactForm(id) {
  return request({
    url: `/api/admin/contact/forms/${id}`,
    method: 'delete'
  })
}





