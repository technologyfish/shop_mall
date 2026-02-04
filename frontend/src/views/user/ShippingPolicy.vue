<template>
  <div class="policy-page">
    <div class="policy-container">
      <h1>Shipping policy</h1>
      
      <div class="policy-content">
        <h2>UK Shipping:</h2>
        <ul>
          <li>Free standard shipping on orders over £30.</li>
          <li>Standard shipping fee of £3.95 for orders under £30.</li>
          <li>Estimated delivery time: 2-3 working days.</li>
          <li>Orders are dispatched from:<br>
            The Chilli Trail<br>
            10 Hillside Way<br>
            Brighton & Hove<br>
            BN1 5FE
          </li>
        </ul>

        <h2>International Shipping:</h2>
        <ul>
          <li>Available upon request. Please contact us at <a href="mailto:contact@thechillitrail.com">contact@thechillitrail.com</a> for shipping quotes.</li>
          <li>Customers are responsible for any import duties or taxes.</li>
        </ul>

        <h2>Order Processing:</h2>
        <ul>
          <li>Orders are processed Monday to Friday.</li>
          <li>We aim to dispatch orders within 48 hours of receiving them.</li>
        </ul>

        <h2>Returns:</h2>
        <ul>
          <li>For returns, please send items to:<br>
            The Chilli Trail<br>
            Returns Department<br>
            10 Hillside Way<br>
            Brighton & Hove<br>
            BN1 5FE
          </li>
          <li>A note with your order number and reason for return should be included.</li>
        </ul>

        <h2>Tracking:</h2>
        <p>A tracking number will be provided once your order has shipped.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped lang="scss">
.policy-page {
  min-height: 100vh;
  background: #fafafa;
  padding: 60px 20px;
}

.policy-container {
  max-width: 900px;
  margin: 0 auto;
  background: white;
  padding: 60px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);

  h1 {
    font-size: 36px;
    color: #2c2c2c;
    margin-bottom: 40px;
    font-weight: 700;
  }

  .policy-content {
    line-height: 1.8;
    color: #444;

    h2 {
      font-size: 24px;
      color: #2c2c2c;
      margin-top: 40px;
      margin-bottom: 20px;
      font-weight: 600;
    }

    p {
      margin-bottom: 20px;
      font-size: 15px;
    }

    ul {
      margin: 20px 0;
      padding-left: 30px;

      li {
        margin-bottom: 12px;
        font-size: 15px;

        strong {
          color: #2c2c2c;
        }
      }
    }

    a {
      color: #c94a3c;
      text-decoration: none;

      &:hover {
        text-decoration: underline;
      }
    }
  }
}

@media (max-width: 768px) {
  .policy-page {
    padding: 40px 15px;
  }

  .policy-container {
    padding: 30px 20px;

    h1 {
      font-size: 28px;
    }

    .policy-content {
      h2 {
        font-size: 20px;
      }

      p, ul li {
        font-size: 14px;
      }
    }
  }
}
</style>













