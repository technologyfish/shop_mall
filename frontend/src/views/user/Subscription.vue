<template>
  <div class="subscription-page">
    <div class="container">
      <!-- 标题部分 -->
      <div class="hero-section">
        <h1 class="main-title">HOW THE SAUCE CLUB SUBSCRIPTION WORKS</h1>
      </div>

      <!-- 步骤说明 -->
      <div class="steps-section">
        <!-- Step 1: JOIN THE CLUB -->
        <div class="step-item">
          <div class="step-number">1</div>
          <div class="step-icon">
<!--            <img src="@/assets/images/step-1-join.svg" alt="Join" v-if="false" />-->
            <div class="icon-placeholder">📱</div>
          </div>
          <h2 class="step-title">JOIN THE CLUB</h2>
          <p class="step-desc">Sign up to join the sauciest club in the UK. Pause, skip or cancel anytime.</p>
        </div>

        <!-- Step 2: UNBOX YOUR SAUCE -->
        <div class="step-item">
          <div class="step-number">2</div>
          <div class="step-icon">
            <div class="icon-placeholder">📦</div>
          </div>
          <h2 class="step-title">UNBOX YOUR SAUCE</h2>
          <p class="step-desc">Every other month you'll receive a flavour packed delivery.</p>
        </div>

        <!-- Step 3: SAVOUR THE FLAVOUR -->
        <div class="step-item">
          <div class="step-number">3</div>
          <div class="step-icon">
            <div class="icon-placeholder">😋</div>
          </div>
          <h2 class="step-title">SAVOUR THE FLAVOUR</h2>
          <p class="step-desc">Dip, drizzle and dunk like never before. Be sure to tag @thesauceclubco when you've cracked open your new sauce.</p>
        </div>
      </div>

      <!-- 订阅计划 -->
      <div class="plans-section">
        <h2 class="section-title">Choose Your Plan</h2>
        
        <div class="plans-grid" v-if="plans.length">
          <div v-for="plan in plans" :key="plan.id" class="plan-card">
            <div class="plan-image" v-if="plan.image">
              <img :src="getImageUrl(plan.image)" :alt="plan.name" />
            </div>
            <div class="plan-header">
              <h3>{{ plan.name }}</h3>
              <div class="plan-price">
                <span class="currency">$</span>
                <span class="amount">{{ plan.price }}</span>
                <span class="period">/{{ getPeriodText(plan.plan_type) }}</span>
              </div>
            </div>
            <div class="plan-body">
              <p class="plan-desc">{{ plan.description }}</p>
              <ul class="plan-features">
                <li>{{ plan.bottles_per_delivery }} bottles per delivery</li>
                <li>Free shipping</li>
                <li>Cancel anytime</li>
              </ul>
            </div>
            <div class="plan-footer">
              <el-button 
                type="primary" 
                size="large" 
                @click="handleSubscribe(plan)"
                :loading="subscribing && selectedPlanId === plan.id"
                class="subscribe-btn"
              >
                Subscribe Now
              </el-button>
            </div>
          </div>
        </div>

        <el-empty v-else description="No subscription plans available" />
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import message from '@/utils/message'
import { getSubscriptionPlans, createSubscription } from '@/api/subscription'
import { useUserStore } from '@/store/user'
import { getImageUrl } from '@/utils/image'

const router = useRouter()
const userStore = useUserStore()
const plans = ref([])
const subscribing = ref(false)
const selectedPlanId = ref(null)

onMounted(() => {
  fetchPlans()
})

const fetchPlans = async () => {
  try {
    const res = await getSubscriptionPlans()
    plans.value = res.data.data || []
  } catch (error) {
    message.error('Failed to load subscription plans')
  }
}

const handleSubscribe = async (plan) => {
  // 检查登录状态
  if (!userStore.token) {
    message.warning('Please login first')
    router.push('/login?redirect=/subscription')
    return
  }

  subscribing.value = true
  selectedPlanId.value = plan.id

  try {
    const res = await createSubscription({ plan_id: plan.id })
    const checkoutUrl = res.data.data.checkout_url

    // 跳转到Stripe Checkout页面
    window.location.href = checkoutUrl

  } catch (error) {
    // 400 错误（如已有订阅）在这里处理
    if (error.response?.status === 400) {
      message.error(error.response?.data?.message || 'Failed to create subscription')
    }
    subscribing.value = false
  }
}

