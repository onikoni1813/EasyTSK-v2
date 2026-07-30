<template>
  <LegalLayout title="Cookie Policy" icon="🍪" last-updated="July 27, 2025">

    <div class="highlight-box">
      This Cookie Policy explains how Easytsk V2 uses cookies and similar tracking technologies on our platform. By using our platform, you consent to the use of cookies as described in this policy. You can manage your cookie preferences at any time.
    </div>

    <h2>1. What Are Cookies?</h2>
    <p>
      Cookies are small text files placed on your device (computer, tablet, or mobile) when you visit a website. They are widely used to make websites work efficiently, remember your preferences, and provide information to website owners. Cookies do not contain personally identifiable information in their raw form, but when combined with other data we collect, they can be used to identify you.
    </p>
    <p>
      In addition to cookies, we use similar technologies such as web beacons, pixel tags, local storage, and session storage, which function similarly to cookies and are subject to this same policy.
    </p>

    <h2>2. Types of Cookies We Use</h2>

    <h3>2.1 Strictly Necessary Cookies</h3>
    <p>
      These cookies are essential for the Platform to function. They enable core functionality such as security, session management, and account authentication. You cannot opt out of these cookies as the Platform cannot function properly without them.
    </p>
    <ul>
      <li><strong style="color:#e2e8f0;">Session Cookie (easytsk_session):</strong> Maintains your login session. Expires at end of browser session.</li>
      <li><strong style="color:#e2e8f0;">CSRF Token (XSRF-TOKEN):</strong> Protects against cross-site request forgery attacks. Required for all form submissions.</li>
      <li><strong style="color:#e2e8f0;">Cookie Consent (easytsk_cookie_consent):</strong> Stored in localStorage to remember your cookie preferences. Expires after 365 days.</li>
    </ul>

    <h3>2.2 Analytics Cookies</h3>
    <p>
      These cookies help us understand how visitors interact with our Platform — which pages they visit, how long they stay, and where they come from. This information is used in aggregate to improve the Platform experience. These cookies may be set by us or by third-party analytics providers.
    </p>
    <ul>
      <li>Usage session data including pages visited, clicks, and time on page.</li>
      <li>Performance metrics to identify and fix technical issues.</li>
      <li>Referral source tracking to understand how users find our Platform.</li>
    </ul>

    <h3>2.3 Advertising & Targeting Cookies</h3>
    <p>
      These cookies are set by third-party advertising networks and offerwall providers that we work with to display relevant ads and offers on our Platform. They track your browsing activity across websites to build a profile of your interests and show you relevant advertising.
    </p>
    <div class="highlight-box">
      <strong>Third-Party Ad Network Disclosure:</strong> Our advertising partners may use their own cookies and tracking technologies when you interact with offerwall content or sponsored tasks on our platform. These networks operate under their own privacy and cookie policies, which we encourage you to review. We do not control third-party cookies.
    </div>
    <ul>
      <li>Conversion tracking to verify task and offer completions for advertisers.</li>
      <li>Retargeting pixels that may serve you ads on other websites based on your visits to our Platform.</li>
      <li>Audience segmentation for personalized offer delivery.</li>
    </ul>

    <h3>2.4 Functional Cookies</h3>
    <p>
      These cookies enable enhanced functionality and personalization, such as remembering your preferences, language settings, and UI customizations. They may be set by us or by third-party providers whose services we have added to our pages.
    </p>

    <h2>3. How We Use Cookies</h2>
    <ul>
      <li>To authenticate and maintain your logged-in session securely.</li>
      <li>To protect the Platform against CSRF attacks and other security threats.</li>
      <li>To remember your cookie consent preferences.</li>
      <li>To analyze platform usage and improve user experience.</li>
      <li>To enable offerwall and advertising networks to track conversions and credit your earnings.</li>
      <li>To detect fraudulent activity, multiple accounts, and bot traffic.</li>
      <li>To measure the effectiveness of our platform features.</li>
    </ul>

    <h2>4. Third-Party Cookies</h2>
    <p>
      When you access offerwall content, sponsored tasks, or advertising campaigns on our Platform, third-party companies may place their own cookies on your device. These third parties include, but are not limited to:
    </p>
    <ul>
      <li>Offerwall network providers (for conversion tracking and postback verification)</li>
      <li>Analytics platforms (for aggregate usage analysis)</li>
      <li>Advertising partners (for ad delivery and performance measurement)</li>
    </ul>
    <p>
      We do not have control over these third-party cookies. Please refer to the respective third party's privacy and cookie policy to understand how they use this information.
    </p>

    <h2>5. Managing Your Cookie Preferences</h2>
    <p>
      You can manage your cookie preferences in the following ways:
    </p>
    <ul>
      <li><strong style="color:#e2e8f0;">Cookie Banner:</strong> Use the "Accept All" or "Reject" buttons in the cookie consent banner that appears when you first visit the Platform.</li>
      <li><strong style="color:#e2e8f0;">Browser Settings:</strong> Most browsers allow you to block or delete cookies through their settings. Note that blocking strictly necessary cookies will prevent you from logging in or using key features of the Platform.</li>
      <li><strong style="color:#e2e8f0;">Opt-Out Tools:</strong> For third-party advertising cookies, you may use industry opt-out tools such as the NAI opt-out or the DAA opt-out at optout.aboutads.info.</li>
    </ul>

    <div class="warning-box">
      <strong>⚠️ Important:</strong> Disabling or rejecting cookies may affect the functionality of the Platform. Core features such as login sessions and task submissions require strictly necessary cookies to work correctly.
    </div>

    <h2>6. Cookie Retention Periods</h2>
    <ul>
      <li><strong style="color:#e2e8f0;">Session Cookies:</strong> Deleted when you close your browser.</li>
      <li><strong style="color:#e2e8f0;">Persistent Cookies:</strong> Retained for up to 365 days or until you clear them.</li>
      <li><strong style="color:#e2e8f0;">LocalStorage Consent Record:</strong> Retained for 365 days.</li>
      <li><strong style="color:#e2e8f0;">Third-Party Cookies:</strong> Duration varies by provider. Refer to each provider's policy.</li>
    </ul>

    <h2>7. Updates to This Policy</h2>
    <p>
      We may update this Cookie Policy from time to time to reflect changes in technology, regulation, or our business practices. When we make material changes, we will notify you via a notice on the Platform or via email. Your continued use of the Platform after such notice constitutes your acceptance of the updated policy.
    </p>

    <h2>8. Contact Us</h2>
    <p>
      If you have questions about our use of cookies or this Cookie Policy, please contact us at: <a :href="'mailto:' + ($page.props.siteSettings?.support_email || 'support@easytsk.com')">{{ $page.props.siteSettings?.support_email || 'support@easytsk.com' }}</a>
    </p>

  </LegalLayout>
</template>

<script setup>
import LegalLayout from '@/Layouts/LegalLayout.vue';
</script>
