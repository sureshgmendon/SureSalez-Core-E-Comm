// SureSalez Documentation Interactive JS
document.addEventListener('DOMContentLoaded', () => {
  // 1. Copy-to-clipboard for code blocks
  document.querySelectorAll('.code-block').forEach((block) => {
    const copyBtn = block.querySelector('.copy-btn');
    const codeEl = block.querySelector('pre code') || block.querySelector('pre');
    if (copyBtn && codeEl) {
      copyBtn.addEventListener('click', async () => {
        try {
          const text = codeEl.innerText.trim();
          await navigator.clipboard.writeText(text);
          const originalText = copyBtn.innerText;
          copyBtn.innerText = 'Copied!';
          copyBtn.style.background = '#10b981';
          copyBtn.style.color = '#ffffff';
          setTimeout(() => {
            copyBtn.innerText = originalText;
            copyBtn.style.background = '';
            copyBtn.style.color = '';
          }, 2000);
        } catch (err) {
          console.error('Clipboard copy failed:', err);
        }
      });
    }
  });

  // 2. Sidebar Navigation Active Spy
  const navLinks = document.querySelectorAll('.nav-item a');
  const sections = document.querySelectorAll('.doc-section');

  const observerOptions = {
    root: null,
    rootMargin: '-80px 0px -60% 0px',
    threshold: 0
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const id = entry.target.getAttribute('id');
        navLinks.forEach((link) => {
          const href = link.getAttribute('href');
          const parentItem = link.parentElement;
          if (href === `#${id}`) {
            parentItem.classList.add('active');
          } else {
            parentItem.classList.remove('active');
          }
        });
      }
    });
  }, observerOptions);

  sections.forEach((sec) => observer.observe(sec));

  // 3. Search Filter in Sidebar
  const searchInput = document.getElementById('docs-search');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const q = e.target.value.toLowerCase().trim();
      navLinks.forEach((link) => {
        const text = link.innerText.toLowerCase();
        const parentItem = link.parentElement;
        if (!q || text.includes(q)) {
          parentItem.style.display = 'block';
        } else {
          parentItem.style.display = 'none';
        }
      });
    });
  }

  // 4. Smooth Anchor Scrolling
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId && targetId !== '#') {
        const targetEl = document.querySelector(targetId);
        if (targetEl) {
          e.preventDefault();
          targetEl.scrollIntoView({
            behavior: 'smooth'
          });
          history.pushState(null, null, targetId);
        }
      }
    });
  });
});
