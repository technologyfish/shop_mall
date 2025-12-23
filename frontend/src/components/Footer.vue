<template>
  <footer class="site-footer">
<!--    <div class="footer-content">-->
<!--      <div class="footer-section about">-->
<!--        <h3 class="footer-logo">The Chilli Trail</h3>-->
<!--        <p class="footer-desc">-->
<!--          Bringing authentic Sri Lankan flavours to your kitchen. -->
<!--          Every jar is a journey of taste and adventure.-->
<!--        </p>-->
<!--        <div class="social-links">-->
<!--          <a href="#" class="social-link" aria-label="Facebook">-->
<!--            <i class="icon-facebook"></i>-->
<!--          </a>-->
<!--          <a href="#" class="social-link" aria-label="Instagram">-->
<!--            <i class="icon-instagram"></i>-->
<!--          </a>-->
<!--          <a href="#" class="social-link" aria-label="Twitter">-->
<!--            <i class="icon-twitter"></i>-->
<!--          </a>-->
<!--        </div>-->
<!--      </div>-->

<!--      <div class="footer-section links">-->
<!--        <h4 class="footer-title">Quick Links</h4>-->
<!--        <ul class="footer-links">-->
<!--          <li><router-link to="/">Home</router-link></li>-->
<!--          <li><router-link to="/shop">Shop</router-link></li>-->
<!--          <li><router-link to="/our-journey">Our Journey</router-link></li>-->
<!--          <li><router-link to="/recipes">Recipes</router-link></li>-->
<!--          <li><router-link to="/contact">Contact</router-link></li>-->
<!--        </ul>-->
<!--      </div>-->

<!--      <div class="footer-section links">-->
<!--        <h4 class="footer-title">Customer Service</h4>-->
<!--        <ul class="footer-links">-->
<!--          <li><router-link to="/shipping">Shipping Info</router-link></li>-->
<!--          <li><router-link to="/returns">Returns</router-link></li>-->
<!--          <li><router-link to="/faq">FAQ</router-link></li>-->
<!--          <li><router-link to="/terms">Terms & Conditions</router-link></li>-->
<!--          <li><router-link to="/privacy">Privacy Policy</router-link></li>-->
<!--        </ul>-->
<!--      </div>-->

<!--      <div class="footer-section contact">-->
<!--        <h4 class="footer-title">Contact Us</h4>-->
<!--        <ul class="contact-info">-->
<!--          <li>-->
<!--            <i class="icon-email"></i>-->
<!--            <a href="mailto:hello@thechillitrail.com">hello@thechillitrail.com</a>-->
<!--          </li>-->
<!--          <li>-->
<!--            <i class="icon-phone"></i>-->
<!--            <a href="tel:+441234567890">+44 123 456 7890</a>-->
<!--          </li>-->
<!--          <li>-->
<!--            <i class="icon-location"></i>-->
<!--            <span>London, United Kingdom</span>-->
<!--          </li>-->
<!--        </ul>-->
<!--      </div>-->
<!--    </div>-->

    <div class="footer-bottom">
      <p class="copyright">
        © {{ currentYear }} The Chilli Trail. All rights reserved.
      </p>
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






