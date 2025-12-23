import { defineStore } from 'pinia'
import { getCart, addToCart, updateCart, deleteCart } from '@/api/cart'

export const useCartStore = defineStore('cart', {
  state: () => ({
    cartItems: []
  }),

  getters: {
    // 购物车商品种类数量（不是总数量）
    cartCount: (state) => state.cartItems.length,
    // 购物车商品总数量
    cartTotalQuantity: (state) => state.cartItems.reduce((total, item) => total + item.quantity, 0),
    // 购物车总金额
    cartTotal: (state) => state.cartItems.reduce((total, item) => {
      return total + (item.product?.price || 0) * item.quantity
    }, 0)
  },

  actions: {
    // 获取购物车
    async fetchCart() {
      const res = await getCart()
      this.cartItems = res.data.data || []
      return res
    },

    // 添加到购物车
    async addItem(product_id, quantity = 1) {
      await addToCart({ product_id, quantity })
      await this.fetchCart()
    },

    // 更新购物车
    async updateItem(id, quantity) {
      await updateCart(id, { quantity })
      await this.fetchCart()
    },

    // 删除购物车商品
    async removeItem(id) {
      await deleteCart(id)
      await this.fetchCart()
    }
  }
})


