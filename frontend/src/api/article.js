import request from '@/utils/request'

/**
 * 获取文章列表
 */
export function getArticles(params) {
  return request({
    url: '/api/articles',
    method: 'get',
    params
  })
}

/**
 * 获取文章详情
 */
export function getArticleDetail(id) {
  return request({
    url: `/api/articles/${id}`,
    method: 'get'
  })
}

/**
 * 根据slug获取文章详情
 */
export function getArticleBySlug(slug) {
  return request({
    url: `/api/articles/slug/${slug}`,
    method: 'get'
  })
}

