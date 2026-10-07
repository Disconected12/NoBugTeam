/**
 * ClubHub - Script quản trị hệ thống (Admin interactions)
 */

document.addEventListener('DOMContentLoaded', () => {
  initImagePreviews();
  initConfirmDeletes();
  initMobileSidebar();
});

function initImagePreviews() {
  document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
    input.addEventListener('change', () => {
      const targetSelector = input.getAttribute('data-preview');
      const targetImg = document.querySelector(targetSelector);
      if (!targetImg || !input.files || !input.files[0]) return;

      const file = input.files[0];
      const reader = new FileReader();
      reader.onload = (e) => {
        targetImg.src = e.target.result;
        targetImg.style.display = 'block';
      };
      reader.readAsDataURL(file);
    });
  });
}

function initConfirmDeletes() {
  document.querySelectorAll('.btn-confirm-delete').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const msg = btn.getAttribute('data-confirm') || 'Bạn có chắc chắn muốn thực hiện thao tác xóa này không? Hành động này không thể hoàn tác.';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });
}

function initMobileSidebar() {
  const toggle = document.querySelector('.admin-mobile-toggle');
  const sidebar = document.querySelector('.admin-sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });
  }
}
