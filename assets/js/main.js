/**
 * ClubHub - Script tương tác giao diện chính
 */

document.addEventListener('DOMContentLoaded', () => {
  initMobileMenu();
  initBannerSlider();
  initLightbox();
  initShareButtons();
  initReportModal();
  initCelestialParticles();
  initClubSpecificMotionEffects();
});

/**
 * Menu trên điện thoại (Mobile Menu Hamburger)
 */
function initMobileMenu() {
  const toggle = document.querySelector('.mobile-menu-toggle');
  const menu = document.querySelector('.nav-menu');
  if (!toggle || !menu) return;

  toggle.addEventListener('click', () => {
    const expanded = toggle.getAttribute('aria-expanded') === 'true' || false;
    toggle.setAttribute('aria-expanded', !expanded);
    menu.classList.toggle('active');
  });

  // Đóng khi click ra ngoài
  document.addEventListener('click', (e) => {
    if (!toggle.contains(e.target) && !menu.contains(e.target)) {
      menu.classList.remove('active');
      toggle.setAttribute('aria-expanded', 'false');
    }
  });
}

/**
 * Carousel Banner Đầu Trang (Banner Slider)
 */
function initBannerSlider() {
  const container = document.querySelector('.banner-slider-container');
  if (!container) return;

  const wrapper = container.querySelector('.banner-slides-wrapper');
  const slides = container.querySelectorAll('.banner-slide');
  const prevBtn = container.querySelector('.banner-prev');
  const nextBtn = container.querySelector('.banner-next');
  const dots = container.querySelectorAll('.banner-indicator-dot');

  if (slides.length <= 1) return;

  let currentIndex = 0;
  let autoplayTimer = null;
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function goToSlide(index) {
    if (index < 0) index = slides.length - 1;
    if (index >= slides.length) index = 0;
    currentIndex = index;

    wrapper.style.transform = `translateX(-${currentIndex * 100}%)`;

    dots.forEach((dot, i) => {
      dot.classList.toggle('active', i === currentIndex);
      dot.setAttribute('aria-current', i === currentIndex ? 'true' : 'false');
    });
  }

  function nextSlide() {
    goToSlide(currentIndex + 1);
  }

  function prevSlide() {
    goToSlide(currentIndex - 1);
  }

  if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); resetAutoplay(); });
  if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); resetAutoplay(); });

  dots.forEach((dot, idx) => {
    dot.addEventListener('click', () => {
      goToSlide(idx);
      resetAutoplay();
    });
  });

  // Thao tác bàn phím
  container.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') { prevSlide(); resetAutoplay(); }
    if (e.key === 'ArrowRight') { nextSlide(); resetAutoplay(); }
  });

  function startAutoplay() {
    if (prefersReducedMotion) return;
    stopAutoplay();
    autoplayTimer = setInterval(nextSlide, 5000);
  }

  function stopAutoplay() {
    if (autoplayTimer) clearInterval(autoplayTimer);
  }

  function resetAutoplay() {
    stopAutoplay();
    startAutoplay();
  }

  container.addEventListener('mouseenter', stopAutoplay);
  container.addEventListener('mouseleave', startAutoplay);
  container.addEventListener('focusin', stopAutoplay);
  container.addEventListener('focusout', startAutoplay);

  startAutoplay();
}

/**
 * Thư viện Xem Ảnh Lớn (Photo Lightbox)
 */
function initLightbox() {
  const modal = document.querySelector('#lightboxModal');
  if (!modal) return;

  const modalImg = modal.querySelector('#lightboxImage');
  const modalCap = modal.querySelector('#lightboxCaption');
  const closeBtn = modal.querySelector('.lightbox-close');

  document.querySelectorAll('.gallery-thumb').forEach(thumb => {
    thumb.addEventListener('click', () => {
      const img = thumb.querySelector('img');
      if (img) {
        modalImg.src = img.getAttribute('data-large') || img.src;
        modalCap.textContent = img.alt || '';
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
      }
    });
  });

  function closeModal() {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }

  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('active')) {
      closeModal();
    }
  });
}

/**
 * Nút Sao Chép Liên Kết Chia Sẻ (Share Link Button)
 */
