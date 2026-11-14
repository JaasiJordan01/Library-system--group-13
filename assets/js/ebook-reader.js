(() => {
    const container = document.getElementById('epub-reader');
    const status = document.querySelector('[data-epub-status]');
    const previousButton = document.querySelector('[data-epub-previous]');
    const nextButton = document.querySelector('[data-epub-next]');

    if (!container || !status || !previousButton || !nextButton) {
        return;
    }

    if (typeof window.ePub !== 'function') {
        status.textContent = 'The EPUB reader could not load. Check your internet connection and refresh this page.';
        return;
    }

    const book = window.ePub(container.dataset.epubUrl, { openAs: 'epub' });
    const rendition = book.renderTo(container, {
        width: '100%',
        height: '100%',
        flow: 'paginated'
    });

    const setControlsEnabled = (enabled) => {
        previousButton.disabled = !enabled;
        nextButton.disabled = !enabled;
    };

    setControlsEnabled(false);
    rendition.on('relocated', (location) => {
        const displayed = location && location.start && location.start.displayed;
        status.textContent = displayed && displayed.total
            ? 'Page ' + displayed.page + ' of ' + displayed.total
            : 'Book opened';
    });

    rendition.display().then(() => {
        setControlsEnabled(true);
    }).catch(() => {
        status.textContent = 'This EPUB could not be opened. Ask library staff to check the uploaded file.';
    });

    previousButton.addEventListener('click', () => {
        rendition.prev().catch(() => {
            status.textContent = 'The previous page could not be opened.';
        });
    });
    nextButton.addEventListener('click', () => {
        rendition.next().catch(() => {
            status.textContent = 'The next page could not be opened.';
        });
    });

    window.addEventListener('pagehide', () => book.destroy(), { once: true });
})();
