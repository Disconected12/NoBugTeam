/**
 * ClubHub - Script tương tác giao diện chính
 */

document.addEventListener('DOMContentLoaded', () => {
  initMobileMenu();
  initBannerSlider();
  initLightbox();
  initShareButtons();
  initReportModal();
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
