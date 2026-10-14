/**
 * SureSalez Verified Reviews & Community Q&A Client Hydrator
 */

document.addEventListener('DOMContentLoaded', () => {
    let reviewsData = null;

    if (typeof SureSalezReviewsQAData !== 'undefined') {
        reviewsData = SureSalezReviewsQAData;
    } else {
        const el = document.getElementById('suresalez-reviews-data-json');
        if (el) {
            try {
                reviewsData = JSON.parse(el.textContent);
            } catch (e) {
                console.error('Error parsing SureSalez Reviews JSON:', e);
            }
        }
    }

    if (!reviewsData) return;

    // 1. Mount Buy Box Rating Badge
    const badgeMount = document.getElementById('suresalez-reviews-badge-mount');
    if (badgeMount && reviewsData.aggregate) {
        renderRatingBadge(badgeMount, reviewsData.aggregate);
    }

    // 2. Mount Full Reviews & Q&A Section
    const sectionMount = document.getElementById('suresalez-reviews-qa-section');
    if (sectionMount) {
        renderReviewsQASection(sectionMount, reviewsData);
    }
});

function renderRatingBadge(container, aggregate) {
    const avg = aggregate.average || 5.0;
    const count = aggregate.count || 0;
    const fullStars = Math.round(avg);
    const starStr = '★'.repeat(fullStars) + '☆'.repeat(5 - fullStars);

    container.innerHTML = `
        <div class="suresalez-rating-badge" title="${avg} out of 5 stars based on ${count} reviews">
            <span class="suresalez-stars">${starStr}</span>
            <span class="suresalez-rating-score">${avg}</span>
            <span class="suresalez-rating-count">(${count} verified ${count === 1 ? 'review' : 'reviews'})</span>
        </div>
    `;

    container.querySelector('.suresalez-rating-badge').addEventListener('click', () => {
        const sec = document.getElementById('suresalez-reviews-qa-section');
        if (sec) {
            sec.scrollIntoView({ behavior: 'smooth' });
        }
    });
}

function renderReviewsQASection(container, data) {
    container.innerHTML = `
        <div class="suresalez-qa-tabs">
            <button type="button" class="suresalez-tab-btn is-active" data-tab="reviews">
                Customer Reviews (${(data.reviews || []).length})
            </button>
            <button type="button" class="suresalez-tab-btn" data-tab="qa">
                Questions & Answers (${(data.questions || []).length})
            </button>
        </div>

        <div class="suresalez-tab-content" id="suresalez-tab-reviews-content">
            <div class="suresalez-section-bar">
                <div>
                    <h3 style="margin:0 0 4px 0;">Customer Feedback</h3>
                    <p style="margin:0; font-size:13px; color:#64748b;">Verified buyer ratings & real-world durability evaluations</p>
                </div>
                <button type="button" class="suresalez-btn-action" id="suresalez-open-review-modal">Write a Review</button>
            </div>
            <div class="suresalez-reviews-list" id="suresalez-reviews-list-container">
                ${renderReviewsHtml(data.reviews || [])}
            </div>
        </div>

        <div class="suresalez-tab-content" id="suresalez-tab-qa-content" style="display:none;">
            <div class="suresalez-section-bar">
                <div>
                    <h3 style="margin:0 0 4px 0;">Product Q&A</h3>
                    <p style="margin:0; font-size:13px; color:#64748b;">Direct answers from verified merchants & technical engineers</p>
                </div>
                <button type="button" class="suresalez-btn-action" id="suresalez-open-question-modal">Ask a Question</button>
            </div>
            <div class="suresalez-questions-list" id="suresalez-questions-list-container">
                ${renderQuestionsHtml(data.questions || [])}
            </div>
        </div>
    `;

    // Tab Switcher
    const tabBtns = container.querySelectorAll('.suresalez-tab-btn');
    const reviewsTab = container.querySelector('#suresalez-tab-reviews-content');
    const qaTab = container.querySelector('#suresalez-tab-qa-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');

            const tab = btn.getAttribute('data-tab');
            if (tab === 'reviews') {
                reviewsTab.style.display = 'block';
                qaTab.style.display = 'none';
            } else {
                reviewsTab.style.display = 'none';
                qaTab.style.display = 'block';
            }
        });
    });

    // Helpful voting handler
    container.addEventListener('click', async (e) => {
        const btn = e.target.closest('.suresalez-helpful-btn');
        if (!btn) return;
        const reviewId = btn.getAttribute('data-review-id');
        if (!reviewId || btn.disabled) return;

        btn.disabled = true;
        const currentCount = parseInt(btn.getAttribute('data-count') || '0', 10);
        btn.textContent = `✓ Helpful (${currentCount + 1})`;

        try {
            if (typeof SureSalezReviewsQAData !== 'undefined' && SureSalezReviewsQAData.voteEndpoint) {
                await fetch(SureSalezReviewsQAData.voteEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ review_id: reviewId }),
                });
            }
        } catch (err) {
            console.error('Vote review error:', err);
        }
    });

    // Modals
    container.querySelector('#suresalez-open-review-modal').addEventListener('click', () => {
        openReviewModal(data);
    });
    container.querySelector('#suresalez-open-question-modal').addEventListener('click', () => {
        openQuestionModal(data);
    });
}

