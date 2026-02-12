;(function (global) {
    'use strict';

    function h(tag, attrs, children) {
        var el = document.createElement(tag);

        if (attrs) {
            Object.keys(attrs).forEach(function (key) {
                if (key === 'className') {
                    el.className = attrs[key];
                    return;
                }

                if (key === 'selected' && attrs[key]) {
                    el.selected = true;
                    return;
                }

                if (key.indexOf('on') === 0 && typeof attrs[key] === 'function') {
                    el.addEventListener(key.slice(2).toLowerCase(), attrs[key]);
                    return;
                }

                el.setAttribute(key, attrs[key]);
            });
        }

        if (children) {
            children.forEach(function (child) {
                if (typeof child === 'string') {
                    el.appendChild(document.createTextNode(child));
                    return;
                }

                if (child) {
                    el.appendChild(child);
                }
            });
        }

        return el;
    }

    function hs(tag, attrs, children) {
        var el = document.createElementNS('http://www.w3.org/2000/svg', tag);

        if (attrs) {
            Object.keys(attrs).forEach(function (key) {
                el.setAttribute(key, attrs[key]);
            });
        }

        if (children) {
            children.forEach(function (child) {
                if (child) {
                    el.appendChild(child);
                }
            });
        }

        return el;
    }

    function lockScroll() {
        document.body.style.overflow = 'hidden';
    }

    function unlockScroll() {
        document.body.style.overflow = '';
    }

    function createOverlay(onClose) {
        var overlay = h('div', {
            className: 'fixed inset-0 z-[9998] bg-[color:rgba(31,37,34,0.55)]'
        });

        if (typeof onClose === 'function') {
            overlay.addEventListener('click', onClose);
        }

        return overlay;
    }

    function closeIcon() {
        return hs('svg', {
            xmlns: 'http://www.w3.org/2000/svg',
            viewBox: '0 0 24 24',
            width: '20',
            height: '20',
            fill: '#000',
            stroke: '#000',
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            'aria-hidden': 'true'
        }, [
            hs('line', { x1: '18', x2: '6', y1: '6', y2: '18' }),
            hs('line', { x1: '6', x2: '18', y1: '6', y2: '18' })
        ]);
    }

    function removeModal(overlay, dialog) {
        unlockScroll();

        if (overlay && overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
        }

        if (dialog && dialog.parentNode) {
            dialog.parentNode.removeChild(dialog);
        }

        if (dialog && dialog._escHandler) {
            document.removeEventListener('keydown', dialog._escHandler);
        }
    }

    global.KeuanganUI = global.KeuanganUI || {};
    global.KeuanganUI.modalCore = {
        h: h,
        hs: hs,
        lockScroll: lockScroll,
        unlockScroll: unlockScroll,
        createOverlay: createOverlay,
        closeIcon: closeIcon,
        removeModal: removeModal
    };
})(window);
