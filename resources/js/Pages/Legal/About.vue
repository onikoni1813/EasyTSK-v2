<template>
  <LegalLayout title="About Us" icon="🌟" last-updated="July 2025">

    <div class="highlight-box">
      <strong>Easytsk V2</strong> is a next-generation micro-task earning platform designed to help anyone — regardless of background or experience — monetize their spare time from the comfort of their home.
    </div>

    <h2>Our Mission</h2>
    <p>
      At Easytsk, our mission is simple: <strong style="color:#e2e8f0;">democratize online earning</strong> by connecting advertisers who need real human engagement with everyday individuals who want to earn supplemental income. We believe that your time has value, and we've built a platform that proves it.
    </p>
    <p>
      We bridge the gap between global digital advertisers and local earners by providing a trusted, transparent, and rewarding micro-task ecosystem. Every task you complete on our platform contributes to a larger ecosystem of digital marketing, market research, and user experience improvement.
    </p>

    <h2>Who We Are</h2>
    <p>
      Easytsk V2 is developed and operated by a team of passionate digital entrepreneurs and software engineers who identified a critical gap in the online earning market: while numerous platforms exist, very few offer genuine, verified earning opportunities with reliable payouts and a user-first experience.
    </p>
    <p>
      Founded with a focus on the South Asian market — particularly Bangladesh — Easytsk supports local payment methods including bKash and Nagad, making it accessible to millions who are often underserved by international earning platforms that require credit cards or PayPal accounts.
    </p>
    <p>
      Our platform has been carefully designed to be fraud-resistant, ensuring that advertisers receive genuine engagement and that legitimate earners are rewarded fairly. We use advanced IP monitoring, device fingerprinting, and behavioral analysis to maintain the integrity of our ecosystem.
    </p>

    <h2>What We Offer</h2>
    <ul>
      <li><strong style="color:#e2e8f0;">Micro-Tasks:</strong> Short, simple tasks including survey completions, video watching, app testing, and website visits.</li>
      <li><strong style="color:#e2e8f0;">Offerwalls:</strong> Access to hundreds of third-party earning opportunities from global advertisers.</li>
      <li><strong style="color:#e2e8f0;">Referral Program:</strong> Earn bonus points when you refer active members to the platform.</li>
      <li><strong style="color:#e2e8f0;">Daily Spin Wheel:</strong> A gamified daily bonus system to keep earning exciting and engaging.</li>
      <li><strong style="color:#e2e8f0;">Micro-Campaigns:</strong> Promote products and services as a creator and earn commission per valid engagement.</li>
      <li><strong style="color:#e2e8f0;">Local Payouts:</strong> Withdraw your earnings directly to bKash, Nagad, or other supported gateways.</li>
    </ul>

    <h2>Our Values</h2>
    <h3>Transparency</h3>
    <p>
      We believe in being upfront with our users. Our earnings potential, task requirements, withdrawal conditions, and account rules are all clearly documented. We do not hide fees or create misleading earning expectations.
    </p>
    <h3>Fairness</h3>
    <p>
      Every user — whether they joined today or a year ago — is subject to the same rules. Our review team evaluates task completions objectively, and our automated fraud detection system treats all accounts equally without bias.
    </p>
    <h3>Security</h3>
    <p>
      User data privacy and platform security are paramount. We use industry-standard encryption for all sensitive data, comply with applicable data protection regulations, and never sell personal information to unauthorized third parties.
    </p>
    <h3>Community</h3>
    <p>
      Easytsk is more than a platform — it's a growing community of digital earners helping each other succeed. We are committed to continuously improving based on user feedback, adding new task types, and increasing payout rates as our advertiser base grows.
    </p>

    <h2>Contact & Support</h2>
    <p>
      Have questions, concerns, or partnership inquiries? Our support team is available to help:
    </p>
    <ul>
      <li><strong style="color:#e2e8f0;">Support Email:</strong> <a :href="'mailto:' + ($page.props.siteSettings?.support_email || 'support@easytsk.com')">{{ $page.props.siteSettings?.support_email || 'support@easytsk.com' }}</a></li>
      <li><strong style="color:#e2e8f0;">Contact Form:</strong> <a href="/contact">easytsk.com/contact</a></li>
      <li><strong style="color:#e2e8f0;">Response Time:</strong> Within 24–48 business hours</li>
    </ul>
    <p>
      We are committed to responding to every legitimate inquiry within two business days. For urgent account issues, please include your registered email address and a brief description of your issue in the subject line.
    </p>

  </LegalLayout>
</template>

<script setup>
import LegalLayout from '@/Layouts/LegalLayout.vue';
</script>