function renderReviewsHtml(reviews) {
    if (!reviews.length) {
        return '<p style="color:#64748b; font-size:13px;">No reviews yet. Be the first to review this product!</p>';
    }

    return reviews.map(rev => {
        const fullStars = parseInt(rev.rating, 10) || 5;
        const starStr = '★'.repeat(fullStars) + '☆'.repeat(5 - fullStars);
        return `
            <div class="suresalez-review-card" data-review-id="${rev.id}">
                <div class="suresalez-review-header">
                    <div>
                        <div class="suresalez-author-line">
                            <span class="suresalez-author-name">${escapeHtml(rev.author_name)}</span>
                            ${rev.is_verified_buyer ? '<span class="suresalez-verified-badge">✓ Verified Buyer</span>' : ''}
                        </div>
                        <div class="suresalez-stars" style="margin-top:4px;">${starStr}</div>
                    </div>
                    <span class="suresalez-review-date">${escapeHtml((rev.created_at || '').substring(0, 10))}</span>
                </div>
                <div class="suresalez-review-title">${escapeHtml(rev.title)}</div>
                <div class="suresalez-review-content">${escapeHtml(rev.content)}</div>
                ${(rev.pros || rev.cons) ? `
                    <div class="suresalez-pros-cons">
                        ${rev.pros ? `<div class="suresalez-pros"><strong>+ Pros:</strong> ${escapeHtml(rev.pros)}</div>` : ''}
                        ${rev.cons ? `<div class="suresalez-cons"><strong>- Cons:</strong> ${escapeHtml(rev.cons)}</div>` : ''}
                    </div>
                ` : ''}
                <button type="button" class="suresalez-helpful-btn" data-review-id="${rev.id}" data-count="${rev.helpful_count || 0}">
                    👍 Helpful (${rev.helpful_count || 0})
                </button>
            </div>
        `;
    }).join('');
}

function renderQuestionsHtml(questions) {
    if (!questions.length) {
        return '<p style="color:#64748b; font-size:13px;">Have a question? Ask our engineers and community!</p>';
    }

    return questions.map(q => {
        const answersHtml = (q.answers || []).map(a => `
            <div class="suresalez-answer-item">
                <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                    <strong>${escapeHtml(a.author_name)}</strong>
                    ${a.is_merchant ? '<span class="suresalez-merchant-pill">Merchant Verified</span>' : ''}
                </div>
                <p style="margin:0;">${escapeHtml(a.answer)}</p>
            </div>
        `).join('');

        return `
            <div class="suresalez-question-card">
                <div class="suresalez-question-title">
                    <span style="color:#2563eb; font-weight:800;">Q:</span>
                    <span>${escapeHtml(q.question)}</span>
                </div>
                <div class="suresalez-answers-list">
                    ${answersHtml || '<p style="color:#94a3b8; font-size:12px; margin:0;">Awaiting merchant response...</p>'}
                </div>
            </div>
        `;
    }).join('');
}