function initShareButtons() {
  document.querySelectorAll('.btn-share-link').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const urlToShare = btn.getAttribute('data-url') || window.location.href;
      try {
        await navigator.clipboard.writeText(urlToShare);
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="icon-check"></i> Đã chép link!';
        btn.classList.add('btn-success');
        setTimeout(() => {
          btn.innerHTML = originalText;
          btn.classList.remove('btn-success');
        }, 2500);
      } catch (err) {
        prompt('Sao chép đường dẫn này:', urlToShare);
      }
    });
  });
}

/**
 * Modal Báo Cáo Thông Tin Sai / Link Hỏng (Report Modal)
 */
function initReportModal() {
  const modal = document.querySelector('#reportModal');
  if (!modal) return;

  const closeBtns = modal.querySelectorAll('.modal-close, .btn-modal-cancel');
  const form = modal.querySelector('#reportForm');

  document.querySelectorAll('.btn-open-report-modal').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const clubId = btn.getAttribute('data-club-id') || '';
      const eventId = btn.getAttribute('data-event-id') || '';
      const entityName = btn.getAttribute('data-entity-name') || '';

      const inputClub = modal.querySelector('input[name="club_id"]');
      const inputEvent = modal.querySelector('input[name="event_id"]');
      const targetLabel = modal.querySelector('#reportTargetName');

      if (inputClub) inputClub.value = clubId;
      if (inputEvent) inputEvent.value = eventId;
      if (targetLabel) targetLabel.textContent = entityName ? `Đối tượng phản ánh: ${entityName}` : '';

      modal.classList.add('active');
    });
  });

  function closeModal() {
    modal.classList.remove('active');
  }

  closeBtns.forEach(b => b.addEventListener('click', closeModal));
  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });
}

/**
 * Hiệu ứng Canvas Bụi Sao, Tinh Cầu & Cánh Bướm Thiên Hà Demension (Dành riêng cho Trang Chủ & Các trang chung)
 */
