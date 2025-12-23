import request from '@/utils/request'

/**
 * 获取公告列表
 */
export function getAnnouncements() {
  return request({
    url: '/api/announcements',
    method: 'get'
  })
}

/**
 * 获取Banner列表
 */
export function getBanners(position = 'home') {
  return request({
    url: '/api/banners',
    method: 'get',
    params: { position }
  })
}

