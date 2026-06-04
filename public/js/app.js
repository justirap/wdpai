(function () {
    const toggle = document.getElementById('nav-user-toggle');
    const menu = document.getElementById('nav-dropdown-menu');
    const overlay = document.getElementById('contact-modal-overlay');
    const openContact = document.getElementById('open-contact-modal');
    const closeContact = document.getElementById('close-contact-modal');
    const contactForm = document.getElementById('contact-form');
    const feedback = document.getElementById('contact-modal-feedback');

    if (toggle && menu) {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = !menu.hidden;
            menu.hidden = isOpen;
            toggle.setAttribute('aria-expanded', String(!isOpen));
        });

        document.addEventListener('click', () => {
            menu.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        });

        menu.addEventListener('click', (e) => e.stopPropagation());
    }

    function openModal() {
        if (!overlay) return;
        overlay.hidden = false;
        document.body.classList.add('modal-open');
        if (menu) {
            menu.hidden = true;
        }
    }

    function closeModal() {
        if (!overlay) return;
        overlay.hidden = true;
        document.body.classList.remove('modal-open');
        if (feedback) {
            feedback.hidden = true;
            feedback.textContent = '';
            feedback.className = 'contact-modal-feedback';
        }
    }

    if (openContact) {
        openContact.addEventListener('click', openModal);
    }

    if (closeContact) {
        closeContact.addEventListener('click', closeModal);
    }

    if (overlay) {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeModal();
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal();
            if (menu) {
                menu.hidden = true;
            }
        }
    });

    if (contactForm) {
        contactForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('contact-submit-btn');
            if (btn) btn.disabled = true;

            const formData = new FormData(contactForm);

            try {
                const response = await fetch('/contact', {
                    method: 'POST',
                    body: formData,
                });
                const data = await response.json();

                if (!feedback) return;

                feedback.hidden = false;
                if (data.success) {
                    feedback.className = 'contact-modal-feedback success';
                    feedback.textContent = data.message || 'Message sent successfully.';
                    contactForm.reset();
                    const nameInput = document.getElementById('contact-name');
                    const emailInput = document.getElementById('contact-email');
                    if (nameInput) nameInput.value = contactForm.dataset.presetName || '';
                    if (emailInput) emailInput.value = contactForm.dataset.presetEmail || '';
                    document.getElementById('contact-message').value = '';
                } else {
                    feedback.className = 'contact-modal-feedback error';
                    feedback.textContent = data.error || 'Could not send message. Please try again.';
                }
            } catch (err) {
                if (feedback) {
                    feedback.hidden = false;
                    feedback.className = 'contact-modal-feedback error';
                    feedback.textContent = 'Network error. Please try again.';
                }
            } finally {
                if (btn) btn.disabled = false;
            }
        });
    }
})();