function initCelestialParticles() {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  // Nếu người dùng đang ở trong trang chi tiết CLB có hiệu ứng riêng, tắt hoàn toàn hiệu ứng sao bướm ngoài trang chủ để không bị lẫn
  if (document.querySelector('.club-personalized-space') || document.getElementById('club-custom-motion-canvas')) {
    return;
  }

  const canvas = document.createElement('canvas');
  canvas.id = 'demension-star-canvas';
  document.body.appendChild(canvas);

  const ctx = canvas.getContext('2d');
  let width = (canvas.width = window.innerWidth);
  let height = (canvas.height = window.innerHeight);

  window.addEventListener('resize', () => {
    width = canvas.width = window.innerWidth;
    height = canvas.height = window.innerHeight;
  });

  // Tương tác chuột: tạo vệt sáng ma thuật sao khi di chuột
  let mouse = { x: -1000, y: -1000, active: false };
  const mouseSparks = [];

  window.addEventListener('mousemove', (e) => {
    mouse.x = e.clientX;
    mouse.y = e.clientY;
    mouse.active = true;

    // Sinh tia sáng ma thuật lấp lánh theo vệt chuột
    if (Math.random() < 0.6) {
      mouseSparks.push({
        x: e.clientX + (Math.random() - 0.5) * 16,
        y: e.clientY + (Math.random() - 0.5) * 16,
        size: Math.random() * 2.5 + 1.2,
        life: 1,
        decay: Math.random() * 0.03 + 0.02,
        color: Math.random() < 0.7 ? '#F6C453' : '#67E8F9',
        vx: (Math.random() - 0.5) * 1.2,
        vy: (Math.random() - 0.5) * 1.2 - 0.3
      });
    }
  });

  // Tạo các ngôi sao bụi vàng và xanh tinh vân
  const stars = [];
  const starCount = Math.min(85, Math.floor(width / 16));
  for (let i = 0; i < starCount; i++) {
    stars.push({
      x: Math.random() * width,
      y: Math.random() * height,
      size: Math.random() * 2.4 + 0.6,
      isSpecial: Math.random() < 0.18, // Sao 8 cánh lấp lánh
      speedX: (Math.random() - 0.5) * 0.4,
      speedY: -Math.random() * 0.5 - 0.15,
      alpha: Math.random() * 0.8 + 0.2,
      alphaSpeed: (Math.random() * 0.02 + 0.008) * (Math.random() < 0.5 ? 1 : -1),
      color: Math.random() < 0.6 ? '#F6C453' : (Math.random() < 0.85 ? '#93C5FD' : '#F472B6')
    });
  }

  // Tạo đàn bướm vàng phát sáng (Golden Celestial Butterflies)
  const butterflies = [];
  const butterflyCount = width < 768 ? 5 : 10;
  for (let i = 0; i < butterflyCount; i++) {
    butterflies.push({
      x: Math.random() * width,
      y: Math.random() * height,
      size: Math.random() * 9 + 10,
      vx: (Math.random() * 1.2 + 0.5) * (Math.random() < 0.5 ? 1 : -1),
      vy: -Math.random() * 0.6 - 0.25,
      wingSpeed: Math.random() * 0.18 + 0.12,
      wingAngle: Math.random() * Math.PI,
      glowPulse: Math.random() * Math.PI
    });
  }

  // Hàm vẽ sao 4 cánh ma thuật Demension
  function drawSparkleStar(cx, cy, spikes, outerRadius, innerRadius, color, alpha) {
    let rot = Math.PI / 2 * 3;
    let x = cx;
    let y = cy;
    let step = Math.PI / spikes;

    ctx.save();
    ctx.globalAlpha = Math.max(0, Math.min(1, alpha));
    ctx.fillStyle = color;
    ctx.shadowColor = color;
    ctx.shadowBlur = 12;

    ctx.beginPath();
    ctx.moveTo(cx, cy - outerRadius);
    for (let i = 0; i < spikes; i++) {
      x = cx + Math.cos(rot) * outerRadius;
      y = cy + Math.sin(rot) * outerRadius;
      ctx.lineTo(x, y);
      rot += step;

      x = cx + Math.cos(rot) * innerRadius;
      y = cy + Math.sin(rot) * innerRadius;
      ctx.lineTo(x, y);
      rot += step;
    }
    ctx.lineTo(cx, cy - outerRadius);
    ctx.closePath();
    ctx.fill();
    ctx.restore();
  }

  function drawButterfly(b) {
    ctx.save();
    ctx.translate(b.x, b.y);
    ctx.rotate(Math.atan2(b.vy, b.vx));

    const wingSpread = Math.sin(b.wingAngle) * 0.85 + 0.15;
    const glow = Math.sin(b.glowPulse) * 5 + 12;

    ctx.fillStyle = 'rgba(246, 196, 83, 0.9)';
    ctx.shadowColor = '#F6C453';
    ctx.shadowBlur = glow;

    // Cánh trái
    ctx.beginPath();
    ctx.ellipse(-b.size * 0.2, -b.size * 0.55 * wingSpread, b.size * 0.55, b.size * 0.4 * Math.abs(wingSpread), 0.35, 0, Math.PI * 2);
    ctx.fill();

    // Cánh phải
    ctx.beginPath();
    ctx.ellipse(-b.size * 0.2, b.size * 0.55 * wingSpread, b.size * 0.55, b.size * 0.4 * Math.abs(wingSpread), -0.35, 0, Math.PI * 2);
    ctx.fill();

    // Cánh nhỏ phụ sau
    ctx.fillStyle = 'rgba(253, 230, 138, 0.85)';
    ctx.beginPath();
    ctx.ellipse(b.size * 0.2, -b.size * 0.35 * wingSpread, b.size * 0.35, b.size * 0.25 * Math.abs(wingSpread), -0.2, 0, Math.PI * 2);
    ctx.fill();
    ctx.beginPath();
    ctx.ellipse(b.size * 0.2, b.size * 0.35 * wingSpread, b.size * 0.35, b.size * 0.25 * Math.abs(wingSpread), 0.2, 0, Math.PI * 2);
    ctx.fill();

    // Thân bướm
    ctx.shadowBlur = 4;
    ctx.fillStyle = '#FFFFFF';
    ctx.beginPath();
    ctx.ellipse(0, 0, b.size * 0.45, b.size * 0.12, 0, 0, Math.PI * 2);
    ctx.fill();

    ctx.restore();
  }

  // Lớp sóng tinh vân & dòng chảy thời không uốn lượn (Vivid Cosmic Waves & Time Current)
  let waveTime = 0;
  function drawNebulaWaves() {
    waveTime += 0.016;
    ctx.save();
    
    // Sóng 1: Dòng sông thời không xanh lam điện quang (Electric Cyan Stream)
    ctx.beginPath();
    for (let x = 0; x <= width + 50; x += 25) {
      const y = height * 0.35 + Math.sin(x * 0.0035 + waveTime) * 60 + Math.cos(x * 0.002 - waveTime * 0.8) * 45;
      if (x === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    }
    ctx.strokeStyle = 'rgba(56, 189, 248, 0.45)';
    ctx.lineWidth = 4.5;
    ctx.shadowColor = '#38BDF8';
    ctx.shadowBlur = 24;
    ctx.stroke();

    // Sóng 2: Dải lụa hoàng kim đa chiều uốn lượn (Celestial Golden Ribbon)
    ctx.beginPath();
    for (let x = 0; x <= width + 50; x += 25) {
      const y = height * 0.60 + Math.cos(x * 0.003 - waveTime * 1.2) * 75 + Math.sin(x * 0.0018 + waveTime) * 40;
      if (x === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    }
    ctx.strokeStyle = 'rgba(246, 196, 83, 0.6)';
    ctx.lineWidth = 5;
    ctx.shadowColor = '#F6C453';
    ctx.shadowBlur = 30;
    ctx.stroke();

    // Sóng 3: Dòng chảy tím tinh vân ma thuật (Violet Nebula Drift)
    ctx.beginPath();
    for (let x = 0; x <= width + 50; x += 30) {
      const y = height * 0.78 + Math.sin(x * 0.0025 + waveTime * 0.9) * 55 + Math.cos(x * 0.0015 - waveTime * 0.6) * 35;
      if (x === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    }
    ctx.strokeStyle = 'rgba(192, 132, 252, 0.4)';
    ctx.lineWidth = 3.5;
    ctx.shadowColor = '#C084FC';
    ctx.shadowBlur = 20;
    ctx.stroke();

    ctx.restore();
  }

  function animate() {
    ctx.clearRect(0, 0, width, height);

    // Vẽ hiệu ứng sóng tinh vân uốn lượn
    drawNebulaWaves();

    // Vẽ sao bụi
    for (let s of stars) {
      s.x += s.speedX;
      s.y += s.speedY;
      s.alpha += s.alphaSpeed;
      if (s.alpha > 0.95 || s.alpha < 0.2) s.alphaSpeed = -s.alphaSpeed;

      if (s.y < -10) { s.y = height + 10; s.x = Math.random() * width; }
      if (s.x < -10) s.x = width + 10;
      if (s.x > width + 10) s.x = -10;

      if (s.isSpecial) {
        drawSparkleStar(s.x, s.y, 4, s.size * 3.5, s.size * 0.8, s.color, s.alpha);
      } else {
        ctx.save();
        ctx.globalAlpha = Math.max(0.1, Math.min(1, s.alpha));
        ctx.fillStyle = s.color;
        ctx.shadowColor = s.color;
        ctx.shadowBlur = s.size > 1.8 ? 10 : 5;
        ctx.beginPath();
        ctx.arc(s.x, s.y, s.size, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
      }
    }

    // Vẽ hạt vệt chuột (Cursor trail sparks)
    for (let i = mouseSparks.length - 1; i >= 0; i--) {
      const p = mouseSparks[i];
      p.x += p.vx;
      p.y += p.vy;
      p.life -= p.decay;

      if (p.life <= 0) {
        mouseSparks.splice(i, 1);
        continue;
      }

      ctx.save();
      ctx.globalAlpha = p.life;
      ctx.fillStyle = p.color;
      ctx.shadowColor = p.color;
      ctx.shadowBlur = 8;
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.size * p.life, 0, Math.PI * 2);
      ctx.fill();
      ctx.restore();
    }

    // Vẽ bướm vàng thiên hà
    for (let b of butterflies) {
      b.x += b.vx;
      b.y += b.vy;
      b.wingAngle += b.wingSpeed;
      b.glowPulse += 0.05;

      // Lượn sóng tự nhiên
      b.vy += (Math.random() - 0.5) * 0.06;
      b.vy = Math.max(-1.4, Math.min(0.3, b.vy));

      if (b.y < -30) { b.y = height + 30; b.x = Math.random() * width; }
      if (b.x < -40) b.x = width + 30;
      if (b.x > width + 40) b.x = -30;

      drawButterfly(b);
    }

    requestAnimationFrame(animate);
  }

  animate();
}

/**
 * Hiệu ứng Canvas Động Độc Quyền Dành Cho Từng CLB
 * (Lửa Bùng Lên / Ma Trận Công Nghệ / Sấm Sét / Cánh Hoa Rơi / Mưa Bụi Vàng / Bão Tuyết)
 */
function initClubSpecificMotionEffects() {
  const canvas = document.getElementById('club-custom-motion-canvas');
  const space = document.querySelector('.club-personalized-space');
  if (!canvas || !space) return;

  const motionType = space.getAttribute('data-club-motion') || 'nebula-wave';
  const ctx = canvas.getContext('2d');
  let width = (canvas.width = window.innerWidth);
  let height = (canvas.height = window.innerHeight);

  window.addEventListener('resize', () => {
    width = canvas.width = window.innerWidth;
    height = canvas.height = window.innerHeight;
  });

  // 1. HIỆU ỨNG LỬA BÙNG LÊN & TÀN TRO RỰC ĐỎ (Fire Burst & Embers)
  if (motionType === 'fire-burst') {
    const particles = [];
    for (let i = 0; i < 75; i++) {
      particles.push(createFireParticle(true));
    }

    function createFireParticle(randomY = false) {
      return {
        x: Math.random() * width,
        y: randomY ? Math.random() * height : height + 10,
        size: Math.random() * 8 + 4,
        speedY: -(Math.random() * 3.5 + 1.8),
        speedX: (Math.random() - 0.5) * 1.5,
        life: 1,
        decay: Math.random() * 0.015 + 0.008,
        color: Math.random() < 0.4 ? '#EF4444' : (Math.random() < 0.75 ? '#F97316' : '#FBBF24')
      };
    }

    function loopFire() {
      ctx.clearRect(0, 0, width, height);
      if (particles.length < 85) particles.push(createFireParticle(false));

      for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i];
        p.x += p.speedX;
        p.y += p.speedY;
        p.life -= p.decay;
        p.size *= 0.985;

        if (p.life <= 0 || p.size <= 0.5) {
          particles.splice(i, 1);
          continue;
        }

        ctx.save();
        ctx.globalAlpha = p.life;
        ctx.fillStyle = p.color;
        ctx.shadowColor = p.color;
        ctx.shadowBlur = 18;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
      }
      requestAnimationFrame(loopFire);
    }
    loopFire();
  }

  // 2. HIỆU ỨNG MA TRẬN CÔNG NGHỆ SỐ & MẠCH ĐIỆN TỬ (Cyber Tech Grid & Data Rain)
  else if (motionType === 'cyber-matrix') {
    const chars = '010101<>/{};:AI*#+~_TECHCODE';
    const fontSize = 15;
    const columns = Math.floor(width / fontSize);
    const drops = Array(columns).fill(1);

    function loopMatrix() {
      ctx.fillStyle = 'rgba(4, 9, 30, 0.12)';
      ctx.fillRect(0, 0, width, height);

      ctx.fillStyle = '#00F0FF';
      ctx.shadowColor = '#00F0FF';
      ctx.shadowBlur = 8;
      ctx.font = fontSize + 'px monospace';

      for (let i = 0; i < drops.length; i++) {
        const char = chars.charAt(Math.floor(Math.random() * chars.length));
        ctx.fillText(char, i * fontSize, drops[i] * fontSize);

        if (drops[i] * fontSize > height && Math.random() > 0.975) {
          drops[i] = 0;
        }
        drops[i]++;
      }
      requestAnimationFrame(loopMatrix);
    }
    loopMatrix();
  }

  // 3. HIỆU ỨNG SẤM SÉT TINH VÂN & LÔI ĐIỆN (Cosmic Lightning Storm)
  else if (motionType === 'thunder-storm') {
    let bolts = [];
    let nextStrike = 20;

    function createBolt() {
      let x = Math.random() * width;
      let y = 0;
      const segments = [{ x, y }];
      while (y < height) {
        x += (Math.random() - 0.5) * 35;
        y += Math.random() * 25 + 10;
        segments.push({ x, y });
      }
      return { segments, life: 1, color: Math.random() < 0.6 ? '#60A5FA' : '#C084FC' };
    }

    function loopThunder() {
      ctx.clearRect(0, 0, width, height);
      nextStrike--;
      if (nextStrike <= 0) {
        bolts.push(createBolt());
        nextStrike = Math.floor(Math.random() * 90) + 40;
      }

      for (let i = bolts.length - 1; i >= 0; i--) {
        const b = bolts[i];
        b.life -= 0.08;
        if (b.life <= 0) {
          bolts.splice(i, 1);
          continue;
        }

        ctx.save();
        ctx.globalAlpha = b.life;
        ctx.strokeStyle = b.color;
        ctx.shadowColor = b.color;
        ctx.shadowBlur = 25;
        ctx.lineWidth = 3;
        ctx.beginPath();
        for (let j = 0; j < b.segments.length; j++) {
          const pt = b.segments[j];
          if (j === 0) ctx.moveTo(pt.x, pt.y);
          else ctx.lineTo(pt.x, pt.y);
        }
        ctx.stroke();
        ctx.restore();
      }
      requestAnimationFrame(loopThunder);
    }
    loopThunder();
  }

  // 4. HIỆU ỨNG CÁNH HOA THỜI KHÔNG RƠI & GIÓ CUỐN (Cherry Blossom Sakura)
  else if (motionType === 'sakura-drift') {
    const petals = [];
    for (let i = 0; i < 45; i++) {
      petals.push({
        x: Math.random() * width,
        y: Math.random() * height,
        size: Math.random() * 7 + 8,
        speedX: Math.random() * 1.5 + 0.8,
        speedY: Math.random() * 1.8 + 1,
        angle: Math.random() * Math.PI * 2,
        rotSpeed: Math.random() * 0.03 + 0.01
      });
    }

    function loopSakura() {
      ctx.clearRect(0, 0, width, height);
      for (let p of petals) {
        p.x += p.speedX;
        p.y += p.speedY;
        p.angle += p.rotSpeed;

        if (p.y > height + 20) { p.y = -20; p.x = Math.random() * width; }
        if (p.x > width + 20) { p.x = -20; }

        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate(p.angle);
        ctx.fillStyle = 'rgba(244, 114, 182, 0.75)';
        ctx.shadowColor = '#F472B6';
        ctx.shadowBlur = 8;
        ctx.beginPath();
        ctx.ellipse(0, 0, p.size, p.size * 0.55, 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
      }
      requestAnimationFrame(loopSakura);
    }
    loopSakura();
  }

  // 5. HIỆU ỨNG BÃO TUYẾT BĂNG TINH BẮC CỰC (Glacial Frost Vortex)
  else if (motionType === 'frost-vortex') {
    const flakes = [];
    for (let i = 0; i < 70; i++) {
      flakes.push({
        x: Math.random() * width,
        y: Math.random() * height,
        size: Math.random() * 3 + 1.5,
        speedY: Math.random() * 2.2 + 1,
        speedX: (Math.random() - 0.5) * 1.2
      });
    }

    function loopFrost() {
      ctx.clearRect(0, 0, width, height);
      for (let f of flakes) {
        f.y += f.speedY;
        f.x += f.speedX;
        if (f.y > height) f.y = 0;
        if (f.x > width) f.x = 0;
        if (f.x < 0) f.x = width;

        ctx.save();
        ctx.fillStyle = 'rgba(224, 242, 254, 0.85)';
        ctx.shadowColor = '#BAE6FD';
        ctx.shadowBlur = 10;
        ctx.beginPath();
        ctx.arc(f.x, f.y, f.size, 0, Math.PI * 2);
        ctx.fill();
        ctx.restore();
      }
      requestAnimationFrame(loopFrost);
    }
    loopFrost();
  }

  // 6. HIỆU ỨNG MƯA BỤI VÀNG HOÀNG KIM (Golden Stardust Rain)
  else if (motionType === 'golden-rain') {
    const drops = [];
    for (let i = 0; i < 65; i++) {
      drops.push({
        x: Math.random() * width,
        y: Math.random() * height,
        length: Math.random() * 16 + 8,
        speedY: Math.random() * 4 + 3,
        alpha: Math.random() * 0.7 + 0.3
      });
    }

    function loopGoldRain() {
      ctx.clearRect(0, 0, width, height);
      for (let d of drops) {
        d.y += d.speedY;
        if (d.y > height) { d.y = -20; d.x = Math.random() * width; }

        ctx.save();
        ctx.globalAlpha = d.alpha;
        ctx.strokeStyle = '#F6C453';
        ctx.shadowColor = '#F6C453';
        ctx.shadowBlur = 12;
        ctx.lineWidth = 1.8;
        ctx.beginPath();
        ctx.moveTo(d.x, d.y);
        ctx.lineTo(d.x, d.y + d.length);
        ctx.stroke();
        ctx.restore();
      }
      requestAnimationFrame(loopGoldRain);
    }
    loopGoldRain();
  }
}
