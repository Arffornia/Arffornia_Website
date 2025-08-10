document.addEventListener('DOMContentLoaded', () => {
    const slideshowContainer = document.querySelector('.slideshow-container');

    if (!slideshowContainer) {
        return;
    }

    let slides = [];
    let currentIndex = 0;
    const slideInterval = 3000;

    async function fetchAndCreateSlides() {
        try {
            const response = await fetch('/api/launcherImages');
            if (!response.ok) {
                throw new Error('Failed to fetch launcher images');
            }
            const imageUrls = await response.json();

            if (!imageUrls || imageUrls.length === 0) {
                slideshowContainer.innerHTML = '<p>No images to display.</p>';
                return;
            }

            slideshowContainer.innerHTML = '';
            imageUrls.forEach(url => {
                const img = document.createElement('img');
                img.src = url;
                img.className = 'slide';
                slideshowContainer.appendChild(img);
            });

            slides = slideshowContainer.querySelectorAll('.slide');
            if (slides.length > 0) {
                slides[ 0 ].classList.add('active');
                if (slides.length > 1) {
                    setInterval(nextSlide, slideInterval);
                }
            }
        } catch (error) {
            console.error('Slideshow error:', error);
            slideshowContainer.innerHTML = '<p>Error loading images.</p>';
        }
    }

    function nextSlide() {
        const oldActiveSlide = slides[ currentIndex ];

        slides.forEach(slide => slide.classList.remove('previous'));

        currentIndex = (currentIndex + 1) % slides.length;
        const newActiveSlide = slides[ currentIndex ];

        oldActiveSlide.classList.remove('active');
        oldActiveSlide.classList.add('previous');

        newActiveSlide.classList.add('active');
    }

    fetchAndCreateSlides();
});