function openReviewModal(data) {
    const overlay = document.createElement('div');
    overlay.className = 'suresalez-modal-overlay';
    overlay.innerHTML = `
        <div class="suresalez-modal-card">
            <h3>Write a Verified Customer Review</h3>
            <form id="suresalez-submit-review-form">
                <div class="suresalez-form-row">
                    <label>Rating:</label>
                    <select name="rating" required>
                        <option value="5">★★★★★ 5 Stars - Outstanding</option>
                        <option value="4">★★★★☆ 4 Stars - Very Good</option>
                        <option value="3">★★★☆☆ 3 Stars - Average</option>
                        <option value="2">★★☆☆☆ 2 Stars - Subpar</option>
                        <option value="1">★☆☆☆☆ 1 Star - Poor</option>
                    </select>
                </div>
                <div class="suresalez-form-row">
                    <label>Your Name:</label>
                    <input type="text" name="author_name" required placeholder="e.g. John D.">
                </div>
                <div class="suresalez-form-row">
                    <label>Your Email:</label>
                    <input type="email" name="author_email" required placeholder="john@example.com">
                </div>
                <div class="suresalez-form-row">
                    <label>Review Headline:</label>
                    <input type="text" name="title" required placeholder="Summary of your experience">
                </div>
                <div class="suresalez-form-row">
                    <label>Review Details:</label>
                    <textarea name="content" rows="3" required placeholder="How does the product perform?"></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                    <button type="button" class="suresalez-modal-close" style="background:#e2e8f0; border:none; padding:8px 16px; border-radius:6px; cursor:pointer;">Cancel</button>
                    <button type="submit" class="suresalez-btn-action">Submit Review</button>
                </div>
            </form>
        </div>
    `;

    document.body.appendChild(overlay);

    overlay.querySelector('.suresalez-modal-close').addEventListener('click', () => overlay.remove());
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.remove();
    });

    overlay.querySelector('form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        try {
            if (typeof SureSalezReviewsQAData !== 'undefined' && SureSalezReviewsQAData.reviewsEndpoint) {
                const res = await fetch(SureSalezReviewsQAData.reviewsEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const result = await res.json();
                if (result.success) {
                    alert('Thank you! Your review has been submitted.');
                    overlay.remove();
                    location.reload();
                    return;
                }
            }
            alert('Review received!');
            overlay.remove();
        } catch (err) {
            console.error('Submit review error:', err);
            overlay.remove();
        }
    });
}

function openQuestionModal(data) {
    const overlay = document.createElement('div');
    overlay.className = 'suresalez-modal-overlay';
    overlay.innerHTML = `
        <div class="suresalez-modal-card">
            <h3>Ask a Technical or Product Question</h3>
            <form id="suresalez-submit-question-form">
                <div class="suresalez-form-row">
                    <label>Your Name:</label>
                    <input type="text" name="author_name" required placeholder="e.g. Sarah M.">
                </div>
                <div class="suresalez-form-row">
                    <label>Your Email:</label>
                    <input type="email" name="author_email" required placeholder="sarah@example.com">
                </div>
                <div class="suresalez-form-row">
                    <label>Your Question:</label>
                    <textarea name="question" rows="4" required placeholder="What would you like to know about specifications, warranty, or delivery?"></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                    <button type="button" class="suresalez-modal-close" style="background:#e2e8f0; border:none; padding:8px 16px; border-radius:6px; cursor:pointer;">Cancel</button>
                    <button type="submit" class="suresalez-btn-action">Post Question</button>
                </div>
            </form>
        </div>
    `;

    document.body.appendChild(overlay);

    overlay.querySelector('.suresalez-modal-close').addEventListener('click', () => overlay.remove());
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.remove();
    });

    overlay.querySelector('form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        try {
            if (typeof SureSalezReviewsQAData !== 'undefined' && SureSalezReviewsQAData.questionsEndpoint) {
                const res = await fetch(SureSalezReviewsQAData.questionsEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const result = await res.json();
                if (result.success) {
                    alert('Your question has been posted!');
                    overlay.remove();
                    location.reload();
                    return;
                }
            }
            alert('Question received!');
            overlay.remove();
        } catch (err) {
            console.error('Submit question error:', err);
            overlay.remove();
        }
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
