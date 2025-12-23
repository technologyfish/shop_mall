import request from '@/utils/request'

// 获取运费设置
export function getShippingSettings() {
  return request({
    url: '/api/shipping/settings',
    method: 'get'
  })
}






