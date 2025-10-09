export class SvgMap {
    constructor() {
        this._init();
    }

    _init() {
        const $tooltip = $("#map-tooltip");

        if ($tooltip.length === 0) {
            return;
        }

        const positionTooltip = (evt) => {
            const padding = 8;
            const x = evt.clientX;
            const y = evt.clientY;

            const rect = $tooltip[0].getBoundingClientRect();
            let left = x + 12;
            let top = y - rect.height - 12;

            // keep tooltip inside viewport
            if (left + rect.width + padding > window.innerWidth) {
                left = x - rect.width - 12;
            }

            if (top < padding) {
                top = y + 12;
            }

            $tooltip.css({
                left,
                top,
            });
        };

        const getTooltipHtml = (pathEl) => {
            const countryName = $(pathEl).data("name");
            const pageViews = $(pathEl).data("pageviews");
            const uniqueVisitors = $(pathEl).data("unique-visitors");
            const visits = $(pathEl).data("visits");

            let html = `<div class="svg-tooltip-content">`;
            html += `<div class="svg-tooltip-content-hl">${countryName}</div>`;

            if (pageViews) {
                html += `<div>Pageviews: ${pageViews}</div>`;
            }

            if (uniqueVisitors) {
                html += `<div>Unique: ${uniqueVisitors}</div>`;
            }

            if (visits) {
                html += `<div>Visits: ${visits}</div>`;
            }

            html += `</div>`;

            return html;
        };

        $("#svg-world-map path").on({
            mouseenter: (evt) => {
                const tooltipHtml = getTooltipHtml(evt.currentTarget);
                $tooltip.html(tooltipHtml).addClass("show");
                positionTooltip(evt);
            },
            mousemove: (evt) => positionTooltip(evt),
            mouseleave: () => $tooltip.removeClass("show"),
            touchstart: (evt) => {
                const touch = evt.originalEvent.touches[0];
                const tooltipHtml = getTooltipHtml(evt.currentTarget);
                $tooltip.html(tooltipHtml).addClass("show");
                positionTooltip(touch);
                evt.preventDefault();
            },
            touchend: () => $tooltip.removeClass("show"),
        });

        $(window).on("scroll resize", () => $tooltip.removeClass("show"));
    }
}
