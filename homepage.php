<?php
  // Basic config
  $company = "Inclusive Networks";
  $tagline = "Trusted cybersecurity distribution, made inclusive.";
  $phone = "+65 6000 0000";
  $email = "hello@inclusivenetworks.example";
  $address = "Somewhere in Singapore";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($company); ?> — <?php echo htmlspecialchars($tagline); ?></title>
  <meta name="description" content="Inclusive Networks: a cybersecurity-focused distributor with services-first mindset, global reach, and local expertise." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <header class="site-header">
    <div class="container header-wrap">
      <a class="brand" href="/">
        <img src="assets/logo.svg" alt="<?php echo htmlspecialchars($company); ?> logo" class="logo" />
        <span class="brand-name"><?php echo htmlspecialchars($company); ?></span>
      </a>
      <nav class="nav">
        <a href="#why">Why us</a>
        <a href="#partners">Partners</a>
        <a href="#services">Services</a>
        <a class="cta" href="#contact">Contact</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="container hero-wrap">
        <h1><?php echo htmlspecialchars($tagline); ?></h1>
        <p>Specialist distribution to accelerate a trusted digital world—global perspective, local execution, and service-first delivery for cybersecurity and digital infrastructure ecosystems.</p>
        <div class="hero-ctas">
          <a href="#why" class="btn primary">Discover why</a>
          <a href="#contact" class="btn ghost">Talk to us</a>
        </div>
      </div>
    </section>

    <section id="why" class="section">
      <div class="container two-col">
        <div>
          <h2>Why Inclusive</h2>
          <p>Purpose-built for partner value: enable resellers, MSPs, MSSPs, and integrators to capture fast-evolving security demand with curated vendors, enablement, and lifecycle services.</p>
          <ul class="bullets">
            <li>Services-first mindset with enablement, support, and managed offerings.</li>
            <li>Global reach with local expertise across APAC partners.</li>
            <li>Portfolio aligned to modern architectures and cloud models.</li>
          </ul>
        </div>
        <div class="card">
          <h3>At a glance</h3>
          <ul class="stats">
            <li><strong>Focus:</strong> Cybersecurity distribution</li>
            <li><strong>Model:</strong> Channel-first</li>
            <li><strong>Delivery:</strong> Services-led</li>
            <li><strong>Location:</strong> Singapore HQ</li>
          </ul>
        </div>
      </div>
    </section>

    <section id="partners" class="section alt">
      <div class="container">
        <h2>Partner ecosystem</h2>
        <p>Built for value-added resellers, global and regional MSPs/MSSPs, and consultancies—connecting innovative vendors with high-performing channel partners.</p>
        <div class="logos">
          <div class="logo-box">Vendor A</div>
          <div class="logo-box">Vendor B</div>
          <div class="logo-box">Vendor C</div>
          <div class="logo-box">Vendor D</div>
        </div>
      </div>
    </section>

    <section id="services" class="section">
      <div class="container grid-3">
        <div class="service">
          <h3>Distribution</h3>
          <p>Selective cybersecurity vendors with enablement, presales support, and demand programs.</p>
        </div>
        <div class="service">
          <h3>Professional services</h3>
          <p>Design, deployment, and migration assistance to accelerate time-to-value.</p>
        </div>
        <div class="service">
          <h3>Managed services</h3>
          <p>Co-managed and fully managed offerings to extend partner capabilities.</p>
        </div>
      </div>
    </section>

    <section id="contact" class="section cta-band">
      <div class="container cta-wrap">
        <h2>Ready to collaborate?</h2>
        <p>Reach the team for partnerships, solutions, or opportunities.</p>
        <div class="contact-grid">
          <div class="contact-card">
            <h4>Call</h4>
            <p><?php echo htmlspecialchars($phone); ?></p>
          </div>
          <div class="contact-card">
            <h4>Email</h4>
            <p><a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a></p>
          </div>
          <div class="contact-card">
            <h4>Visit</h4>
            <p><?php echo htmlspecialchars($address); ?></p>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-wrap">
      <div class="foot-left">
        <span>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($company); ?>. All rights reserved.</span>
      </div>
      <div class="foot-right">
        <a href="#">Privacy</a>
        <a href="#">Terms</a>
      </div>
    </div>
  </footer>
</body>
</html>
