const backToTopButton = document.getElementById('backToTop');

// スクロールでボタンを表示／非表示
window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
        backToTopButton.classList.add('show');
    } else {
        backToTopButton.classList.remove('show');
    }
});

// スクロール要素を取得
// const scrollElm = document.documentElement || document.body;
const scrollElm = document.scrollingElement || document.documentElement;

backToTopButton.addEventListener('click', (e) => {
    e.preventDefault();
    // 任意の要素位置へスクロール
    // const targetElm = document.querySelector('#some-section'); // 任意の要素
    // const targetPos = targetElm.getBoundingClientRect().top + window.scrollY;
    const targetPos = 0;

    const startTime = Date.now();
    const scrollFrom = scrollElm.scrollTop;
    const duration = 400;

    const easing = function easeInOutCubic(t, b, c, d) {
        t /= d / 2;
        if (t < 1) return c / 2 * t * t * t + b;
        t -= 2;
        return c / 2 * (t * t * t + 2) + b;
    };

    (function loop() {
        const currentTime = Date.now() - startTime;
        if (currentTime < duration) {
            window.scrollTo(0, easing(currentTime, scrollFrom, targetPos - scrollFrom, duration));
            requestAnimationFrame(loop);
        } else {
            window.scrollTo(0, targetPos);
        }
    })();
});
