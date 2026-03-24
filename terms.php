<!doctype html>
<html lang="en-GB">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Paralete | Terms & Conditions (Paralegals)</title>
  <meta name="description" content="Paralete Terms & Conditions for Paralegals." />

  <style>
    :root{
      --bg:#0b1220;
      --panel:#0f1b2e;
      --card:#111f36;
      --text:#e7eefc;
      --muted:#a9b6d2;
      --brand:#2f6bff;
      --brand2:#3cc8ff;
      --border:rgba(255,255,255,.10);
      --shadow: 0 18px 50px rgba(0,0,0,.35);
      --radius:18px;
      --radius2:14px;
      --max: 1120px;
      --font: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji","Segoe UI Emoji";
    }

    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      font-family:var(--font);
      color:var(--text);
      background:
        radial-gradient(1200px 600px at 15% -10%, rgba(47,107,255,.30), transparent 65%),
        radial-gradient(900px 500px at 85% 0%, rgba(60,200,255,.18), transparent 60%),
        radial-gradient(700px 450px at 50% 110%, rgba(47,107,255,.15), transparent 60%),
        var(--bg);
      line-height:1.55;
    }

    a{color:inherit}
    .container{max-width:var(--max); margin:0 auto; padding:28px 18px 80px;}
    .topbar{
      display:flex; align-items:center; justify-content:space-between;
      gap:14px; padding:14px 18px;
      border:1px solid var(--border);
      border-radius:var(--radius);
      background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
      box-shadow:var(--shadow);
      position:sticky; top:14px; z-index:50;
      backdrop-filter: blur(10px);
    }
    .brand{
      display:flex; align-items:center; gap:10px; text-decoration:none;
      font-weight:800; letter-spacing:.2px;
    }
    .logo{
      width:34px; height:34px; border-radius:12px;
      background: conic-gradient(from 220deg, var(--brand), var(--brand2), var(--brand));
      box-shadow: 0 10px 24px rgba(47,107,255,.25);
      position:relative;
    }
    .logo::after{
      content:""; position:absolute; inset:9px;
      border-radius:9px;
      background:rgba(11,18,32,.85);
      border:1px solid rgba(255,255,255,.10);
    }
    .brand span{opacity:.95}
    .top-actions{
      display:flex; gap:10px; align-items:center; flex-wrap:wrap;
    }
    .pill{
      font-size:12px; color:var(--muted);
      padding:6px 10px; border:1px solid var(--border);
      border-radius:999px; background:rgba(255,255,255,.03);
      white-space:nowrap;
    }
    .btn{
      appearance:none; border:1px solid var(--border);
      background:rgba(255,255,255,.03);
      color:var(--text);
      padding:9px 12px; border-radius:12px;
      font-weight:650; font-size:13px;
      cursor:pointer;
      transition: transform .08s ease, background .18s ease, border-color .18s ease;
      text-decoration:none;
    }
    .btn:hover{background:rgba(255,255,255,.06); border-color:rgba(255,255,255,.16)}
    .btn:active{transform: translateY(1px)}
    .btn.primary{
      background: linear-gradient(180deg, rgba(47,107,255,.95), rgba(47,107,255,.72));
      border-color: rgba(47,107,255,.50);
      box-shadow: 0 14px 34px rgba(47,107,255,.22);
    }
    .btn.primary:hover{background: linear-gradient(180deg, rgba(47,107,255,1), rgba(47,107,255,.78))}
    .hero{
      margin-top:20px;
      border:1px solid var(--border);
      border-radius: calc(var(--radius) + 4px);
      background: linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.02));
      box-shadow: var(--shadow);
      padding: 26px 22px;
      overflow:hidden;
      position:relative;
    }
    .hero::before{
      content:""; position:absolute; inset:-40px -40px auto auto; width:280px; height:280px;
      background: radial-gradient(circle at 30% 30%, rgba(60,200,255,.28), transparent 55%),
                  radial-gradient(circle at 70% 70%, rgba(47,107,255,.26), transparent 60%);
      filter: blur(2px);
      opacity:.9;
      transform: rotate(12deg);
    }
    .hero h1{
      margin:0 0 6px;
      font-size: clamp(26px, 3.2vw, 40px);
      letter-spacing:-.5px;
    }
    .hero p{
      margin:10px 0 0;
      color:var(--muted);
      max-width: 74ch;
    }
    .grid{
      display:grid;
      grid-template-columns: 320px 1fr;
      gap:18px;
      margin-top:18px;
      align-items:start;
    }
    .toc, .doc{
      border:1px solid var(--border);
      border-radius:var(--radius);
      background: rgba(255,255,255,.03);
      box-shadow: var(--shadow);
    }
    .toc{
      padding:16px 14px;
      position: sticky;
      top: 92px;
      max-height: calc(100vh - 120px);
      overflow:auto;
    }
    .toc h2{
      margin:0 0 8px;
      font-size:14px;
      letter-spacing:.3px;
      text-transform:uppercase;
      color: var(--muted);
    }
    .toc a{
      display:block;
      padding:9px 10px;
      border-radius:12px;
      text-decoration:none;
      color: var(--text);
      border:1px solid transparent;
      transition: background .16s ease, border-color .16s ease;
      font-size:14px;
    }
    .toc a:hover{
      background: rgba(255,255,255,.04);
      border-color: rgba(255,255,255,.10);
    }
    .doc{padding: 10px 18px 6px;}
    .section{
      padding: 16px 0;
      border-bottom:1px solid rgba(255,255,255,.08);
    }
    .section:last-child{border-bottom:none}
    .section h2{
      margin:0 0 10px;
      font-size:20px;
      color: #dbe8ff;
      letter-spacing:-.2px;
    }
    .section h3{
      margin:14px 0 8px;
      font-size:15px;
      color: #dbe8ff;
      letter-spacing:-.1px;
    }
    .meta{
      display:flex; gap:10px; flex-wrap:wrap;
      margin-top:12px;
    }
    .callout{
      margin: 14px 0 0;
      border:1px solid rgba(47,107,255,.35);
      background: linear-gradient(180deg, rgba(47,107,255,.16), rgba(47,107,255,.08));
      border-radius: var(--radius2);
      padding: 12px 12px;
      color: var(--text);
    }
    .callout strong{display:block; margin-bottom:6px}
    ul{margin:8px 0 0 18px; color: var(--text)}
    li{margin:6px 0; color: var(--text)}
    .muted{color:var(--muted)}
    .def-grid{
      display:grid; grid-template-columns: 1fr 1fr;
      gap:10px; margin-top:10px;
    }
    .def{
      border:1px solid rgba(255,255,255,.08);
      background: rgba(255,255,255,.02);
      border-radius: 14px;
      padding: 10px 10px;
    }
    .def b{display:block; margin-bottom:4px}
    .footer{
      margin-top:22px;
      padding:18px 18px;
      border:1px solid var(--border);
      border-radius: var(--radius);
      background: rgba(255,255,255,.03);
      box-shadow: var(--shadow);
      display:flex; justify-content:space-between; align-items:center; gap:10px;
      flex-wrap:wrap;
    }
    .fineprint{font-size:12px; color:var(--muted)}
    .accept{
      border:1px solid rgba(60,200,255,.35);
      background: linear-gradient(180deg, rgba(60,200,255,.14), rgba(60,200,255,.06));
      border-radius: var(--radius2);
      padding: 12px 12px;
      margin-top: 12px;
    }

    /* Mobile */
    @media (max-width: 980px){
      .grid{grid-template-columns: 1fr;}
      .toc{position:relative; top:auto; max-height:none}
      .topbar{position:relative; top:auto}
    }
    @media print{
      .topbar, .toc, .btn { display:none !important; }
      body{ background:#fff; color:#000; }
      .doc, .hero, .footer{ box-shadow:none; background:#fff; border-color:#ddd; }
      .section h2, .section h3{ color:#000; }
      .muted, .fineprint{ color:#333; }
    }
  </style>
</head>

<body>
  <div class="container">
    <!-- Top Bar -->
    <header class="topbar" role="banner">
      <a class="brand" href="/" aria-label="Paralete home">
        <div class="logo" aria-hidden="true"></div>
        <span>Paralete<span style="color:var(--brand2)">.</span></span>
      </a>

      <div class="top-actions">
        <span class="pill">Terms & Conditions — Paralegals</span>
        <span class="pill">Last Updated: 21 February 2026</span>
        <a class="btn" href="#acceptance">Jump to acceptance</a>
        <button class="btn primary" onclick="window.print()">Print / Save PDF</button>
      </div>
    </header>

    <!-- Hero -->
    <section class="hero" aria-label="Page introduction">
      <h1>Terms & Conditions (Paralegals)</h1>
      <p>
        These Terms set out the rules, responsibilities, and protections that apply when you provide services through Paralete.
        By registering as a Paralegal or using the Platform, you agree to these Terms.
      </p>

      <div class="meta">
        <span class="pill">Jurisdiction: England & Wales</span>
        <span class="pill">Business Hours: 9:00–17:00 (UK)</span>
        <span class="pill">Payments: via Platform only</span>
      </div>

      <div class="callout" role="note" aria-label="Important note">
        <strong>Important:</strong>
        Paralete is a marketplace platform. Contracts for Services are formed directly between you and the Employer.
        Paralete is not a law firm and does not provide legal advice.
      </div>
    </section>

    <div class="grid">
      <!-- TOC -->
      <aside class="toc" aria-label="Quick navigation">
        <h2>Quick Navigation</h2>
        <a href="#s1">1. Introduction</a>
        <a href="#s2">2. Definitions</a>
        <a href="#s3">3. Role of Paralete</a>
        <a href="#s4">4. Eligibility & Account</a>
        <a href="#s5">5. Use of the Platform</a>
        <a href="#s6">6. Service Engagement</a>
        <a href="#s7">7. Timesheets, Invoicing & Payment</a>
        <a href="#s8">8. Platform Fees</a>
        <a href="#s9">9. Non-Circumvention & Non-Solicitation</a>
        <a href="#s10">10. Cancellation & Contract Termination</a>
        <a href="#s11">11. Confidentiality</a>
        <a href="#s12">12. Data Protection (GDPR)</a>
        <a href="#s13">13. Intellectual Property</a>
        <a href="#s14">14. Warranties</a>
        <a href="#s15">15. Liability</a>
        <a href="#s16">16. Indemnity</a>
        <a href="#s17">17. Termination by Paralete</a>
        <a href="#s18">18. Dispute Resolution</a>
        <a href="#s19">19. Force Majeure</a>
        <a href="#s20">20. Assignment</a>
        <a href="#s21">21. Notices</a>
        <a href="#s22">22. Severability</a>
        <a href="#s23">23. Waiver</a>
        <a href="#s24">24. Entire Agreement</a>
        <a href="#s25">25. Amendments</a>
        <a href="#s26">26. Governing Law</a>
      </aside>

      <!-- Document -->
      <main class="doc" role="main" aria-label="Terms and Conditions content">

        <section id="s1" class="section">
          <h2>1. Introduction</h2>
          <p>
            These Paralegal Terms and Conditions (“Terms”) govern your access to and use of the Paralete.com platform
            (the “Platform”), operated by Paralete (“we”, “us”, “our”), as a provider of paralegal or legal support services (“Paralegal”, “you”).
          </p>
          <p>
            By registering as a Paralegal or using the Platform, you accept these Terms. If you do not agree, do not use the Platform.
          </p>
          <p>
            These Terms form a legally binding agreement between you and Paralete. Additional policies published on the Platform form part of these Terms.
          </p>
        </section>

        <section id="s2" class="section">
          <h2>2. Definitions</h2>
          <p class="muted">Key terms used throughout these Terms:</p>
          <div class="def-grid">
            <div class="def"><b>Account</b> Your Paralegal profile on the Platform.</div>
            <div class="def"><b>Employer / Client</b> Any user seeking Services.</div>
            <div class="def"><b>Services</b> Paralegal/legal support services you provide.</div>
            <div class="def"><b>Contract</b> Agreement between you and Employer for Services.</div>
            <div class="def"><b>Fees</b> Amount payable by Employer to you.</div>
            <div class="def"><b>Platform Fee</b> Commission or charges retained by Paralete.</div>
            <div class="def"><b>Deliverables</b> Work product resulting from Services.</div>
            <div class="def"><b>Confidential Information</b> Non-public information.</div>
            <div class="def"><b>GDPR</b> UK GDPR and Data Protection Act 2018.</div>
            <div class="def"><b>Business Hours</b> 9:00am–5:00pm (UK time).</div>
          </div>
        </section>

        <section id="s3" class="section">
          <h2>3. Role of Paralete</h2>
          <ul>
            <li>The Platform is a marketplace enabling Paralegals and Employers to connect.</li>
            <li>Paralete does not provide legal advice or represent either party.</li>
            <li>You are an independent contractor, not an employee or agent of Paralete.</li>
            <li>Paralete is not responsible for Contracts or outcomes between you and Employers.</li>
          </ul>
        </section>

        <section id="s4" class="section">
          <h2>4. Eligibility & Account</h2>
          <ul>
            <li>You must be 18+ and legally capable of entering into contracts.</li>
            <li>You warrant you have the skills, qualifications, and compliance with applicable laws.</li>
            <li>You must keep Account information accurate and updated.</li>
            <li>You are responsible for all activities under your Account and for keeping login details secure.</li>
            <li>Paralete may suspend or terminate Accounts for breach or misuse.</li>
          </ul>
        </section>

        <section id="s5" class="section">
          <h2>5. Use of the Platform</h2>
          <p>You must use the Platform only for lawful purposes and for offering Services.</p>
          <h3>Prohibited behaviour includes:</h3>
          <ul>
            <li>Providing false or misleading information.</li>
            <li>Contacting Employers off-platform to avoid Platform Fees.</li>
            <li>Interfering with other users or Platform functionality.</li>
            <li>Unlawful, discriminatory, or unethical conduct.</li>
          </ul>
        </section>

        <section id="s6" class="section">
          <h2>6. Service Engagement</h2>
          <ul>
            <li>Employers may post job requests.</li>
            <li>You may submit proposals with scope, timeline, and Fees.</li>
            <li>Upon Employer acceptance, a Contract forms directly between you and the Employer.</li>
            <li>You must perform Services with due skill, care, and reasonable diligence.</li>
            <li>Employers must provide necessary information to enable performance.</li>
          </ul>
        </section>

        <section id="s7" class="section">
          <h2>7. Timesheets, Invoicing & Payment</h2>

          <h3>7.1 Daily Timesheets</h3>
          <p>
            You shall submit accurate daily Timesheets via the Platform recording all time spent providing the Services.
            Timesheets must be submitted by the end of the relevant working day and, where work continues beyond Business Hours,
            no later than <b>1:00pm</b> on the following Business Day.
          </p>

          <h3>7.2 Employer Review</h3>
          <p>
            Upon submission, the Employer has <b>48 hours</b> to approve the Timesheet or raise a written objection through the Platform.
          </p>

          <h3>7.3 Objections</h3>
          <p>Any objection must specify, with reasonable detail, the entries disputed and the grounds for dispute.</p>

          <h3>7.4 Deemed Acceptance</h3>
          <p>Failure to raise a written objection within 48 hours constitutes deemed acceptance.</p>

          <h3>7.5 Paralegal Response</h3>
          <p>You shall respond to any Timesheet objection within <b>24 hours</b>, providing clarification or evidence as reasonably required.</p>

          <h3>7.6 Basis of Invoice</h3>
          <p>Approved or deemed-approved Timesheets form the basis of your monthly invoice.</p>

          <h3>7.7 Monthly Invoice Submission</h3>
          <p>You shall submit an invoice via the Platform within the first <b>3 days</b> of the following calendar month.</p>

          <h3>7.8 Payment Obligation</h3>
          <p>Subject to any properly raised dispute, the Employer shall pay your invoice within <b>7 days</b> of submission via the Platform.</p>

          <h3>7.9 Disputed Amounts</h3>
          <p>Where a dispute is raised, the Employer shall within the same 7-day period pay all undisputed amounts.</p>

          <h3>7.10 Referral to Paralete</h3>
          <p>
            Where a Timesheet dispute remains unresolved following your response, both parties agree Paralete may act as an independent adjudicator
            solely to determine whether the disputed time was reasonably and properly incurred.
          </p>

          <h3>7.11 Binding Decision</h3>
          <p>Paralete shall issue a final and binding decision within <b>14 days</b> of receiving your response.</p>

          <h3>7.12 Payment Following Decision</h3>
          <p>Following a decision, the Employer shall pay any amounts determined to be payable within <b>7 days</b>.</p>

          <h3>7.13 Late Payment Interest</h3>
          <p>Any sums not paid when due shall accrue interest at <b>4% above the Bank of England base rate</b>, calculated daily.</p>

          <h3>7.14 Suspension for Non-Payment</h3>
          <p>Where payment is overdue by more than <b>7 days</b>, you may suspend further Services until payment is received in full.</p>
        </section>

        <section id="s8" class="section">
          <h2>8. Platform Fees</h2>
          <ul>
            <li>Fees are agreed between you and the Employer.</li>
            <li>Paralete charges a percentage-based Platform Fee on all Fees earned.</li>
            <li>All payments must be made via the Platform (no off-platform payments).</li>
            <li>Paralete may use escrow or milestone releases.</li>
            <li>Funds are released to you net of Platform Fees.</li>
            <li>You are responsible for your taxes, VAT, and filings and will not be regarded as an employee of Paralete or the Employer.</li>
          </ul>
        </section>

        <section id="s9" class="section">
          <h2>9. Non-Circumvention & Non-Solicitation</h2>
          <p>You must not solicit or accept work or payment from Employers outside the Platform once introduced.</p>
          <h3>Restriction Period</h3>
          <p>
            For <b>24 months</b> following the last engagement with any Employer or termination of your Account (whichever is later), you must not, directly or indirectly:
          </p>
          <ul>
            <li>Engage or attempt to engage with the Employer outside the Platform.</li>
            <li>Solicit or accept work or payment from the Employer (or associates) outside the Platform.</li>
            <li>Induce the Employer to terminate use of the Platform.</li>
          </ul>
          <div class="callout">
            <strong>Fixed Penalty:</strong>
            You shall pay Paralete <b>£5,000</b> if you breach this clause. Paralete may also seek additional damages and injunctive relief.
          </div>
        </section>

        <section id="s10" class="section">
          <h2>10. Cancellation & Contract Termination</h2>
          <ul>
            <li>Contracts may be cancelled as agreed between the parties.</li>
            <li>If an Employer cancels after work begins, you may be entitled to payment for work completed.</li>
            <li>If you cancel, you must provide reasonable notice; the Employer may be entitled to a refund or replacement.</li>
            <li>A breach may entitle the other party to terminate and seek remedies.</li>
            <li>Paralete may, but is not obligated to, offer dispute mediation.</li>
          </ul>
        </section>

        <section id="s11" class="section">
          <h2>11. Confidentiality</h2>
          <ul>
            <li>You may receive Employer or third-party Confidential Information.</li>
            <li>You must safeguard and not disclose Confidential Information except to fulfil Services or as required by law.</li>
            <li>Confidentiality obligations survive termination indefinitely.</li>
          </ul>
        </section>

        <section id="s12" class="section">
          <h2>12. Data Protection (GDPR)</h2>
          <ul>
            <li>You are an independent data controller for personal data you handle.</li>
            <li>You must process data lawfully, securely, and only for service delivery.</li>
            <li>You must delete or return data on request or at completion unless legally required to retain.</li>
            <li>You must promptly report data breaches as required by law to the Employer.</li>
          </ul>
        </section>

        <section id="s13" class="section">
          <h2>13. Intellectual Property</h2>
          <ul>
            <li>IP in Deliverables created for an Employer transfers to the Employer upon full payment unless agreed otherwise.</li>
            <li>You retain pre-existing IP and general know-how.</li>
            <li>You grant Paralete a limited licence to display non-confidential work samples or profile content on the Platform.</li>
          </ul>
        </section>

        <section id="s14" class="section">
          <h2>14. Warranties</h2>
          <p>You warrant that:</p>
          <ul>
            <li>You have full authority to enter these Terms.</li>
            <li>Your Services will be of professional quality.</li>
            <li>You will not infringe third-party rights.</li>
          </ul>
          <p class="muted">Paralete disclaims all warranties regarding the performance of other users on the Platform.</p>
        </section>

        <section id="s15" class="section">
          <h2>15. Liability</h2>
          <ul>
            <li>Nothing excludes liability for death, personal injury, or fraud.</li>
            <li>Paralete is not liable for loss of profits, data, business, reputation, or indirect/consequential loss.</li>
            <li>Paralete is not liable for Paralegal or Employer conduct or outcomes of Contracts.</li>
            <li>Paralete’s total liability to you is limited to the total Platform Fees paid by your Employer(s) in the preceding 12 months.</li>
          </ul>
        </section>

        <section id="s16" class="section">
          <h2>16. Indemnity</h2>
          <ul>
            <li>You indemnify Paralete (and its agents) and Employers against claims arising from your breach, infringement, or unlawful acts.</li>
            <li>This indemnity survives termination.</li>
          </ul>
        </section>

        <section id="s17" class="section">
          <h2>17. Termination by Paralete</h2>
          <p>Paralete may suspend or terminate your Account immediately if you:</p>
          <ul>
            <li>Breach these Terms or a Contract;</li>
            <li>Commit fraud or misconduct;</li>
            <li>Attempt circumvention;</li>
            <li>Damage the Platform or reputation.</li>
          </ul>
          <p class="muted">Termination does not affect accrued rights.</p>
        </section>

        <section id="s18" class="section">
          <h2>18. Dispute Resolution</h2>
          <ul>
            <li>You should attempt to resolve disputes directly with the Employer.</li>
            <li>Paralete may offer binding mediation for cost-effective disposal.</li>
            <li>Disputes are governed by the laws of England and Wales, with exclusive jurisdiction of its courts.</li>
          </ul>
        </section>

        <section id="s19" class="section">
          <h2>19. Force Majeure</h2>
          <p>No party is liable for delays or failures caused by events beyond reasonable control. The affected party must notify and mitigate where possible.</p>
        </section>

        <section id="s20" class="section">
          <h2>20. Assignment</h2>
          <ul>
            <li>You may not assign these Terms without Paralete’s consent.</li>
            <li>Paralete may assign rights and obligations to affiliates or successors.</li>
          </ul>
        </section>

        <section id="s21" class="section">
          <h2>21. Notices</h2>
          <ul>
            <li>Notices may be given via email or Platform notifications.</li>
            <li>Notices are deemed delivered upon sending during Business Hours.</li>
          </ul>
        </section>

        <section id="s22" class="section">
          <h2>22. Severability</h2>
          <p>If any provision is invalid, the remainder remains enforceable.</p>
        </section>

        <section id="s23" class="section">
          <h2>23. Waiver</h2>
          <p>No failure to enforce a right constitutes a waiver.</p>
        </section>

        <section id="s24" class="section">
          <h2>24. Entire Agreement</h2>
          <p>These Terms and related policies form the entire agreement, superseding previous understandings.</p>
        </section>

        <section id="s25" class="section">
          <h2>25. Amendments</h2>
          <p>Paralete may update these Terms with notice. Continued use constitutes acceptance.</p>
        </section>

        <section id="s26" class="section">
          <h2>26. Governing Law</h2>
          <p>These Terms are governed by the laws of England and Wales. The courts of England and Wales have exclusive jurisdiction.</p>

          <div id="acceptance" class="accept">
            <b>Acceptance:</b> By registering as a Paralegal or using the Platform, you confirm you have read, understood, and agree to these Terms.
          </div>
        </section>
      </main>
    </div>

    <footer class="footer" role="contentinfo">
      <div class="fineprint">
        <b>Paralete</b> © <span id="year"></span> — All rights reserved.
        <span class="muted"> | These Terms apply to Paralegals using Paralete.com.</span>
      </div>
      <div style="display:flex; gap:10px; align-items:center;">
        <a class="btn" href="#top" onclick="window.scrollTo({top:0, behavior:'smooth'}); return false;">Back to top</a>
        <button class="btn" onclick="navigator.clipboard?.writeText(window.location.href).catch(()=>{}); alert('Link copied (if your browser allows it).');">Copy page link</button>
      </div>
    </footer>
  </div>

  <script>
    // Footer year
    document.getElementById("year").textContent = new Date().getFullYear();

    // Smooth scroll for TOC links
    document.querySelectorAll('.toc a').forEach(a=>{
      a.addEventListener('click', (e)=>{
        const id = a.getAttribute('href');
        const el = document.querySelector(id);
        if(!el) return;
        e.preventDefault();
        el.scrollIntoView({behavior:'smooth', block:'start'});
        history.replaceState(null, '', id);
      });
    });
  </script>
</body>
</html>