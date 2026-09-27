/**
 * ClubHub - Bộ điều khiển Quiz Trắc Nghiệm Khám Phá CLB
 * Hỗ trợ chuyển bước (multi-step), giữ đáp án khi quay lại, kiểm tra câu bắt buộc
 */

document.addEventListener('DOMContentLoaded', () => {
  initQuizEngine();
});

function initQuizEngine() {
  const wizard = document.querySelector('#quizWizard');
  if (!wizard) return;

  const steps = wizard.querySelectorAll('.quiz-question-box');
  const totalSteps = steps.length;
  if (totalSteps === 0) return;

  let currentStep = 0;
  const userAnswers = {}; // Lưu trữ { questionId: [selectedOptionIds] }

  const progressBar = wizard.querySelector('#quizProgressBar');
  const progressText = wizard.querySelector('#quizProgressText');
  const prevBtn = wizard.querySelector('#quizPrevBtn');
  const nextBtn = wizard.querySelector('#quizNextBtn');
  const restartBtn = wizard.querySelector('#quizRestartBtn');
  const form = wizard.querySelector('#quizForm');
  const alertBox = wizard.querySelector('#quizAlertBox');

  // Khởi tạo các sự kiện chọn đáp án (Tile Click)
  steps.forEach(step => {
    const qId = step.getAttribute('data-question-id');
    const qType = step.getAttribute('data-question-type'); // 'single' hoặc 'multiple'
    const tiles = step.querySelectorAll('.quiz-option-tile');

    tiles.forEach(tile => {
      tile.addEventListener('click', () => {
        const optId = tile.getAttribute('data-option-id');

        if (qType === 'single') {
          // Xóa chọn các tile khác trong cùng câu
          tiles.forEach(t => t.classList.remove('selected'));
          tile.classList.add('selected');
          userAnswers[qId] = [optId];
        } else {
          // Cho phép chọn nhiều (multiple)
          tile.classList.toggle('selected');
          if (!userAnswers[qId]) userAnswers[qId] = [];
          if (tile.classList.contains('selected')) {
            if (!userAnswers[qId].includes(optId)) userAnswers[qId].push(optId);
          } else {
            userAnswers[qId] = userAnswers[qId].filter(id => id !== optId);
          }
        }

        hideAlert();
      });
    });
  });

  function showStep(index) {
    if (index < 0 || index >= totalSteps) return;
    currentStep = index;

    steps.forEach((step, idx) => {
      step.classList.toggle('active', idx === currentStep);
    });

    // Cập nhật thanh tiến độ
    const percent = Math.round(((currentStep + 1) / totalSteps) * 100);
    if (progressBar) progressBar.style.width = `${percent}%`;
    if (progressText) progressText.textContent = `Câu hỏi ${currentStep + 1} / ${totalSteps}`;

    // Nút Quay lại
    if (prevBtn) {
      prevBtn.style.display = currentStep === 0 ? 'none' : 'inline-flex';
    }

    // Nút Tiếp theo / Hoàn thành
    if (nextBtn) {
      if (currentStep === totalSteps - 1) {
        nextBtn.innerHTML = 'Xem kết quả gợi ý <i class="icon-arrow-right"></i>';
        nextBtn.classList.add('btn-primary');
      } else {
        nextBtn.innerHTML = 'Tiếp theo <i class="icon-arrow-right"></i>';
      }
    }

    hideAlert();
    window.scrollTo({ top: wizard.offsetTop - 80, behavior: 'smooth' });
  }

  function validateCurrentStep() {
    const activeStep = steps[currentStep];
    const qId = activeStep.getAttribute('data-question-id');
    const selected = userAnswers[qId] || [];

    if (selected.length === 0) {
      showAlert('Vui lòng chọn ít nhất một đáp án để tiếp tục.');
      return false;
    }
    return true;
  }

  function showAlert(message) {
    if (alertBox) {
      alertBox.textContent = message;
      alertBox.style.display = 'block';
    }
  }

  function hideAlert() {
    if (alertBox) {
      alertBox.style.display = 'none';
    }
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', (e) => {
      e.preventDefault();
      if (!validateCurrentStep()) return;

      if (currentStep < totalSteps - 1) {
        showStep(currentStep + 1);
      } else {
        // Hoàn thành quiz -> Chuyển hướng hoặc submit form lên backend
        submitQuiz();
      }
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', (e) => {
      e.preventDefault();
      if (currentStep > 0) {
        showStep(currentStep - 1);
      }
    });
  }

  if (restartBtn) {
    restartBtn.addEventListener('click', (e) => {
      e.preventDefault();
      if (confirm('Bạn có chắc muốn làm lại bài trắc nghiệm từ đầu không?')) {
        for (const key in userAnswers) {
          delete userAnswers[key];
        }
        wizard.querySelectorAll('.quiz-option-tile').forEach(t => t.classList.remove('selected'));
        showStep(0);
      }
    });
  }

  function submitQuiz() {
    // Đổ dữ liệu đáp án vào input ẩn của form rồi submit
    const inputHidden = form.querySelector('input[name="answers_payload"]');
    if (inputHidden) {
      inputHidden.value = JSON.stringify(userAnswers);
      nextBtn.disabled = true;
      nextBtn.innerHTML = 'Đang chấm điểm & đối chiếu...';
      form.submit();
    }
  }

  // Khởi chạy câu hỏi đầu tiên
  showStep(0);
}
