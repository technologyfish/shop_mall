/**
 * 图片 URL 处理工具
 */

// API 基础地址
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || ''

/**
 * 获取完整图片 URL
 * @param {string} path - 图片路径（相对路径或完整 URL）
 * @param {string} placeholder - 占位图（可选）
 * @returns {string} 完整的图片 URL
 */
export const getImageUrl = (path, placeholder = '') => {
  if (!path) return placeholder
  if (path.startsWith('http')) return path
  if (path.startsWith('data:')) return path // base64 图片
  return `${apiBaseUrl}${path}`
}

/**
 * 获取商品图片（优先使用 images 数组的第一张）
 * @param {object} product - 商品对象
 * @returns {string} 图片 URL
 */
export const getProductImage = (product) => {
  if (!product) return '/placeholder-product.jpg'
  
  let imagePath = ''
  if (product.images && Array.isArray(product.images) && product.images.length > 0) {
    imagePath = product.images[0]
  } else {
    imagePath = product.image
  }
  
  return imagePath ? getImageUrl(imagePath) : '/placeholder-product.jpg'
}

/**
 * 获取商品图片列表（返回完整 URL 数组）
 * @param {object} product - 商品对象
 * @returns {string[]} 图片 URL 数组
 */
export const getProductImages = (product) => {
  if (!product) return ['/placeholder-product.jpg']
  
  if (product.images && Array.isArray(product.images) && product.images.length > 0) {
    return product.images.map(img => getImageUrl(img))
  } else if (product.image) {
    return [getImageUrl(product.image)]
  }
  
  return ['/placeholder-product.jpg']
}

export default {
  getImageUrl,
  getProductImage,
  getProductImages
}
