<template>
  <LegalLayout title="Privacy Policy" icon="🔒" last-updated="July 27, 2025">

    <div class="highlight-box">
      This Privacy Policy describes how Easytsk V2 collects, uses, stores, and shares your personal information when you use our Platform. By using Easytsk V2, you consent to the data practices described in this Policy.
    </div>

    <h2>1. Information We Collect</h2>
    <p>We collect the following categories of personal and technical information:</p>

    <h3>1.1 Information You Provide</h3>
    <ul>
      <li><strong style="color:#e2e8f0;">Account Information:</strong> Your name, email address, and password (stored as a bcrypt hash) upon registration.</li>
      <li><strong style="color:#e2e8f0;">Payment Details:</strong> Your payment method information (e.g., bKash/Nagad mobile number) when you add a withdrawal method. We do not store full payment account PINs.</li>
      <li><strong style="color:#e2e8f0;">Communications:</strong> Messages you send to our support team, including email content and metadata.</li>
    </ul>

    <h3>1.2 Automatically Collected Information</h3>
    <ul>
      <li><strong style="color:#e2e8f0;">IP Address:</strong> Your IP address is recorded at login, registration, task submission, and withdrawal request for fraud prevention and geographic compliance purposes.</li>
      <li><strong style="color:#e2e8f0;">Device Fingerprint Hash:</strong> A one-way cryptographic hash of your browser fingerprint (including browser type, OS, screen resolution, timezone, and installed fonts) is generated and stored. This hash cannot be reversed to identify your device but is used to detect multiple account creation and bot activity. The raw fingerprint data is never stored.</li>
      <li><strong style="color:#e2e8f0;">Usage Data:</strong> Pages visited, tasks viewed and completed, time spent on platform, click patterns, and referral sources.</li>
      <li><strong style="color:#e2e8f0;">Cookies & Local Storage:</strong> See our <a href="/cookie-policy">Cookie Policy</a> for full details. We use cookies for session management, analytics, and advertising personalization.</li>
    </ul>

    <h2>2. How We Use Your Information</h2>
    <ul>
      <li>To create and manage your account and authenticate your identity.</li>
      <li>To process task completions, calculate earnings, and process withdrawals.</li>
      <li>To detect and prevent fraud, multiple account creation, bot activity, and violations of our Terms of Service.</li>
      <li>To communicate with you regarding your account, task status, withdrawal status, and platform updates.</li>
      <li>To personalize your experience on the platform, including task recommendations and offer targeting.</li>
      <li>To comply with legal obligations and enforce our Terms of Service.</li>
      <li>To improve our platform, analyze usage patterns, and develop new features.</li>
    </ul>

    <h2>3. Sharing Your Information with Third Parties</h2>
    <p>
      We value your privacy. We do not sell your personal information to third parties. However, we share certain data with the following categories of partners for legitimate operational purposes:
    </p>

    <h3>3.1 Advertising & Offerwall Networks</h3>
    <div class="highlight-box">
      <strong>Ad Network Data Sharing Disclosure:</strong> When you access offerwall content or complete advertiser-sponsored tasks, the following data may be shared with the respective ad network or advertiser: your anonymized User ID, task completion status, IP address (for geographic targeting and fraud verification), and device type. This is required for advertiser verification and postback crediting. We partner with ad networks including but not limited to those that may use this data for retargeting and audience segmentation in accordance with their own privacy policies.
    </div>
    <ul>
      <li>Offerwall providers may receive your User ID, IP address, and task completion data to verify conversions and credit your account.</li>
      <li>We do not share your name, email address, or payment details with advertising partners.</li>
      <li>Third-party ad networks operate under their own privacy policies. We encourage you to review those policies.</li>
    </ul>

    <h3>3.2 Payment Processors</h3>
    <p>
      To process withdrawals, we share necessary payment information (your mobile banking number and withdrawal amount) with the respective payment gateway provider (e.g., bKash, Nagad). This data is transmitted securely and used solely to process your payment.
    </p>

    <h3>3.3 Analytics Providers</h3>
    <p>
      We may use third-party analytics services to understand how users interact with our Platform. These services may receive anonymized usage data. We do not share personally identifiable information with analytics providers.
    </p>

    <h3>3.4 Legal Compliance</h3>
    <p>
      We may disclose your information to law enforcement authorities, government agencies, or other parties when required by law, court order, or when we believe in good faith that such disclosure is necessary to protect our rights, your safety, or the safety of others.
    </p>

    <h2>4. Data Retention</h2>
    <p>
      We retain your personal data for as long as your account is active or as needed to provide our services. If you delete your account, we will delete or anonymize your personal data within 30 days, except where retention is required by law (e.g., financial records) or for fraud prevention purposes (e.g., device fingerprint hashes associated with banned accounts).
    </p>
    <p>
      IP address logs and device fingerprint hashes associated with fraud investigations or banned accounts may be retained for up to 3 years to prevent re-registration by bad actors.
    </p>

    <h2>5. Data Security</h2>
    <p>
      We implement industry-standard security measures to protect your personal data, including:
    </p>
    <ul>
      <li>HTTPS encryption for all data in transit.</li>
      <li>bcrypt hashing for all passwords (never stored in plaintext).</li>
      <li>One-way hashing for device fingerprints.</li>
      <li>Access controls limiting employee access to personal data on a need-to-know basis.</li>
      <li>Regular security audits and vulnerability assessments.</li>
    </ul>
    <p>
      While we take reasonable measures to protect your data, no system is 100% secure. We cannot guarantee absolute security and are not responsible for unauthorized access resulting from user negligence (e.g., sharing credentials).
    </p>

    <h2>6. Your Rights</h2>
    <p>Depending on your jurisdiction, you may have the following rights regarding your personal data:</p>
    <ul>
      <li><strong style="color:#e2e8f0;">Right to Access:</strong> Request a copy of the personal data we hold about you.</li>
      <li><strong style="color:#e2e8f0;">Right to Correction:</strong> Request correction of inaccurate data.</li>
      <li><strong style="color:#e2e8f0;">Right to Deletion:</strong> Request deletion of your data (subject to legal retention requirements).</li>
      <li><strong style="color:#e2e8f0;">Right to Object:</strong> Object to the processing of your data for certain purposes, including direct marketing.</li>
      <li><strong style="color:#e2e8f0;">Right to Data Portability:</strong> Receive your data in a structured, machine-readable format.</li>
    </ul>
    <p>To exercise any of these rights, contact us at <a :href="'mailto:' + ($page.props.siteSettings?.support_email || 'support@easytsk.com')">{{ $page.props.siteSettings?.support_email || 'support@easytsk.com' }}</a>. We will respond within 30 days.</p>

    <h2>7. Children's Privacy</h2>
    <p>
      Our Platform is not intended for children under the age of 13. We do not knowingly collect personal information from children. If we become aware that we have collected data from a child under 13 without parental consent, we will delete that data promptly.
    </p>

    <h2>8. Changes to This Policy</h2>
    <p>
      We may update this Privacy Policy periodically. We will notify registered users of significant changes via email or a prominent notice on the Platform. Your continued use of the Platform after such notification constitutes your acceptance of the updated Policy.
    </p>

    <h2>9. Contact Us</h2>
    <p>For privacy-related questions or to exercise your data rights, contact:</p>
    <ul>
      <li><strong style="color:#e2e8f0;">Email:</strong> <a :href="'mailto:' + ($page.props.siteSettings?.support_email || 'support@easytsk.com')">{{ $page.props.siteSettings?.support_email || 'support@easytsk.com' }}</a></li>
      <li><strong style="color:#e2e8f0;">Address:</strong> Dhaka, Bangladesh</li>
    </ul>

  </LegalLayout>
</template>

<script setup>
import LegalLayout from '@/Layouts/LegalLayout.vue';
</script>
