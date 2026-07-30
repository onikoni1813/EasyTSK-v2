<template>
  <LegalLayout title="Terms of Service" icon="⚖️" last-updated="July 27, 2025">

    <div class="highlight-box">
      Please read these Terms of Service carefully before using Easytsk V2. By accessing or using our platform, you agree to be bound by these terms. If you do not agree, you must not use the platform.
    </div>

    <h2>1. Acceptance of Terms</h2>
    <p>
      By registering an account, accessing, or using the Easytsk V2 platform ("Platform"), you agree to comply with and be legally bound by these Terms of Service ("Terms"), our Privacy Policy, and our Cookie Policy. These Terms constitute a legally binding agreement between you ("User") and Easytsk V2 ("Company," "we," "us," or "our").
    </p>
    <p>
      We reserve the right to modify these Terms at any time. Continued use of the Platform after changes constitutes acceptance of the updated Terms. It is your responsibility to review these Terms periodically.
    </p>

    <h2>2. Eligibility</h2>
    <p>
      You must be at least 13 years of age (or the minimum age of digital consent in your jurisdiction) to use this Platform. By creating an account, you represent and warrant that you meet this requirement. The Platform is intended for personal, non-commercial use.
    </p>

    <h2>3. Account Registration & One Account Policy</h2>
    <p>
      Each individual is permitted to register and operate <strong style="color:#fda4af;">exactly one (1) account</strong> on the Platform. Creating, operating, or participating in multiple accounts — either directly or through a third party — from the same household, IP address, device, or browser fingerprint is strictly prohibited.
    </p>
    <div class="warning-box">
      <strong>⚠️ Multiple Account Policy:</strong> If you are found to operate more than one account, ALL associated accounts will be permanently banned, and ALL accumulated balances will be forfeited without the possibility of appeal or refund.
    </div>
    <p>
      You are responsible for maintaining the confidentiality of your login credentials and for all activities that occur under your account. You must notify us immediately at <a :href="'mailto:' + ($page.props.siteSettings?.support_email || 'support@easytsk.com')">{{ $page.props.siteSettings?.support_email || 'support@easytsk.com' }}</a> if you suspect unauthorized access to your account.
    </p>

    <h2>4. Prohibited Activities</h2>
    <p>The following activities are strictly prohibited and will result in immediate account suspension or permanent ban:</p>
    <ul>
      <li>Creating multiple accounts (see Section 3).</li>
      <li>Using VPNs, proxy servers, Tor, or any IP-masking tool to circumvent geographic restrictions or fraud detection.</li>
      <li>Using bots, scripts, automation tools, browser extensions, or any non-human means to complete tasks.</li>
      <li>Submitting false, fabricated, or doctored screenshots or proof-of-work for task completions.</li>
      <li>Exploiting bugs or vulnerabilities in the Platform. Any discovered bugs must be reported to support immediately.</li>
      <li>Engaging in chargebacks, refund fraud, or any financial manipulation.</li>
      <li>Sharing accounts with other individuals or groups.</li>
      <li>Using the Platform for any illegal, fraudulent, or harmful purpose.</li>
      <li>Reverse engineering, decompiling, or disassembling any part of the Platform.</li>
      <li>Harassing, threatening, or abusing other users or Platform staff.</li>
    </ul>

    <h2>5. Account Suspension & Ban Policy</h2>
    <p>
      We reserve the right to suspend or permanently ban any account at our sole discretion if we determine, through automated systems or manual review, that the account is in violation of these Terms. The following actions will result in the corresponding consequences:
    </p>
    <ul>
      <li><strong style="color:#e2e8f0;">First-time minor violation</strong> (e.g., suspicious task submission): 24–72 hour suspension and task rejection.</li>
      <li><strong style="color:#e2e8f0;">Repeated minor violations or single major violation</strong> (e.g., VPN use, automation): Permanent ban and balance forfeiture.</li>
      <li><strong style="color:#e2e8f0;">Multiple account creation</strong>: Immediate permanent ban of all associated accounts and balance forfeiture.</li>
      <li><strong style="color:#e2e8f0;">Fraudulent activity</strong>: Permanent ban, balance forfeiture, and potential legal action.</li>
    </ul>
    <p>
      Banned accounts may submit a formal appeal to <a :href="'mailto:' + ($page.props.siteSettings?.support_email || 'support@easytsk.com')">{{ $page.props.siteSettings?.support_email || 'support@easytsk.com' }}</a>. Appeals are reviewed within 7 business days. Our decisions on ban appeals are final and binding.
    </p>

    <h2>6. Earning & Points System</h2>
    <p>
      Points are earned by completing approved tasks and offers on the Platform. Points have no cash value until converted through our withdrawal system. We reserve the right to adjust point values, task rewards, and conversion rates at any time with reasonable notice to users.
    </p>
    <p>
      Pending points are held in a pending balance and will be transferred to your main balance upon successful review and approval of the associated task. Approval typically takes 24–72 hours.
    </p>

    <h2>7. Withdrawal & Payment Rules</h2>
    <ul>
      <li>Withdrawals are only available to accounts in good standing (not suspended or under review).</li>
      <li>A minimum withdrawal amount applies as set by the Platform settings. This amount may change with notice.</li>
      <li>A withdrawal processing fee may be charged, as disclosed at the time of withdrawal request.</li>
      <li>The first withdrawal from a new account is subject to a higher minimum threshold to prevent fraud.</li>
      <li>Withdrawals are processed within 24–72 business hours. During high-volume periods, processing may take up to 7 business days.</li>
      <li>Payments are made to the payment method on file (bKash, Nagad, etc.). Easytsk is not responsible for delays caused by the payment processor.</li>
      <li>Balances associated with banned accounts will be forfeited and will not be refunded under any circumstance.</li>
    </ul>

    <h2>8. Intellectual Property</h2>
    <p>
      All content on the Platform, including but not limited to text, graphics, logos, icons, images, and software, is the property of Easytsk V2 and is protected by applicable intellectual property laws. You may not reproduce, distribute, or create derivative works without our express written permission.
    </p>

    <h2>9. Disclaimer of Warranties</h2>
    <p>
      The Platform is provided "as is" and "as available" without any warranties of any kind, express or implied. We do not warrant that the Platform will be uninterrupted, error-free, or free of viruses. We are not responsible for any losses arising from technical issues, system downtime, or third-party offerwall discrepancies.
    </p>

    <h2>10. Limitation of Liability</h2>
    <p>
      To the maximum extent permitted by law, Easytsk V2 shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of the Platform. Our total aggregate liability shall not exceed the amount of points/cash in your active account at the time of the incident.
    </p>

    <h2>11. Governing Law</h2>
    <p>
      These Terms shall be governed by and construed in accordance with the laws of Bangladesh. Any disputes arising from these Terms shall be subject to the exclusive jurisdiction of the courts of Dhaka, Bangladesh.
    </p>

    <h2>12. Contact</h2>
    <p>
      For questions regarding these Terms, please contact: <a :href="'mailto:' + ($page.props.siteSettings?.support_email || 'support@easytsk.com')">{{ $page.props.siteSettings?.support_email || 'support@easytsk.com' }}</a>
    </p>

  </LegalLayout>
</template>

<script setup>
import LegalLayout from '@/Layouts/LegalLayout.vue';
</script>
