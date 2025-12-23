import request from '@/utils/request'

// 获取用户可用折扣
export function getUserDiscount() {
  return request({
    url: '/api/user/discount',
    method: 'get'
  })
}