const getPeriodText = (type) => {
  const periods = {
    'monthly': 'month',
    'quarterly': 'quarter',
    'yearly': 'year'
  }
  return periods[type] || 'month'
}

const getOrdinalSuffix = (day) => {
  if (day > 3 && day < 21) return 'th'
  switch (day % 10) {
    case 1: return 'st'
    case 2: return 'nd'
    case 3: return 'rd'
    default: return 'th'
  }
}

const formatDeliveryDate = (date) => {
  if (!date) return ''
  const d = new Date(date)
  const day = d.getDate()
  return `${day}${getOrdinalSuffix(day)} of each month`
}

const scrollToPlans = () => {
  document.querySelector('.plans-section')?.scrollIntoView({ behavior: 'smooth' })
}
</script>

<style scoped lang="scss">
.subscription-page {
  min-height: 100vh;
  background: var(--primary-lighter);

  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 60px 20px;
  }

  .hero-section {
    text-align: center;
    margin-bottom: 80px;

    .main-title {
      font-size: 48px;
      font-weight: bold;
      color: #fff;
      letter-spacing: 2px;

      @media (max-width: 768px) {
        font-size: 32px;
      }
    }
  }

  .steps-section {
    display: flex;
    justify-content: space-around;
    gap: 40px;
    margin-bottom: 80px;

    @media (max-width: 968px) {
      flex-direction: column;
      gap: 60px;
    }

    .step-item {
      flex: 1;
      text-align: center;
      position: relative;

      .step-number {
        position: absolute;
        top: -20px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 60px;
        background: var(--secondary-color);
        color: #000;
        font-size: 32px;
        font-weight: bold;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
      }

      .step-icon {
        margin: 60px auto 30px;
        width: 200px;
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;

        .icon-placeholder {
          font-size: 100px;
        }
      }

      .step-title {
        font-size: 24px;
        font-weight: bold;
        color: var(--text-white);
        margin-bottom: 15px;
        text-transform: uppercase;
      }

      .step-desc {
        font-size: 16px;
        color: var(--text-white);
        line-height: 1.6;
      }
    }
  }

  .plans-section {
    margin-bottom: 80px;

    .section-title {
      text-align: center;
      font-size: 36px;
      font-weight: bold;
      margin-bottom: 40px;
      color: var(--text-white);
    }

    .plans-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
      margin-bottom: 40px;
    }

    .plan-card {
      background: var(--secondary-color);
      border-radius: 16px;
      padding: 30px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s, box-shadow 0.3s;

      &:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 30px rgba(0, 0, 0, 0.15);
      }

      .plan-image {
        width: 100%;
        height: 200px;
        margin-bottom: 20px;
        border-radius: 12px;
        overflow: hidden;

        img {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }
      }

      .plan-header {
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 2px solid  var(--primary-lighter);

        h3 {
          font-size: 24px;
          font-weight: bold;
          margin-bottom: 15px;
          color: var(--text-color);
        }

        .plan-price {
          display: flex;
          align-items: baseline;
          justify-content: center;
          gap: 5px;

          .currency {
            font-size: 24px;
            color: var(--primary-lighter);
          }

          .amount {
            font-size: 48px;
            font-weight: bold;
            color: var(--primary-lighter);
          }

          .period {
            font-size: 18px;
            color: var(--text-color);
          }
        }
      }

      .plan-body {
        margin-bottom: 30px;

        .plan-desc {
          font-size: 14px;
          color: #666;
          margin-bottom: 20px;
          line-height: 1.6;
        }

        .plan-features {
          list-style: none;
          padding: 0;

          li {
            padding: 10px 0;
            color: #333;
            font-size: 15px;
            position: relative;
            padding-left: 25px;

            &:before {
              content: '✓';
              position: absolute;
              left: 0;
              color: #4CAF50;
              font-weight: bold;
            }
          }
        }
      }

      .plan-footer {
        .subscribe-btn {
          width: 100%;
          height: 50px;
          font-size: 18px;
          font-weight: bold;
          background: var(--accent-color);
          border: none;

        }
      }
    }
  }

}
@media (max-width: 968px) {
  .container {
    width: 100%;
  }
}
</style>


