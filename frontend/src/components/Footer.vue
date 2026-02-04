<template>
  <footer class="site-footer">
    <div class="footer-content">
      <div class="footer-left">
        <p class="copyright">
          © {{ currentYear }}  Mama's Fire · Love In Every Drop!
        </p>
<!--        <div class="terms-dropdown">-->
<!--          <span class="terms-link">Terms and Policies</span>-->
<!--          <div class="dropdown-menu">-->
<!--            <router-link to="/privacy-policy" class="dropdown-item">Privacy policy</router-link>-->
<!--            <router-link to="/refund-policy" class="dropdown-item">Refund policy</router-link>-->
<!--            <router-link to="/terms-of-service" class="dropdown-item">Terms of service</router-link>-->
<!--            <router-link to="/shipping-policy" class="dropdown-item">Shipping policy</router-link>-->
<!--          </div>-->
<!--        </div>-->
      </div>

      <div class="footer-right">
        <a href="https://www.facebook.com/mamasfire" target="_blank" rel="noopener noreferrer" class="social-icon">
          <img src="@/assets/images/facebook.png" alt="Facebook" />
        </a>
        <a href="https://www.instagram.com/mamasfire_" target="_blank" rel="noopener noreferrer" class="social-icon">
          <img src="@/assets/images/instagram.png" alt="Instagram" />
        </a>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
</script>

<style lang="scss">
@import "@/assets/scss/components/footer.scss";

@media (max-width: 768px) {
  @import "@/assets/scss/components/m_footer.scss";
}
</style>






