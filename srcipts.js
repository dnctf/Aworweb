document.addEventListener('DOMContentLoaded', function() {
    // Hero Slider
    const heroSlides = document.querySelectorAll('#hero-slider .slide');
    if (heroSlides.length > 1) {
        let current = 0;
        setInterval(() => {
            heroSlides[current].classList.remove('active');
            current = (current + 1) % heroSlides.length;
            heroSlides[current].classList.add('active');
        }, 5000);
    }

    // Outlet Slider + Filter
    const outletSection = document.getElementById('outlets');
    if (outletSection) {
        const slider = outletSection.querySelector('.outlet-grid-slider');
        const cards = Array.from(slider.querySelectorAll('.outlet-card'));
        const nextBtn = outletSection.querySelector('#next-outlet');
        const prevBtn = outletSection.querySelector('#prev-outlet');
        const filters = outletSection.querySelector('.filter-buttons');

        let currentIndex = 0;
        let cardWidth = 320 + 30; // Lebar kartu + gap

        const updateSlider = () => {
            const visibleCards = cards.filter(c => c.style.display !== 'none');
            if(visibleCards.length === 0) {
                if (prevBtn) prevBtn.style.display = 'none';
                if (nextBtn) nextBtn.style.display = 'none';
                return;
            };

            const totalWidth = visibleCards.length * cardWidth - 30;
            const wrapperWidth = slider.parentElement.offsetWidth;

            slider.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
            
            const showButtons = totalWidth > wrapperWidth;
            if (nextBtn) nextBtn.style.display = prevBtn.style.display = showButtons ? 'flex' : 'none';
            
            if (prevBtn) prevBtn.disabled = currentIndex === 0;
            const lastVisiblePosition = currentIndex * cardWidth + wrapperWidth;
            if (nextBtn) nextBtn.disabled = lastVisiblePosition >= totalWidth;
        };

        if (filters) {
            filters.addEventListener('click', e => {
                if (!e.target.matches('.btn-filter')) return;
                filters.querySelector('.active').classList.remove('active');
                e.target.classList.add('active');
                const filter = e.target.dataset.filter;
                
                cards.forEach(card => {
                    card.style.display = (filter === 'all' || card.dataset.city === filter) ? 'flex' : 'none';
                });
                currentIndex = 0;
                updateSlider();
            });
        }

        if (nextBtn) nextBtn.addEventListener('click', () => { if(!nextBtn.disabled) { currentIndex++; updateSlider(); }});
        if (prevBtn) prevBtn.addEventListener('click', () => { if(!prevBtn.disabled) { currentIndex--; updateSlider(); }});
        
        window.addEventListener('resize', updateSlider);
        updateSlider();
    }
});