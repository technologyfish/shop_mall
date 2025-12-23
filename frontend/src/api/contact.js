import request from '@/utils/request'

// 获取联系信息
export function getContactInfo() {
  return request({
    url: '/api/contact/info',
    method: 'get'
  })
}

// 提交联系表单
export function submitContactForm(data) {
  return request({
    url: '/api/contact/submit',
    method: 'post',
    data
  })
}





