<x-legal-page title="Privacy Policy" updated="27 November 2026"
              description="What personal data BandarOS collects, why, and the choices you have.">
    <p>This policy explains what personal data BandarOS collects, why we need it and what you can do about it. It is written with Malaysia’s Personal Data Protection Act 2010 (PDPA) in mind.</p>

    <h2>What we collect</h2>
    <ul>
        <li><strong>Account details:</strong> your name, email address and password (stored only as a secure hash, never in plain text). If you turn on two-factor login, an encrypted secret and recovery codes.</li>
        <li><strong>Shop data:</strong> the products, categories, stock figures and photos you or your team add.</li>
        <li><strong>Security records:</strong> your IP address and browser for active sessions, and an audit log of changes made in your shop (who did what, and when).</li>
    </ul>

    <h2>Why we use it</h2>
    <ul>
        <li>To run the service: sign you in, show your shop’s data and send the emails you ask for, such as invitations, password resets and stock alerts.</li>
        <li>To keep accounts safe: spot unusual sign-ins, let you sign out other devices, and let shop owners see what changed.</li>
    </ul>
    <p>We don’t sell your data, and we don’t use it for advertising.</p>

    <h2>Who can see it</h2>
    <p>Members of your shop can see the shop’s data, according to their role. Other shops can never see it. Your name and email are visible to the shops you belong to.</p>

    <h2>Cookies</h2>
    <p>BandarOS uses only the cookies it needs to work: a session cookie that keeps you signed in, and a security token that protects forms. Your light or dark theme choice is saved in your browser. There are no tracking or advertising cookies.</p>

    <h2>How long we keep it</h2>
    <ul>
        <li>Account and shop data: for as long as your account is open.</li>
        <li>Audit log entries: one year, then deleted automatically.</li>
        <li>Exported files: {{ config('bandaros.keep_exports_for_days') }} days, then deleted automatically.</li>
    </ul>

    <h2>Your rights</h2>
    <p>Under the PDPA you may ask to see the personal data held about you, to correct it, or to withdraw your consent and have your account closed. You can update your name, email and password yourself under Account settings. For anything else, contact the team running this BandarOS installation.</p>

    <h2>Changes to this policy</h2>
    <p>If we change this policy, we will update the date at the top of this page and, for important changes, tell you by email.</p>
</x-legal-page>
