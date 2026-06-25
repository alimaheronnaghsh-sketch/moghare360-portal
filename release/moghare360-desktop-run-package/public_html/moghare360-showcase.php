<?php
declare(strict_types=1);

/**
 * MOGHARE360 ERP — Static Showcase Page
 * Demo only. No database. No auth. No external assets.
 */
header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>MOGHARE360 ERP — Soft Run Version 1.0</title>
  <style>
    :root {
      --bg-deep: #0a0e17;
      --bg-card: #121a2b;
      --bg-card-hover: #182238;
      --border: rgba(148, 163, 184, 0.14);
      --text: #f1f5f9;
      --text-muted: #94a3b8;
      --accent: #3b82f6;
      --accent-glow: rgba(59, 130, 246, 0.35);
      --gold: #f59e0b;
      --success: #22c55e;
      --purple: #a78bfa;
      --cyan: #22d3ee;
      --radius: 16px;
      --shadow: 0 24px 48px rgba(0, 0, 0, 0.45);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: Vazirmatn, Tahoma, Arial, sans-serif;
      background: var(--bg-deep);
      color: var(--text);
      line-height: 1.7;
      min-height: 100vh;
      overflow-x: hidden;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background:
        radial-gradient(ellipse 80% 50% at 20% -10%, rgba(59, 130, 246, 0.18), transparent),
        radial-gradient(ellipse 60% 40% at 90% 10%, rgba(167, 139, 250, 0.12), transparent),
        radial-gradient(ellipse 50% 30% at 50% 100%, rgba(34, 211, 238, 0.08), transparent);
      pointer-events: none;
      z-index: 0;
    }

    .wrap {
      position: relative;
      z-index: 1;
      max-width: 1180px;
      margin: 0 auto;
      padding: 2rem 1.25rem 3rem;
    }

    /* Hero */
    .hero {
      text-align: center;
      padding: 3.5rem 1.5rem 3rem;
      margin-bottom: 2.5rem;
      border-radius: calc(var(--radius) + 4px);
      background: linear-gradient(145deg, rgba(18, 26, 43, 0.95) 0%, rgba(10, 14, 23, 0.98) 100%);
      border: 1px solid var(--border);
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }

    .hero::after {
      content: '';
      position: absolute;
      top: -50%;
      left: -20%;
      width: 60%;
      height: 200%;
      background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.06), transparent);
      transform: rotate(-12deg);
      pointer-events: none;
    }

    .hero-badge {
      display: inline-block;
      padding: 0.45rem 1.1rem;
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 700;
      letter-spacing: 0.02em;
      background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 211, 238, 0.15));
      border: 1px solid rgba(34, 197, 94, 0.45);
      color: #86efac;
      margin-bottom: 1.25rem;
    }

    .hero h1 {
      font-size: clamp(2rem, 6vw, 3.2rem);
      font-weight: 800;
      letter-spacing: -0.02em;
      background: linear-gradient(135deg, #fff 0%, #cbd5e1 50%, #94a3b8 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 0.75rem;
    }

    .hero-sub {
      font-size: clamp(1rem, 2.5vw, 1.2rem);
      color: var(--text-muted);
      max-width: 640px;
      margin: 0 auto 1.5rem;
    }

    .hero-meta {
      font-size: 0.85rem;
      color: var(--cyan);
      opacity: 0.9;
    }

    /* Section */
    section {
      margin-bottom: 2.5rem;
    }

    .section-title {
      font-size: 1.35rem;
      font-weight: 700;
      margin-bottom: 1.25rem;
      padding-inline-start: 0.75rem;
      border-inline-start: 4px solid var(--accent);
    }

    /* Progress cards */
    .progress-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
    }

    .progress-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.15rem 1.25rem;
      transition: transform 0.2s, border-color 0.2s, background 0.2s;
    }

    .progress-card:hover {
      transform: translateY(-3px);
      background: var(--bg-card-hover);
      border-color: rgba(59, 130, 246, 0.35);
    }

    .progress-card h3 {
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: var(--text);
    }

    .status-badge {
      display: inline-block;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 0.25rem 0.6rem;
      border-radius: 6px;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .status-badge.done {
      background: rgba(34, 197, 94, 0.2);
      color: #86efac;
      border: 1px solid rgba(34, 197, 94, 0.4);
    }

    .status-badge.ready {
      background: rgba(59, 130, 246, 0.2);
      color: #93c5fd;
      border: 1px solid rgba(59, 130, 246, 0.4);
    }

    /* Flow timeline */
    .flow-panel {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.75rem 1.5rem;
      overflow-x: auto;
    }

    .flow-track {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: center;
      gap: 0.5rem 0.35rem;
    }

    .flow-step {
      display: flex;
      align-items: center;
      gap: 0.35rem;
    }

    .flow-node {
      background: linear-gradient(145deg, #1e293b, #0f172a);
      border: 1px solid rgba(59, 130, 246, 0.35);
      border-radius: 10px;
      padding: 0.55rem 0.85rem;
      font-size: 0.78rem;
      font-weight: 600;
      white-space: nowrap;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .flow-arrow {
      color: var(--gold);
      font-size: 1rem;
      opacity: 0.85;
    }

    /* Built grid */
    .built-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1rem;
    }

    .built-item {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1rem 1.1rem;
    }

    .built-icon {
      flex-shrink: 0;
      width: 2.25rem;
      height: 2.25rem;
      border-radius: 8px;
      background: linear-gradient(135deg, var(--accent), #6366f1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.85rem;
      font-weight: 800;
    }

    .built-item p {
      font-size: 0.88rem;
      color: var(--text-muted);
    }

    /* Vision & boundary */
    .dual-panel {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.25rem;
    }

    .panel {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem 1.4rem;
    }

    .panel h3 {
      font-size: 1.05rem;
      margin-bottom: 0.75rem;
      color: var(--purple);
    }

    .panel p {
      font-size: 0.92rem;
      color: var(--text-muted);
    }

    .boundary-list {
      list-style: none;
      margin-top: 0.75rem;
    }

    .boundary-list li {
      font-size: 0.88rem;
      padding: 0.4rem 0;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      gap: 0.5rem;
    }

    .boundary-list li:last-child { border-bottom: none; }

    .tag-current {
      color: var(--success);
      font-weight: 600;
      font-size: 0.8rem;
    }

    .tag-future {
      color: var(--text-muted);
      font-size: 0.8rem;
    }

    /* Next phase */
    .next-list {
      list-style: none;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 0.75rem;
    }

    .next-list li {
      background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 0.85rem 1rem;
      font-size: 0.88rem;
      position: relative;
      padding-inline-start: 2rem;
    }

    .next-list li::before {
      content: '→';
      position: absolute;
      inset-inline-start: 0.75rem;
      color: var(--gold);
      font-weight: 700;
    }

    /* Module strip */
    .module-strip {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-top: 1rem;
    }

    .module-pill {
      font-size: 0.75rem;
      padding: 0.35rem 0.7rem;
      border-radius: 6px;
      background: rgba(59, 130, 246, 0.12);
      border: 1px solid rgba(59, 130, 246, 0.25);
      color: #93c5fd;
    }

    /* Footer */
    footer {
      text-align: center;
      padding: 2rem 1rem;
      margin-top: 2rem;
      border-top: 1px solid var(--border);
      color: var(--text-muted);
      font-size: 0.88rem;
    }

    footer strong {
      display: block;
      color: var(--text);
      font-size: 1rem;
      margin-bottom: 0.35rem;
    }

    .demo-note {
      text-align: center;
      font-size: 0.75rem;
      color: #64748b;
      margin-top: 1.5rem;
      padding: 0.65rem;
      border-radius: 8px;
      background: rgba(15, 23, 42, 0.6);
    }

    @media (max-width: 600px) {
      .wrap { padding: 1.25rem 1rem 2rem; }
      .hero { padding: 2.5rem 1rem 2rem; }
      .flow-node { font-size: 0.7rem; padding: 0.45rem 0.6rem; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <header class="hero">
      <span class="hero-badge">Soft Run Version 1.0 Ready</span>
      <h1>MOGHARE360 ERP</h1>
      <p class="hero-sub">نرم‌افزار جامع مدیریت تعمیرگاه، پذیرش، عملیات، مالی، CRM و کنترل فرآیند</p>
      <p class="hero-meta">سیستم عامل تعمیرگاه — از فرآیند کارگاه تا محصول نرم‌افزاری</p>
    </header>

    <section aria-labelledby="progress-title">
      <h2 class="section-title" id="progress-title">خلاصه پیشرفت محصول</h2>
      <div class="progress-grid">
        <article class="progress-card">
          <h3>Core ERP Foundation</h3>
          <span class="status-badge done">Completed</span>
        </article>
        <article class="progress-card">
          <h3>Access Workflow</h3>
          <span class="status-badge done">Completed</span>
        </article>
        <article class="progress-card">
          <h3>Customer &amp; Vehicle</h3>
          <span class="status-badge ready">Ready</span>
        </article>
        <article class="progress-card">
          <h3>JobCard</h3>
          <span class="status-badge ready">Ready</span>
        </article>
        <article class="progress-card">
          <h3>Service Operation</h3>
          <span class="status-badge ready">Ready</span>
        </article>
        <article class="progress-card">
          <h3>Finance Preview</h3>
          <span class="status-badge ready">Ready</span>
        </article>
        <article class="progress-card">
          <h3>Soft Run</h3>
          <span class="status-badge ready">Ready</span>
        </article>
      </div>
    </section>

    <section aria-labelledby="flow-title">
      <h2 class="section-title" id="flow-title">جریان اصلی عملیات</h2>
      <div class="flow-panel">
        <div class="flow-track">
          <div class="flow-step"><span class="flow-node">مشتری</span><span class="flow-arrow">←</span></div>
          <div class="flow-step"><span class="flow-node">خودرو</span><span class="flow-arrow">←</span></div>
          <div class="flow-step"><span class="flow-node">کارت کار</span><span class="flow-arrow">←</span></div>
          <div class="flow-step"><span class="flow-node">عملیات سرویس</span><span class="flow-arrow">←</span></div>
          <div class="flow-step"><span class="flow-node">قطعه / خرید</span><span class="flow-arrow">←</span></div>
          <div class="flow-step"><span class="flow-node">پیش‌نمایش مالی</span><span class="flow-arrow">←</span></div>
          <div class="flow-step"><span class="flow-node">کنترل کیفیت</span><span class="flow-arrow">←</span></div>
          <div class="flow-step"><span class="flow-node">تحویل</span><span class="flow-arrow">←</span></div>
          <div class="flow-step"><span class="flow-node">پیگیری CRM</span></div>
        </div>
      </div>
    </section>

    <section aria-labelledby="built-title">
      <h2 class="section-title" id="built-title">آنچه ساخته شده است</h2>
      <div class="built-grid">
        <article class="built-item">
          <span class="built-icon">CU</span>
          <p>پروفایل مشتری و خودرو با ارتباط مالکیت و تاریخچه خدمات</p>
        </article>
        <article class="built-item">
          <span class="built-icon">JC</span>
          <p>چرخه کنترل‌شده کارت کار از پذیرش تا بستن پرونده</p>
        </article>
        <article class="built-item">
          <span class="built-icon">WF</span>
          <p>بنیان گردش کار، مجوزها و audit trail عملیاتی</p>
        </article>
        <article class="built-item">
          <span class="built-icon">SO</span>
          <p>ردیابی عملیات سرویس و آمادگی تکنسین</p>
        </article>
        <article class="built-item">
          <span class="built-icon">PI</span>
          <p>مصرف قطعه و درخواست خرید با کنترل موجودی</p>
        </article>
        <article class="built-item">
          <span class="built-icon">PY</span>
          <p>پیش‌نمایش پرداخت و خلاصه مالی JobCard</p>
        </article>
        <article class="built-item">
          <span class="built-icon">QC</span>
          <p>کنترل کیفیت و آمادگی تحویل (Soft Run Gate)</p>
        </article>
        <article class="built-item">
          <span class="built-icon">SR</span>
          <p>داشبورد Soft Run برای استفاده روزانه داخلی تعمیرگاه</p>
        </article>
      </div>
      <div class="module-strip">
        <span class="module-pill">Design System</span>
        <span class="module-pill">Application Shell</span>
        <span class="module-pill">JobCard UX</span>
        <span class="module-pill">Customer UX</span>
        <span class="module-pill">Service UX</span>
        <span class="module-pill">Finance Preview</span>
        <span class="module-pill">Moghare Ready</span>
      </div>
    </section>

    <section aria-labelledby="vision-title">
      <div class="dual-panel">
        <article class="panel">
          <h3 id="vision-title">چشم‌انداز محصول</h3>
          <p>
            MOGHARE360 فقط یک وب‌سایت نیست. این یک <strong style="color:var(--text);">سیستم عامل تعمیرگاه</strong> است
            که گام‌به‌گام برای تبدیل شدن به یک <strong style="color:var(--text);">محصول ERP قابل فروش</strong> طراحی شده است.
            هر ماژول با کنترل فنی، مستندسازی مأموریت و تست مرحله‌ای ساخته شده تا از کارگاه واقعی به نرم‌افزار پایدار برسد.
          </p>
        </article>
        <article class="panel">
          <h3>مرز نسخه فعلی</h3>
          <ul class="boundary-list">
            <li><span>نسخه جاری</span><span class="tag-current">Moghareh Internal Soft Run</span></li>
            <li><span>SaaS</span><span class="tag-future">هنوز نه</span></li>
            <li><span>پرتال مشتری</span><span class="tag-future">هنوز نه</span></li>
            <li><span>حسابداری نهایی</span><span class="tag-future">هنوز نه</span></li>
            <li><span>انتشار تجاری چندمستأجری</span><span class="tag-future">هنوز نه</span></li>
          </ul>
        </article>
      </div>
    </section>

    <section aria-labelledby="next-title">
      <h2 class="section-title" id="next-title">مسیر بعدی</h2>
      <ul class="next-list">
        <li>موتور قرارداد و قیمت‌گذاری</li>
        <li>پرتال مشتری</li>
        <li>پیگیری CRM</li>
        <li>لایه فروش و حسابداری</li>
        <li>دموی تجاری</li>
        <li>بسته‌بندی SaaS</li>
      </ul>
    </section>

    <p class="demo-note">این صفحه نمایشی است — بدون اتصال پایگاه داده · مناسب ارائه محلی</p>

    <footer>
      <strong>Built step by step as a controlled ERP product.</strong>
      MOGHARE360 — From workshop process to software product.
    </footer>
  </div>
</body>
</html>
